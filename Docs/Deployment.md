# Deployment

Northenbridge College CTF runs inside a Vagrant-managed Ubuntu VM. The VM hosts the complete website and database, so player devices only need a web browser.

The lab is **provisioning-only**:

* No Vagrant **synced folders** (`/vagrant`, `./www` are disabled in the `Vagrantfile`).
* No manual copy steps and no runtime dependency on the host filesystem.
* One script, `scripts/provision.sh`, builds the entire CTF: it installs packages, clones the portal application from GitHub into `/var/www/html`, configures Apache, seeds the SQLite database, and places the challenge artifacts (decoy `.env` file and the filesystem final flag).

## Requirements

The host machine needs:

* VirtualBox
* Vagrant
* Git
* A local network that the player devices can access
* Internet access on first `vagrant up` (APT packages and the portal git clone)

The player devices do not need Vagrant, VirtualBox or the project files.

## Reproducibility contract

From a **clean clone** on any host:

```bash
git clone <repository-url>
cd northenbridge-ctf
vagrant up
```

produces an identical working lab. A full reset is:

```bash
vagrant destroy -f
vagrant up
```

The portal application is never read from the host at runtime — it is cloned into `/var/www/html` inside the VM by `scripts/provision.sh`.

## Provisioning (`scripts/provision.sh`)

The script is fully declarative. The configuration variables live at the top of the file:

```bash
PORTAL_REPO="https://github.com/Incogn1mu5/Northenbridge-College-CTF.git"  # GitHub URL of the portal source
PORTAL_BRANCH="main"                                                       # branch / tag to deploy
PORTAL_SUBDIR="www"                                                        # app directory inside the repo
PORTAL_SEED="seed.sql"                                                     # seed file inside the repo
PORTAL_DIR="/var/www/html"                                                 # VM-local web root
```

Steps performed:

1. Updates packages and installs `apache2`, `php`, `libapache2-mod-php`, `php-sqlite3`, `sqlite3`, `git`, `curl`, `rsync`.
2. Clones (or pulls) `PORTAL_REPO` at `PORTAL_BRANCH` into `/opt/northbridge-src`.
3. Installs the app from `PORTAL_SUBDIR` into `PORTAL_DIR` — the `database/` directory is excluded from the sync.
4. Enables Apache modules (`rewrite`, `php`), writes the `northbridge` virtual host pointing at `PORTAL_DIR` with custom `ErrorDocument` directives (`404.html` / `500.html`), disables directory listing, blocks direct `*.db` serving, denies direct access to `PORTAL_DIR/includes`, and applies production PHP settings (display errors off, logging on).
   The app's `.htaccess` (deployed with the portal) maps `/profile` and `/marks` to their `.php` pages (so unauthenticated requests there redirect to login) and funnels unknown routes through the branded 404 page.

The same variables can be overridden from the host shell before `vagrant provision`, which is how a lab operator deploys a custom portal fork (for example a local git-over-HTTP mirror) without editing the script:

```powershell
$env:PORTAL_REPO = "http://10.0.2.2:9418/mirror"
vagrant provision
```
5. Places the decoy credential file (`PORTAL_DIR/.env`) used by the password-spray stage.
6. Places the final flag on the filesystem at `/opt/northbridge/flag.txt` (outside the database); the legacy `flag-final.txt` path is removed.
7. **Idempotently** initializes the SQLite database from `PORTAL_SEED`. The seed is re-applied on every provision using `CREATE IF NOT EXISTS` / `INSERT OR IGNORE` (plus a small migration block), so existing player rows are never overwritten.
8. Sets permissions (`www-data`), restarts Apache and verifies the site answers HTTP 200 on `127.0.0.1:80`.

### Idempotency

`vagrant provision` may be run repeatedly:

* The git checkout is updated in place (`fetch` + `checkout -B`).
* The web files are re-synced with `rsync` (database directory excluded).
* The decoy `.env` is always rewritten from the script (it is fictional data, not player data).
* The flag file is only written if missing, so a lab keeps its unique flag across reprovisions.
* The database seed is re-applied with `INSERT OR IGNORE` semantics, so existing rows are untouched.

### Addresses

The VM listens on two interfaces:

| Interface | Purpose | Access |
|---|---|---|
| `127.0.0.1:8080` (host-forwarded) | Host browser | `http://localhost:8080/` |
| Bridged LAN IP | Player devices on the same network | `http://<VM-IP>/` |

## Apache

Apache listens on port `80` inside the VM. The project uses the `northbridge` Apache virtual host and serves the application from:

```text
/var/www/html
```

The student portal and admin portal are hosted by the same Apache instance.  

## Player Access

Once the VM is running, test the website from the host first:

```text
http://localhost:8080/
http://<VM-IP>/
```

Then test the same address from another device connected to the same network.

If a second device cannot connect, check:

* The VM received a LAN IP address.
* The host and player device are on the same network.
* The network does not have client/AP isolation enabled.
* Apache is running.
* Port `80` is not being blocked by the host or VM firewall.

The `northenbridge.local` Apache `ServerName` does not automatically create DNS for player devices, so using the VM's IP address is the simplest option for the CTF.  

## Challenge Artifacts

Deployed by the provisioning script, never committed to the repository:

| Artifact | Location | Purpose |
|---|---|---|
| SQLite database | `/var/www/html/database/college.db` | application data, built from `seed.sql` |
| Decoy credential file | `/var/www/html/.env` | candidate admin passwords for the spray stage |
| Final flag | `/opt/northbridge/flag.txt` | filesystem flag — **not** in the database |

## Resetting the Lab

`seed.sql` is the source of truth for the initial database state, and the provisioning script regenerates everything.

For a fresh environment, destroy and recreate the VM:

```bash
vagrant destroy -f
vagrant up
```

Re-running `vagrant provision` on a live VM does **not** wipe the database (seeding is guarded), but `vagrant destroy -f` removes the whole VM so it always returns the lab to its seeded state.

Do not reset the database while players are using the CTF, as this can reset their progress and records.  

## Stopping the Lab

To stop the VM without removing it:

```bash
vagrant halt
```

Start it again with:

```bash
vagrant up
```

To completely remove the VM:

```bash
vagrant destroy -f
```

Note that the repository no longer relies on shared folders: `vagrant destroy` removes the VM, and a subsequent `vagrant up` rebuilds the environment entirely inside the VM.  

## Troubleshooting

**`vagrant up` fails with `Could not rename the directory '...' to '...\northenbridge-ctf' ... (VERR_ALREADY_EXISTS)`** (Windows/VirtualBox).

This happens when a previous `vagrant destroy` leaves a stale `northenbridge-ctf` folder behind (usually containing only `Logs/`). VirtualBox refuses to rename a fresh box import over it. Fix on the host:

```powershell
Remove-Item -LiteralPath "C:\Users\Dell\VirtualBox VMs\northenbridge-ctf" -Recurse -Force
vagrant up
```

If the `vagrant destroy` also leaves an orphaned registered VM (check with `VBoxManage list vms`), remove it as well:

```powershell
VBoxManage unregistervm "ubuntu-jammy-22.04-cloudimg-*" --delete
```

**First `vagrant up` reports an SSH boot timeout but the VM keeps running.**

On slow disks the fresh box can take more than 10 minutes before the SSH service answers. Confirm the VM is `running` (`vagrant status`) and simply run `vagrant up` again — Vagrant continues from where it stopped and runs the provisioner.