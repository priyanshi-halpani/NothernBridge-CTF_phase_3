# CTF Design Notes (internal)

> Instructor-facing documentation. This file is part of the source
> repository only — it is never copied into the served web root
> (`www/`) and must never be linked from student-facing pages.

## Exercise stages and flags

| Stage | Where it happens | Flag | Location |
|---|---|---|---|
| 1. Discover the hidden admin portal | `/admin/` is unlinked; brute-forceable | `Flag_01` — `Flag{Discovered_hidden_route}` | `flags` table, shown on the admin login page |
| 2. Password spraying | The decoy `/var/www/html/.env` contains 12 fictional `username:password` pairs; **only one** (`helen.carter:Winter2026!`) is valid | (evidence = login + Apache access log) | — |
| 3. Restricted clerk view | `/admin/students.php` limited view | `Flag_02` — `Flag{Respected_the_Restricted_View}` | `flags` table, shown on the restricted student-records page |
| 4. Exploring limited records | `/admin/dashboard.php` dept-scoped record list (seeded `NB-*` records only — fresh `NC-*` registrations stay hidden) | `Flag_03` — `Flag{Explored_Limited_Records}` | `flags` table, shown on the dashboard |
| 5. Database exfiltration (final) | SQL injection on `/admin/students.php?q=...` → dump all tables → read the compliance note | Final flag `NCC{...}` | filesystem `/opt/northbridge/flag.txt`, **not** in the database |

The final (stage 5) flag is created by `infra/provision.sh` with a
random token so every deployment gets a unique value. It is stored as
`root:www-data` with mode `0640`, and the literal value never appears
in Git or in any database table.

## Req 4 — Decoy credential file & password spraying

- `infra/provision.sh` writes `.env` to `/var/www/html/.env` on every
  provision. The file is clearly labelled "LAB ONLY — FICTIONAL
  CREDENTIALS".
- Exactly 12 pairs are present. Exactly one (`helen.carter:Winter2026!`)
  matches the seeded `admins` row, which the provisioner re-syncs on every
  run (so pre-existing databases converge too).
- Every other pair fails with the same generic **"Invalid administrator
  username or password."** message — there is deliberately no user
  enumeration.
- The file is served as `text/plain` (a `<FilesMatch "^\.env$">` block with
  `ForceType text/plain` — required because Apache treats the hidden
  filename `.env` as extensionless, so `AddType` would never match it),
  returns HTTP 200, and is not blocked.
- `LAB ONLY — no throttling`: there is intentionally no lockout,
  rate-limit or CAPTCHA. Spray attempts are recorded in the Apache
  `access.log` (`northbridge_access.log`) — each multi-field POST is one log line.
- The only hint in served HTML is the HTML comment `<!-- backup: /.env -->`
  on the admin login page (`www/admin/index.php`). It must not appear on
  any public page.

## Req 5 — Limited admin view & database exfiltration

### Clerk-view restriction

- `admins` has a `department` column. The seeded admin
  `helen.carter` / `Winter2026!` belongs to **Information Technology**.
- The limited view (`www/admin/students.php`) and the dashboard list are
  scoped to the signed-in admin's department, so a clerk can only see
  their own department's records at a glance (marks integrity by default).
- `edit-marks.php` shows only five own-department `NB-*` records by
  default, but its search box is a **second** intentional SQL injection
  point (see below) that lists every record and enables editing any of
  them — including cross-department and freshly registered `NC-*` players.
- The limited view exposes only: student ID, name, department and
  enrolled-course count (derived from the `marks` table). It displays:  
  *"Complete records require registrar approval — clerk view is
  intentionally restricted."*

### The intentional SQL injection (`?q=...`)

This is the documented injection field for the exfiltration stage.

File: `www/admin/students.php`

```php
$q = trim((string)($_GET['q'] ?? ''));
$deptSafe = $db->escapeString($admin_dept);

$query = "
    SELECT s.student_id, s.first_name, s.last_name, s.department,
           ( ...enrolled_courses... )
    FROM students s
    LEFT JOIN marks m ON s.student_id = m.student_id
    WHERE
        ( s.first_name LIKE '%$q%' OR s.last_name LIKE '%$q%'
          OR s.student_id LIKE '%$q%' OR s.department LIKE '%$q%' )
        AND s.department = '$deptSafe' ORDER BY s.student_id LIMIT 25
";
```

- The `$q` value is concatenated directly into SQL (`%$q%`); the
  department constraint is embedded as a **safe literal** (server-derived,
  `escapeString()`ed) rather than a placeholder. A payload that terminates
  the LIKE expression also escapes the clerk-view limit.
- The whole tail (`AND s.department ... LIMIT 25`) is kept on a single
  line on purpose: SQLite's `--` comments out to end-of-line, so a `--`
  payload cleanly kills the restriction + ordering + limit.
- Working payload shapes:
  ```text
  q=') OR 1=1 --
  q=') UNION SELECT type,name,tbl_name,rootpage,sql FROM sqlite_master --
  q=') UNION SELECT id,note,note_date,1,2 FROM compliance_notes --
  ```
  (Because each LIKE is `'%$q%'`, `')` closes the quoted literal and the
  FROM/paren group.)
- Column count for the SELECT list (needed for `UNION` payloads) is **5**:
  `student_id, first_name, last_name, department, enrolled_courses`.
  Unioned rows land in those fixed output columns.
- Failure path is safe: `try/catch` + "No records match the search
  criteria." — SQL errors are never printed.

### The intentional SQL injection (`edit-marks.php?search=...`)

Second injection point, on the marks editor.

- Default view (no `search`): only five own-department `NB-*` records —
  freshly registered players (`NC-*`) are hidden until unlocked.
- File: `www/admin/edit-marks.php`. The raw `search` term is concatenated
  into the WHERE clause (`%$search%`) exactly like `students.php`, with
  the department value embedded as an `escapeString()`ed literal and the
  whole tail on a single line (so `--` blanks it).
- The edit path (`?id=` fetch and the `UPDATE marks`) no longer enforces a
  department guard: once the examiner unlocks the list, any listed record
  can be opened and edited, and the change persists.
- Working payload (type into the search box, or `?search=`):
  ```text
  search=%') OR 1=1 --
  ```
  Lists every student of every department (`NB-*` and `NC-*`) with
  per-row "Edit Marks" links.
- Purpose: a write/showcase stage. The read-only union shaping of this
  page differs from `students.php` (marks columns are already joined), so
  its role is unlocking records rather than dumping the schema.

### Tables a complete dump must reveal

`students`, `courses`, `marks`, `faculty`, `compliance_notes` (plus
`admins` and `flags`). `compliance_notes` contains the hint:

> Full audit log archived at /opt/northbridge/flag.txt (lab host only).

That hint is the bridge from the database dump to the filesystem final
flag.

## Anti-cheat / design constraints honoured

- The literal final flag value is **never** stored in the SQLite database
  or committed to Git (`Flag_04` was removed from the `flags` table).
- Static-secret review: `.env`, database file and flag are all generated
  at provision time; `.gitignore` excludes `*.db`, `.env` and
  `www/database/`.
- Docs in the repo (this file, `Docs/`) are outside `PORTAL_SUBDIR` and
  are never served.