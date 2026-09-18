# Northenbridge College CTF — Challenge Flow

## Overview

The Northenbridge College CTF is structured as a progressive investigation through the fictional college portal. The player begins as a normal student and gradually discovers weaknesses in the portal.

The intended progression is:

```
College Website
      │
      ▼
Student Registration
      │
      ▼
Student Login
      │
      ▼
Student Marks
      │
      ▼
Reassessment Clue
      │
      ▼
Hidden /admin Portal
      │
      ▼
Administrator Credentials
      │
      ▼
Admin Dashboard
      │
      ▼
Limited Student Records
      │
      ▼
Unsafe Search
      │
      ▼
Player's Student Record
      │
      ▼
Marks Modification
      │
      ▼
Passing Result
```

The challenge is designed to teach one concept at a time.  
</br>

## Player Starting Point

1. The player starts at the public Northenbridge College homepage.

2. The website presents itself as a normal fictional college portal.

3. The primary student functionality includes:

    - Student registration
    - Student login
    - Student profile
    - Examination marks

The administration portal is deliberately absent from the normal student navigation.  
</br>  

## Stage 1 — Discover the Administration Portal

**Objective**

Discover the hidden administration area.

**Starting point**

- The player begins with the normal student portal.
- Then player registers a student account and receives their generated credentials.
- After logging in, the player can access their profile and examination results.
- The newly registered student's seeded marks include a failing Python result.
- The marks page provides a Reassessment option.
- Selecting it displays an application error-style message indicating that the administrative service at:
    ```
    /admin
    ```
    cannot currently be contacted.

- This provides the intended clue to the hidden administration route.  
</br>  

**Discovery**

The player follows the clue and visits:
```
/admin
```
The administration login page is reached.


**Flag**

The first flag is displayed on the administration login page.


**Lesson**

An unlinked or hidden application route is not a security control.

A route that is not visible in normal navigation can still be discovered.  
</br>  

## Stage 2 — Discover the Exposed Administrator Credentials

**Objective**

- Find the fictional administrator credentials required to continue.
- After reaching the administrator portal, the player needs administrator credentials.
- The challenge intentionally places a clue in a web-accessible file.
- The administration directory contains a robots.txt file.
- The file includes information that should not be treated as a real secret but is intentionally exposed for the CTF.


**Discovery**

- The player discovers the file and observes that it contains the fictional administrator login information.
- The player can then return to the administrator login page and authenticate.


**Flag**

- The second flag is available as part of this stage.


**Lesson**

robots.txt is not an access-control mechanism and should not contain secrets.

Sensitive credentials should never be stored in publicly accessible web files.  
</br>  
## Stage 3 — Explore the Limited Student Records

**Objective**

- Understand the normal limitations of the administrator's student-record view.
- After successful administrator authentication, the player reaches the administration dashboard.
- The dashboard provides a limited overview of student information and an option to edit student marks.
- The marks management page provides a student search interface.
- Under normal use, the search results are restricted to a subset of fictional student records.
- The player can inspect the available records and observe that not every student record is visible through the normal search behavior.

**Intended observation**

The important point at this stage is not immediately exploiting the application.

The player should first understand:

- What records are normally visible
- What information is displayed
- How student searches behave
- That the record set is intentionally limited

**Flag**

- The third flag is associated with the limited-record exploration stage.


**Lesson**

Application-level restrictions must actually enforce the intended data boundary.

A user interface showing only a subset of records does not necessarily mean the underlying query is securely restricted.  
</br>  
## Stage 4 — Investigate the Unsafe Search

**Objective**

- Identify the weakness in the administrator student-search functionality and use it to locate the player's student record.
- The administrator marks page contains a search field.
- The normal search behavior restricts displayed records to student IDs belonging to the seeded administrator-visible group.
- The implementation intentionally constructs part of this search query using the supplied search input without proper parameterization.

- This creates the SQL injection learning stage.

**Intended progression**

The player should:

- Observe the normal search behavior.
- Identify that the search input affects the database query.
- Test how the search behaves with unexpected input.
- Determine that the intended record restriction can be bypassed.
- Locate the student account created during registration.
- Obtain the player's generated student ID.
- Open the corresponding marks record.
- Modify the player's marks.

The vulnerability is confined to the fictional SQLite database used by the CTF.  
</br>  
## Player Record Discovery

A newly registered student receives an ID in the following general format:

```
NC-<initials><year>-<random-suffix>
```
For example:
```
NC-AB26-1234
```

The exact value depends on the registration information and generated suffix.

The student's initial marks include a deliberately low Python score. Therefore, once the player's record is discovered through the vulnerable administrator search, the player can select the record for editing.  
</br>  
## Marks Modification

The administrator marks interface allows examination marks to be updated. Therfore player changes the relevant failing mark so that the student's overall result becomes a pass. The application validates the submitted mark range and stores the updated value in SQLite.

The goal is not to modify the host or obtain operating-system access. The intended outcome is simply to demonstrate that the application-level weakness allows the player to alter their fictional academic record.  
</br>  
## Final Stage — Turn Failure into Success

After modifying the marks, the player returns to the student portal and then refresh profile/marks pages, which reflects the updated result.

The student's status changes from:

**FAILED**

to:

**PASSED**

Along with change in result state page also reveals fourth and final flag.

**Lesson**

Unsafe database query construction can allow unintended access to application data and functionality.

The remediation is to use parameterized SQL queries for user-controlled input and enforce authorization independently of the search interface.  
</br>  
## Complete Challenge Chain

```
┌──────────────────────────────────────┐
│ 1. Visit Northenbridge College       │
│    Website                           │
└──────────────────┬───────────────────┘
                   ▼                    
┌──────────────────────────────────────┐
│ 2. Register Student Account          │
│    Receive generated credentials     │
└──────────────────┬───────────────────┘
                   ▼                    
┌──────────────────────────────────────┐
│ 3. Login and Open Marks              │
│    Observe failing result            │
└──────────────────┬───────────────────┘
                   ▼                    
┌──────────────────────────────────────┐
│ 4. Click Reassessment                │
│    Discover /admin clue              │
└──────────────────┬───────────────────┘
                   ▼                    
┌──────────────────────────────────────┐
│ 5. Visit /admin web directory        │
│    Discover Admin Login Portal       │
└──────────────────┬───────────────────┘
                   │                    
              [ FLAG 1 ]                
                   │                    
                   ▼                    
┌──────────────────────────────────────┐
│ 5. Explore Admin Portal              │
│    Discover exposed web file         │
└──────────────────┬───────────────────┘
                   ▼                    
┌──────────────────────────────────────┐
│ 6. Obtain Fictional Admin Login and  │
│    Authenticate                      │
└──────────────────┬───────────────────┘
                   ▼                    
┌──────────────────────────────────────┐
│ 7. Explore Admin Dashboard           │
│    Inspect limited records           │
└──────────────────┬───────────────────┘
                   │                    
               [ FLAG 2 ]               
                   │                    
                   ▼                    
┌──────────────────────────────────────┐
│ 8. Inspect limited records           │
└──────────────────┬───────────────────┘
                   │                    
              [ FLAG 3 ]                
                   │                    
                   ▼                    
┌──────────────────────────────────────┐
│ 9. Investigate Student Search        │
│    Identify unsafe query handling    │
└──────────────────┬───────────────────┘
                   ▼                    
┌──────────────────────────────────────┐
│ 10.Locate Player's NC-* Record       │
│    Through intended SQLi stage       │
└──────────────────┬───────────────────┘
                   ▼                    
                                        
┌──────────────────────────────────────┐
│ 11. Edit Player's Marks              │
│     Raise failing result             │
└──────────────────┬───────────────────┘
                   ▼                    
                                        
┌──────────────────────────────────────┐
│ 12. Return to Student Marks          │
│     Result becomes PASSED            │
└──────────────────┬───────────────────┘
                   │                    
             [ FLAG 4 ]                 
                   │                    
                   ▼                    
             CTF COMPLETED              
```
</br>  

## 11. Flag Progression
Flag | Stage | Discovery
-|-|-
Flag 1 | Hidden administration route | Admin login portal
Flag 2 | After successful admin login | Admin Dasboard
Flag 3 | Limited records | Exploration of administrator record view
Flag 4 | SQL injection / marks modification |	Player locates and modifies their record
</br>  

## Intended Learning Outcomes

By completing the challenge, a beginner should understand:

**Hidden routes**

- A URL being absent from the navigation does not make it inaccessible.

**Sensitive information exposure**

- Files served by a web server can expose information if administrators place secrets inside them.

**Authorization and data boundaries**

- A restricted-looking interface is not sufficient protection if the backend query does not correctly enforce the restriction.

**SQL injection**

- Building SQL statements by directly concatenating user-controlled input can allow the application's intended query logic to be altered.

**Secure remediation**

Students should be able to identify appropriate defensive measures, including:

- Parameterized SQL queries
- Proper authorization checks
- Avoiding secrets in web-accessible files
- Restricting sensitive routes
- Validating access to individual student records
- Separating user-facing functionality from administrative functionality  
</br>  

## Intended Scope

The challenge is deliberately limited to the fictional application and its seeded SQLite data.

The intended solution does not require:

- Host operating-system exploitation
- Vagrant exploitation
- VirtualBox exploitation
- SSH attacks
- Access to the host computer
- Internet-facing targets
- Real credentials
- Real student information
- Persistence
- Lateral movement

All data used by the challenge is fictional and intended only for the lab.  
</br>  
## Expected Completion

A player has completed the CTF when they have:

1. Discovered the hidden administration portal.
    - Obtained the first flag.

2. Discovered the intentionally exposed fictional administrator credentials.
    - Obtained the second flag.

3. Explored the limited administrator records.
    - Obtained the third flag.

4. Identified the unsafe student-search behavior.
5. Located their own registered student record.
6. Modified the failing mark.
7. Returned to the student portal and achieved the passing result.
    - Obtained the final flag.

The challenge is complete once all four flags have been recovered and the player can explain the basic security issue demonstrated by each stage.