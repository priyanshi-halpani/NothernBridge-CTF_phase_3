PRAGMA foreign_keys = ON;

-- ============================================================
-- Northbridge College CTF — seed data
-- Idempotent: safe to run many times (CREATE IF NOT EXISTS +
-- INSERT OR IGNORE). Existing player data is never destroyed.
-- ============================================================


CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    name TEXT NOT NULL,
    number TEXT NOT NULL,
    salary REAL NOT NULL DEFAULT 0
);


CREATE TABLE IF NOT EXISTS students (
    student_id TEXT PRIMARY KEY,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    contact_number TEXT NOT NULL,
    dob TEXT NOT NULL,
    address TEXT NOT NULL,
    department TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,

    CHECK (
        department IN (
            'Information Technology',
            'Computer Science',
            'Civil Engineering',
            'Mechanical Engineering'
        )
    )
);


CREATE TABLE IF NOT EXISTS marks (
    student_id TEXT PRIMARY KEY,

    math INTEGER NOT NULL DEFAULT 0,
    cpp INTEGER NOT NULL DEFAULT 0,
    python INTEGER NOT NULL DEFAULT 0,
    graphics INTEGER NOT NULL DEFAULT 0,

    FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE,

    CHECK (math BETWEEN 0 AND 100),
    CHECK (cpp BETWEEN 0 AND 100),
    CHECK (python BETWEEN 0 AND 100),
    CHECK (graphics BETWEEN 0 AND 100)
);


CREATE TABLE IF NOT EXISTS flags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    flag_name TEXT NOT NULL UNIQUE,
    flag_value TEXT NOT NULL,
    Description TEXT
);


-- =========================================
-- FICTIONAL ADMIN
-- =========================================

INSERT OR IGNORE INTO admins
(username, password, name, number, salary)
VALUES
(
    'helen.carter',
    '@dmin',
    'Helen Carter',
    '9457623547',
    75000
);

-- FICTIONAL STUDENTS
INSERT OR IGNORE INTO students
(student_id, first_name, last_name, contact_number, dob, address, department, email, password)
VALUES

(   'NB-AP26-1001',
    'Aarav',
    'Patel',
    '9045678991',
    '2004-01-12',
    'Ahmedabad',
    'Information Technology',
    'aaravpatel@northenbridge.lab',
    'aarav2004'
),

(   'NB-RP26-1002',
    'Riya',
    'Patel',
    '9258792358',
    '2004-03-21',
    'Vadodara',
    'Computer Science',
    'riyapatel@northenbridge.lab',
    'riya2004'
),

(   'NB-VS26-1003',
    'Vivaan',
    'Shah',
    '9344665130',
    '2003-11-08',
    'Surat',
    'Civil Engineering',
    'vivaanshah@northenbridge.lab',
    'vivaan2003'
),

(   'NB-KS26-1004',
    'Kavya',
    'Singh',
    '9004567890',
    '2004-06-17',
    'Rajkot',
    'Mechanical Engineering',
    'kavyasingh@northenbridge.lab',
    'kavya2004'
),

(   'NB-AN26-1005',
    'Arjun',
    'Nair',
    '9059993456',
    '2003-09-25',
    'Ahmedabad',
    'Information Technology',
    'arjunnair@northenbridge.lab',
    'arjun2003'
),

(   'NB-YS26-1006',
    'Yash',
    'Sharma',
    '9654321789',
    '2004-08-15',
    'Ahmedabad',
    'Information Technology',
    'yashsharma@northenbridge.lab',
    'yash2004'
),

(   'NB-RT26-9438',
    'raj',
    'tripathi',
    '9674738475',
    '2004-05-03',
    'Uttarakhand,India',
    'Mechanical Engineering',
    'rajtripathi@northenbridge.lab',
    'raj2004'
),

(   'NB-JO26-5724',
    'jack',
    'Okafor',
    '2345678490',
    '2003-11-12',
    'jamaica',
    'Civil Engineering',
    'jackokafor@northenbridge.lab',
    'jack2003'
),

(   'NB-RA26-8681',
    'riya',
    'agarwal',
    '9876512340',
    '2004-02-29',
    'jharkhand,india',
    'Information Technology',
    'riyaagarwal@northenbridge.lab',
    'riya2004'
),

(   'NB-AP26-4737',
    'Ananya',
    'pandey',
    '8967452310',
    '2002-06-03',
    'Mumbai, Maharashtra, India',
    'Computer Science',
    'ananyapandey@northenbridge.lab',
    'ananya2002'
),

(   'NB-RP26-2979',
    'Raghav',
    'patel',
    '7865324567',
    '2004-08-20',
    'Rajkot, Gujarat, India.',
    'Civil Engineering',
    'raghavpatel@northenbridge.lab',
    'raghav2004'
);


-- =========================================
-- FICTIONAL MARKS
-- =========================================

INSERT OR IGNORE INTO marks
(student_id, math, cpp, python, graphics)
VALUES

('NB-AP26-1001', 72, 81, 65, 74),
('NB-RP26-1002', 88, 79, 91, 83),
('NB-VS26-1003', 61, 70, 55, 68),
('NB-KS26-1004', 76, 84, 73, 80),
('NB-AN26-1005', 69, 77, 20, 62),
('NB-YS26-1006', 70, 85, 20, 50),
('NB-RT26-9438', 67, 54, 53, 76),
('NB-JO26-5724', 56, 34, 67, 75),
('NB-RA26-8681', 55, 67, 45, 50),
('NB-AP26-4737', 35, 36, 37, 38),
('NB-RP26-2979', 20, 78, 45, 23);


-- =========================================
-- CTF FLAGS (in-application progression)
-- The final flag does NOT live here — it is placed on the
-- filesystem by scripts/provision.sh.
-- =========================================

INSERT OR IGNORE INTO flags
(flag_name, flag_value, description)
VALUES
('Flag_01','Flag{Discovered_hidden_route}','Flag for discovering unlinked admin directory with login portal'),
('Flag_02','Flag{Found_Admin_Credentials}','Flag for finding admin credentials on web file'),
('Flag_03','Flag{Explored_Limited_Records}','Third flag revealed while exploring the limited student-record view'),
('Flag_04','Flag{Change_Failure_into_Your_Success}','Flag for changing student marks using SQL injection');