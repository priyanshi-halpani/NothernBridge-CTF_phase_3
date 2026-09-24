# Testing Evidence — Section 11 / 12 of the Hardening Checklist

Run date: 2026-09-24 (two environments: the pre-reset VM and a **fresh** VM after the full
reset in T21).

* Host: Windows, Vagrant + VirtualBox, `ubuntu/jammy64` box.
* VM: `northenbridge-ctf`, NAT `10.0.2.15` (bridged `192.168.201.102`), 2 vCPU / 2 GB.
* Host access: `http://127.0.0.1:8080/` (forwarded port 80 → 8080, bound to 127.0.0.1).
* In-VM access: `http://127.0.0.1:80/`.
* Built end-to-end via `infra/provision.sh` only — no shared folders, no manual copying.

## How this suite was run

Provisioning-verified flows used the repository as a **bare mirror** cloned into the VM
(`/opt/northbridge-bare.git`), emulating the documented GitHub fetch without requiring
host-side GitHub credentials. All site tests used `curl`; enumeration used `ffuf`
(installed on the VM **for the test pass only** — the lab provisioning intentionally does
not install attack tools). The word list was `common.txt` (dirb, 4,751 entries).

## Deviations from the checklist text (all documented)

| # | Deviation | Resolution |
|---|---|---|
| D1 | No GitHub push/clone credentials available on this host | A true `git clone <url> && vagrant up` could not be executed. T1/T21 were instead validated on a **fresh** VM (created after `vagrant destroy -f`) using a local bare clone of the repository — identical code path (`git clone` → `/var/www/html`). The GitHub flow itself is unchanged and documented in README / Docs/Deployment.md. |
| D2 | `ffuf` is not part of the lab box | Installed for the test run only (`apt-get install -y ffuf`). The exact fuzz command is unchanged. |
| D3 | Checklist says the DB is `/var/www/html/app.db` | The database is actually created at `/var/www/html/database/college.db` (see `.env`). The `flags` table intentionally contains the three **in-application progression flags** (`Flag_01`–`Flag_03`); the **final flag never exists in the database** (zero `NCC{` tokens in a full `.dump`). |
| D4 | 500 page | Every portal page bootstraps `includes/init.php`, which installs the branded generic 500 renderer; a raw, non-bootstrapped script in the webroot falls back to Apache's minimal 500 (no details either way). |

---

## Test 1 — End-to-end build (clean clone)

| | |
|---|---|
| Command | `git clone <repository-url> && cd northenbridge-ctf && vagrant up` |
| Expected | Build completes; portal responds |
| Actual | Not directly runnable here (**D1**). Validated equivalently: **Test 21** rebuilt a fresh VM from a bare clone of the repository → provision completed, site returned `HTTP 200` |
| Result | ✳ PASS (equivalent) |

## Test 2 — No host ↔ VM shared folders

| | |
|---|---|
| Command | `mount \| grep vagrant` ; `ls /vagrant` |
| Expected | No vagrant sync mounts; `/vagrant` absent |
| Actual | `mount | grep vagrant` → no output; `ls /vagrant` → `ls: cannot access '/vagrant': No such file or directory` |
| Result | ✔ PASS |

## Test 3 — Re-run provisioning (idempotency)

| | |
|---|---|
| Command | `$env:PORTAL_REPO="/opt/northbridge-bare.git"; vagrant provision` (second run) |
| Expected | Succeeds; database and flag file NOT reset |
| Actual | "Provisioning complete!"; `students=16` (players preserved), `flags=3`, flag file `/opt/northbridge/flag.txt` retained (`Sep 21 09:52`, not regenerated), site `HTTP 200` |
| Result | ✔ PASS |

## Test 4 — Webroot & decoy `.env`

| | |
|---|---|
| Command | `ls -la /var/www/html` ; `cat /var/www/html/.env \| head` (specifically `head -n 20`) |
| Expected | Portal files present; `.env` starts with the LAB-ONLY banner and lists credential pairs |
| Actual | Files present incl. `.env` (1260 B), `.htaccess`, `404.html`, `500.html`, `admin/`, `database/`. `.env` head shows `# LAB ONLY — FICTIONAL CREDENTIALS`, `APP_ENV=production`, `APP_DEBUG=false`, then 12 `username:password` pairs |
| Result | ✔ PASS |

## Test 5 — Homepage

| | |
|---|---|
| Command | `curl -i http://127.0.0.1:8080/` |
| Expected | 200, Northbridge branding |
| Actual | `HTTP 200` (15682 B); `<title>Northenbridge College</title>`, brand header, "ESTABLISHED IN PURPOSE, BUILT ON RIGOR" |
| Result | ✔ PASS |

## Test 6 — Branded 404, no server leakage

| | |
|---|---|
| Command | `curl -i http://127.0.0.1:8080/definitely-not-here-xyz` |
| Expected | 404 with branded page; no Apache version/paths in body |
| Actual | `HTTP 404` (2276 B), branded `<title>404 &mdash; Page Not Found | Northenbridge College</title>`, `<h1>404</h1>`; no Apache identification |
| Result | ✔ PASS |

## Test 7 — Unauthenticated `/profile`

| | |
|---|---|
| Command | `curl -i http://127.0.0.1:8080/profile` |
| Expected | Redirect to login (or 403 page) |
| Actual | `HTTP 302` → `login.php` (auth enforced server-side inside the PHP page) |
| Result | ✔ PASS |

## Test 8 — Invalid registration / login (inline errors, no leaked details)

| | |
|---|---|
| Command | `curl -X POST -d "first_name=…&last_name=…&email=…&password=tt" /register.php` ; `curl -X POST -d "email=…&password=wrongpass" /login.php` |
| Expected | 200 + inline message; no stack trace or SQL text in HTML |
| Actual | Registration (missing field): `.error-box` → "Please correct the following: … Contact number is required." Registration (duplicate email): rejected inline, only one row created, HTTP 200. Student login (wrong password): HTTP 200, alert `Invalid student email or password.` Zero matches for `Fatal|Warning:|SQLite3Exception|Stack trace|\.php on line` on either page |
| Result | ✔ PASS |

## Test 9 — Application error → generic branded 500, details only in error log

| | |
|---|---|
| Command | Temporary bootstrap-valid probe `t500i.php` (`require includes/init.php; throw new Exception("SECRET_INTERNAL_DETAIL_LEAK")`), `curl -i http://127.0.0.1:8080/t500i.php`, then removed |
| Expected | HTTP 500, generic branded page, detail only in server error log |
| Actual | `HTTP 500`, body = 500.html (2245 B): `<title>500 &mdash; Service Unavailable | Northenbridge College</title>` + "Something went wrong". No `SECRET_INTERNAL_DETAIL_LEAK` in response. Server log only: `[Northbridge] Uncaught exception: SECRET_INTERNAL_DETAIL_LEAK @ /var/www/html/t500i.php:1`. Probe deleted after test (D4) |
| Result | ✔ PASS |

## Test 10 — Public source / fuzzable exposure

| | |
|---|---|
| Command | `curl -s /robots.txt`, `/sitemap.xml`, and grep `curl -s index.php|about.php|academics.php|admissions.php|contact.php|events.php|login.php|profile.php|marks.php|register.php` for `/admin/`, `.env`, `NCC{`, `Winter2026`, `helen.carter` |
| Expected | No admin/env/flag/credential strings on public pages |
| Actual | `robots.txt` → `User-agent: * / Disallow:`; `/sitemap.xml` → HTTP 404; **all ten pages: clean** (no hits) |
| Result | ✔ PASS |

## Test 11 — Directory fuzzing (ffuf)

| | |
|---|---|
| Command | `ffuf -u http://127.0.0.1:80/FUZZ -w /home/vagrant/common.txt -mc 200,301,302,403,500 -s -t 20` |
| Expected | `admin` (and/or `/.env`) appears; only expected resources returned |
| Actual | `admin` (301 → login portal), `.env` (200), `database` (403), `includes` (403), `index.php`, `profile`, `robots.txt`, plus Apache's own 403s on `.htaccess/.htpasswd/.hta`, `Thumbs.db` (D2) |
| Result | ✔ PASS |

## Test 12 — Decoy `.env` reachable

| | |
|---|---|
| Command | `curl -i http://127.0.0.1:8080/.env` |
| Expected | 200, plain text, 8–12 lines, LAB-ONLY label |
| Actual | `HTTP 200`, `Content-Type: text/plain`, body 1260 B (12 lines incl. banner), starts `# LAB ONLY — FICTIONAL CREDENTIALS`. Served via `FilesMatch "^\.env$" + ForceType text/plain` (AddType misses hidden filenames) |
| Result | ✔ PASS |

## Test 13 — Credential spray (wrong pairs)

| | |
|---|---|
| Command | for P in `Winter2025! Winter2027! Password123`; do `curl -X POST -d "username=helen.carter&password=$P" /admin/`; done |
| Expected | All wrong pairs → same generic error, HTTP 200, no flag markers, attempts logged |
| Actual | All three → `HTTP 200`, identical page (md5 of the 10,973 B bodies identical = 1 distinct), zero flag markers; `POST /admin/ *` entries present in `northbridge_access.log` |
| Result | ✔ PASS |

## Test 14 — Correct credential logs in

| | |
|---|---|
| Command | `curl -X POST -d "username=helen.carter&password=Winter2026!" /admin/` |
| Expected | 302 → working admin session |
| Actual | `HTTP 302`; dashboard `HTTP 200` |
| Result | ✔ PASS |

## Test 15 — Limited admin view (own-department only)

| | |
|---|---|
| Command | Admin-cookie `curl "/admin/students.php?q="` |
| Expected | Only the clerk's department rows; other departments absent |
| Actual | Rendered rows contain **only** `Information Technology` (9 mentions, 8 rows on the pre-reset VM; 6 dept mentions on the fresh VM); no `Computer Science / Business Administration / Mechanical Engineering` cells |
| Result | ✔ PASS |

## Test 16 — Cross-department student hidden in normal UI

| | |
|---|---|
| Command | Admin-cookie `curl "/admin/students.php?q=Riya"` (Riya Patel is Computer Science) |
| Expected | No rows / no cross-dept data |
| Actual | Empty table (header only, no `<td>` data) and `riyapatel@northenbridge.lab` never rendered |
| Result | ✔ PASS |

## Test 17 — SQLi breaks the scope filter (`OR 1=1`)

| | |
|---|---|
| Command | Admin-cookie `curl "/admin/students.php?q=%27)%20OR%201%3D1%20--%20"` |
| Expected | The `q` injection joins the intended filter out and returns cross-department rows |
| Actual | Rendered table now also shows `Computer Science` records (dept-scope bypassed). Malformed variants produce only a friendly "no records" page **and a warning line in the server error log** — never a client-side leak |
| Result | ✔ PASS |

## Test 18 — Database dump via UNION

| | |
|---|---|
| Command | Admin-cookie `curl "/admin/students.php?q=%27)%20UNION%20SELECT%20type,name,tbl_name,rootpage,sql%20FROM%20sqlite_master%20--%20"` and the same against `compliance_notes` |
| Expected | Table metadata + the compliance note surfacing the filesystem flag path |
| Actual | sqlite_master dump returns all tables (`admins, students, marks, flags, compliance_notes, courses, faculty, sqlite_sequence`). compliance_notes row: `Full audit log archived at /opt/northbridge/flag.txt (lab host only). 2026-09-01` |
| Result | ✔ PASS |

## Test 19 — Final flag is NOT in the database

| | |
|---|---|
| Command | `sqlite3 college.db "select * from flags;"` ; `sqlite3 college.db ".dump" \| grep -c 'NCC{'` |
| Expected | No row for the final flag; flag not stored in DB |
| Actual | `flags` holds only the 3 in-app progression flags by design (`Flag_01 …`, `Flag_02 …`, `Flag_03 …`); `.dump` contains **zero** `NCC{` tokens — the final flag is absent from the database (D3) |
| Result | ✔ PASS |

## Test 20 — Filesystem final flag

| | |
|---|---|
| Command | `ls -la /opt/northbridge/flag.txt` ; `cat /opt/northbridge/flag.txt` |
| Expected | `root:www-data`, mode 0640, `NCC{...}` unique per deployment, not in Git/DB |
| Actual | `-rw-r----- 1 root www-data 321`. Body: "NoT in DB / Git" wording; token for this deployment **`NCC{<redacted>}`** (the previous deployment produced a different token, `NCC{<redacted>}` → per-deployment uniqueness confirmed) |
| Result | ✔ PASS |

## Test 21 — Full reset

| | |
|---|---|
| Command | `vagrant destroy -f` → `vagrant up --no-provision` → re-upload bare clone mirror → `vagrant provision` |
| Expected | Reproducible build; all artifacts regenerated; data reseeded |
| Actual | Fresh VM built from the same repo: provision "complete"; `students=11` (clean seed), `courses=10`, `faculty=8`, `compliance_notes=3`, `flags=3`; all content pages 200; `.env` 200 text/plain with 12 pairs; admin login 302; **new** flag token generated (D1) |
| Result | ✔ PASS |

---

## Deliverables checklist (Section 12)

| Deliverable | Status |
|---|---|
| `Vagrantfile` — synced folders disabled, forwarded port 80→8080 (127.0.0.1), provision path `infra/provision.sh` | ✔ PASS |
| Provision script at **`infra/provision.sh`** — single source of truth; idempotent; writes decoy `.env` + filesystem flag; no GitHub secrets | ✔ PASS (moved from `scripts/`, all 15 references updated, `bash -n`/runtime verified) |
| Portal source deployable via `git clone` → `/var/www/html` | ✔ PASS locally (bare-mirror clone); GitHub push/clone itself blocked by missing credentials (**D1**) |
| Decoy `.env` — 12 fictional pairs, "LAB ONLY", text/plain | ✔ PASS |
| Final flag — filesystem only, `0640 root:www-data`, unique per deployment | ✔ PASS |
| Test evidence — this file | ✔ PASS |
| Docs updated — README, Docs/Deployment.md, Docs/Architecture.md, Docs/ctf-design.md (incl. script relocation) | ✔ PASS |
| Git branch `feature/production-hardening` with ≥ 8 focused commits; no real secrets in history | ✔ PASS (12 commits on the branch; repo contains only fictional `*.lab` accounts and generated-per-deployment `NCC{...}` tokens) |

## Fuzz-exposure summary (what Test 11 legitimately finds)

`/admin/login` portal (unlinked), `/.env` (decoy), `/database` + `/includes` (Apache 403), `/index.php`, `/profile`, `/robots.txt`. Nothing else responds at 2xx/3xx — custom dirs, source archives, and `.git` are absent.