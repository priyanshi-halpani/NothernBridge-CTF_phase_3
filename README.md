# Northenbridge College CTF Lab

A fictional college web portal built as a beginner-friendly Capture The Flag (CTF) lab.

The project combines a simple student portal with a deliberately vulnerable college records system. Players are expected to explore the application, follow in-application clues, discover hidden functionality, and eventually modify their own academic record to complete the challenge.

> [!CAUTION]
> **Educational use only:** This application intentionally contains vulnerabilities and fictional data. Do not deploy it on an untrusted or public network.

---

## Features

### Student Portal

* College homepage
* Student registration
* Automatic student credential generation
* Credential download
* Student login/logout
* Profile viewing and editing
* Personal marks and result status
* Reassessment functionality containing a CTF clue

---

## CTF Challenge

The challenge is designed around a simple progression:

```text
Student Portal
      │
      ▼
Registration & Login
      │
      ▼
Failed Result
      │
      ▼
Reassessment Clue
      │
      ▼
Credential Discovery
      │
      ▼
Elevated Access
      │
      ▼
Record Management
      │
      ▼
SQL Injection
      │
      ▼
Discover Player Record
      │
      ▼
Modify Marks
      │
      ▼
PASS + Final Flag
```

The challenge contains **four flags**, with each stage leading toward the next part of the application.

For the detailed challenge flow, see [CTF-Flow.md](https://github.com/Incogn1mu5/Northenbridge-College-CTF/blob/9ce116be7239ff602a58199404a981ab9af41beb/Docs/CTF-Flow.md).

---

## Technology Stack

* **Vagrant** — VM provisioning
* **VirtualBox** — virtualization
* **Ubuntu 22.04** — guest operating system
* **Apache2** — web server
* **PHP** — application
* **SQLite** — database
* **Bash** — provisioning
* **Git** — portal source is cloned from GitHub during provisioning

The project uses **no shared folders**: the web application is deployed entirely by the provisioning script (clone → install → configure), and only that script's outputs are ever served.

---

## Project Structure

```text
northenbridge-ctf/
├── README.md
├── Vagrantfile          (no synced folders — provisioning-only build)
├── seed.sql             (source-of-truth seed, idempotent)
├── scripts/
│   └── provision.sh     (the ONE script that builds the entire lab)
├── www/                 (portal app source; deployed via git clone)
│   ├── index.php        (homepage)
│   ├── login.php        (student login)
│   ├── register.php     (student registration)
│   ├── profile.php      (student profile)
│   ├── marks.php        (exam result)
│   ├── logout.php
│   ├── db.php           (database connection)
│   ├── robots.txt       (generic — no clues)
│   ├── 404.html         (branded not-found page)
│   ├── 500.html         (branded internal-error page)
│   └── includes/        (shared header/footer + runtime bootstrap)
│       ├── init.php
│       ├── header.php
│       └── footer.php
├── Docs/
│   ├── Architecture.md
│   ├── CTF-Flow.md
│   ├── Deployment.md
│   └── Vulnerabilities_&_Testing.md
└── Screenshots/
    ├── homepage.png
    ├── student-portal.png
    └── marks-page.png
```

---

## Screenshots

### College Homepage
<img width="2235" height="865" alt="PwnAD_Banner" src="https://github.com/Incogn1mu5/Northenbridge-College-CTF/blob/9ce116be7239ff602a58199404a981ab9af41beb/Screenshots/Northenbridge-Home_page.png" />  

### Student Portal
<img width="2235" height="865" alt="PwnAD_Banner" src="https://github.com/Incogn1mu5/Northenbridge-College-CTF/blob/9ce116be7239ff602a58199404a981ab9af41beb/Screenshots/Northenvridge-Student-Login_page.png" />  

### Student Marks
<img width="2235" height="865" alt="PwnAD_Banner" src="https://github.com/Incogn1mu5/Northenbridge-College-CTF/blob/9ce116be7239ff602a58199404a981ab9af41beb/Screenshots/Northenvridge-Student-Exam-Result_page.png" />


---

## Deployment

The lab is **provisioning-only**: it builds itself inside a fresh Vagrant-managed Ubuntu VM with no shared folders, no manual copy steps and no runtime dependency on the host filesystem. One script, `infra/provision.sh`, does everything.

### Requirements

Install the following on the host machine:

* VirtualBox
* Vagrant
* Git

### Start the Lab

Clone the repository and start the VM:

```bash
git clone <repository-url>
cd northenbridge-ctf
vagrant up
```

The provisioning script installs Apache, PHP, SQLite and the required PHP SQLite extension, configures the virtual host, clones the portal application from GitHub into `/var/www/html`, initializes the database from `seed.sql` (idempotently) and places the challenge artifacts (decoy `.env` credential file and the filesystem final flag).

The source of truth for the deployment is `infra/provision.sh`. The variables at the top of the script control where the portal is cloned from and where it is installed:

```bash
PORTAL_REPO="https://github.com/<org>/northbridge-portal.git"  # GitHub URL of the portal
PORTAL_BRANCH="main"                                          # branch / tag to deploy
PORTAL_SUBDIR="www"                                           # app directory inside the repo
PORTAL_SEED="seed.sql"                                        # seed file inside the repo
PORTAL_DIR="/var/www/html"                                    # VM-local web root
```

### Find the VM IP

The VM uses **bridged networking**, allowing other devices on the same local network to access the CTF.

Run:

```bash
vagrant ssh
hostname -I
```

Use the VM's LAN address from another device:

```text
http://<VM-IP>/
```

For example:

```text
http://192.168.1.50/
```

The exact IP will depend on the local network.

### Multi-Player Access

The intended setup is:

```text
                 Local Network
                       │
        ┌──────────────┼──────────────┐
        │              │              │
     Player 1       Player 2       Player 3
        │              │              │
        └──────────────┼──────────────┘
                       │
                Host Computer
                       │
                VirtualBox VM
                       │
                Apache + PHP
                       │
                  SQLite DB
```

Only the host computer needs to run the VM. Players connect to the VM's bridged IP from devices connected to the same network.
More deployment details are available in [Deployment.md](https://github.com/Incogn1mu5/Northenbridge-College-CTF/blob/9ce116be7239ff602a58199404a981ab9af41beb/Docs/Deployment.md).

---

## Resetting the Lab

To completely recreate the VM from a clean clone:

```bash
vagrant destroy -f
vagrant up
```

The provisioning process recreates the application environment, seeds the SQLite database and places the challenge artifacts.

`infra/provision.sh` is idempotent: running `vagrant provision` again will **not** wipe an existing database — seeding is skipped whenever the database already contains tables, so a reprovision mid-session does not reset player progress.

**Do not destroy and recreate the VM while a CTF session is in progress**, because `vagrant destroy -f` removes the VM and therefore the challenge data.

---

## Intentional Vulnerabilities

The vulnerabilities are deliberately included as part of the CTF and are restricted to the fictional application.

The main challenge elements include:

* Hidden functionality reachable only through directory enumeration
* Information disclosure through a configuration artifact
* Limited student-record visibility
* SQL injection in the record search functionality
* Marks manipulation through the records interface

The marks update itself uses a prepared SQL statement; the intentional SQL injection is in the student-record search functionality.

Detailed testing information is available in [Vulnerabilities_&_Testing.md](https://github.com/Incogn1mu5/Northenbridge-College-CTF/blob/9ce116be7239ff602a58199404a981ab9af41beb/Docs/Vulnerabilities_%26_Testing.md).

---

## License

See [`LICENSE`](LICENSE).
