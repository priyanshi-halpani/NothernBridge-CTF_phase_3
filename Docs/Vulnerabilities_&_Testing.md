# Vulnerabilities and Testing

The vulnerabilities in this project are intentional and are limited to the fictional Northenbridge College application.

The tests below verify that the intended CTF path works without relying on unrelated system weaknesses.

## 1. Hidden Admin Route

### Location

```text
/admin/
```

The admin portal is not included in the normal student navigation.

The student marks page contains a reassessment error referring to the administrative service at `/admin`.

### Expected Result

A player following the clue can reach the admin login page and obtain the first flag.

---

## 2. Exposed Credentials

### Location

```text
/admin/robots.txt
```

The file contains fictional administrator credentials used by the challenge.

### Expected Result

A player who discovers the file can use the credentials to log into the admin portal.

No real credentials are used by the project.

---

## 3. Limited Student Records

### Location

```text
/admin/dashboard.php
/admin/edit-marks.php
```

The admin dashboard only displays a limited number of student records.

The marks management page also applies a restriction to the normal search results.

The student created during the CTF is not normally visible in this list.

### Expected Result

The player can identify that the visible records are incomplete and continue investigating the search functionality.

---
## 4. Intended Student Search Restriction

### Location
> 🔍 search bar

The student marks management page intentionally restricts normal search results to only fetch records whose student ID begins with `NB-`.

This means that when an administrator performs a normal search using either student full name or exact student ID (eg: NC-AP26-0001), only the pre-existing `NB-*` student records are returned. A newly registered player receives an `NC-*` student ID and therefore does not appear in the normal search results.

This restriction is part of the CTF design rather than a database limitation.

The search query is intentionally vulnerable to SQL injection. By manipulating the search input, a player can bypass the `NB-*` restriction and cause additional records, including the player's `NC-*` record, to appear.

The intended progression is:

```text
Normal Search
     │
     ▼
Only NB-* records visible
     │
     ▼
Player's NC-* record is missing
     │
     ▼
Investigate the search functionality
     │
     ▼
SQL Injection
     │
     ▼
NB-* restriction bypassed
     │
     ▼
NC-* player record discovered
```
---
## 5. SQL Injection

### Location

```text
/admin/edit-marks.php
```

The student search query intentionally uses the supplied search value directly when constructing the SQL query.

The vulnerable search is the main database-related weakness in the CTF.

The actual marks update operation uses a prepared statement; the intended weakness is the discovery/search functionality.

### Expected Result

A player can manipulate the search input to bypass the normal student ID restriction and discover additional fictional records.

The player's own student ID can then be used with the marks editing interface.

---

## 6. Marks Manipulation

Once the player's student record has been discovered, the marks editing interface allows the record to be modified.

The player's initial Python mark is intentionally low enough to produce a failed result.

### Expected Result

After increasing the Python mark to a passing value, the student's result changes to **PASS** and the final flag becomes available.

---

# Testing

## Basic Application Test

* [ ] Homepage loads successfully.
* [ ] Student registration works.
* [ ] Credential file downloads successfully.
* [ ] Student can log in.
* [ ] Profile page loads.
* [ ] Student marks are displayed.
* [ ] Initial result is failed.
* [ ] Reassessment message displays the `/admin` clue.
* [ ] Student logout works.

## Admin Test

* [ ] `/admin` loads the admin login page.
* [ ] Admin credentials from the intended challenge file work.
* [ ] Admin dashboard loads after login.
* [ ] Limited student records are displayed.
* [ ] Marks management page loads.
* [ ] Existing student marks can be edited.
* [ ] Updated marks are saved correctly.
* [ ] Admin logout works.

## CTF Path Test

* [ ] Flag 1 is available from the hidden admin route.
* [ ] Flag 2 is available after discovering the exposed credentials.
* [ ] Flag 3 is available during the limited-record stage.
* [ ] SQL injection allows the intended additional records to be discovered.
* [ ] The player's student record can be identified.
* [ ] The player's marks can be changed.
* [ ] The final result changes from FAIL to PASS.
* [ ] Flag 4 is displayed after completing the final stage.

## Network Test

With bridged networking enabled:

* [ ] VM receives an address on the local network.
* [ ] Website is reachable from the host using the VM IP.
* [ ] Website is reachable from a second device on the same network.
* [ ] Multiple devices can load the website at the same time.
* [ ] Student registration works while another player is using the site.
* [ ] Admin pages remain accessible during normal player activity.

The CTF should be tested with several devices before the actual session, especially because all players use the same application and database.
