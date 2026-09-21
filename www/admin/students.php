<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../db.php';

/*
|--------------------------------------------------------------------------
| Require admin login
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Clerk view restriction
|--------------------------------------------------------------------------
|
| This limited admin view only exposes records belonging to the signed
| in administrator's OWN department. Complete records require registrar
| approval — the clerk view is intentionally restricted.
|
*/
$adminResult = $db->prepare("
    SELECT name, department
    FROM admins
    WHERE id = :id
    LIMIT 1
");

$adminResult->bindValue(':id', $_SESSION['admin_id'], SQLITE3_INTEGER);

$adminRow = $adminResult->execute()->fetchArray(SQLITE3_ASSOC);

if (!$adminRow) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$admin_name = $adminRow['name'] ?? '';
$admin_dept = $adminRow['department'] ?? 'Information Technology';

// Fetch Flag 2 from the database — revealed inside this restricted view.
$flag2 = null;

$flagStmt = $db->prepare("
    SELECT flag_value
    FROM flags
    WHERE flag_name = :flag_name
    LIMIT 1
");

$flagStmt->bindValue(
    ':flag_name',
    'Flag_02',
    SQLITE3_TEXT
);

$flagResult = $flagStmt->execute();

if ($flagResult) {
    $flagRow = $flagResult->fetchArray(SQLITE3_ASSOC);

    if ($flagRow) {
        $flag2 = $flagRow['flag_value'];
    }
}

/*
|--------------------------------------------------------------------------
| LIMITED STUDENT-RECORD SEARCH (?q=...)
|--------------------------------------------------------------------------
|
| Normal behavior:
|     Only the clerk's own department is searchable, and only a limited
|     view is returned: name, department and enrolled-course count.
|
| CTF behavior:
|     The $q value is deliberately concatenated directly into the SQL
|     WHERE clause (this is the documented injection field for the
|     database-exfiltration stage — see Docs/ctf-design.md). The
|     department constraint is embedded as a safe literal (source: the
|     signed-in admin row, escaped before use), so a payload that
|     escapes the LIKE expression also escapes the clerk-view limit.
|
| The WHERE clause keeps the whole tail on a single line on purpose:
| a `--` payload therefore comments out the department constraint, the
| ORDER BY and the LIMIT. There is a hard query limit and the page
| reports "no records" instead of leaking SQL errors.
|--------------------------------------------------------------------------
*/

$q = trim((string)($_GET['q'] ?? ''));

$students = [];
$message = '';
$message_type = '';

$deptSafe = $db->escapeString($admin_dept);

$query = "
    SELECT
        s.student_id,
        s.first_name,
        s.last_name,
        s.department,
        (
            CASE WHEN m.math     > 0 THEN 1 ELSE 0 END +
            CASE WHEN m.cpp      > 0 THEN 1 ELSE 0 END +
            CASE WHEN m.python   > 0 THEN 1 ELSE 0 END +
            CASE WHEN m.graphics > 0 THEN 1 ELSE 0 END
        ) AS enrolled_courses
    FROM students s
    LEFT JOIN marks m
        ON s.student_id = m.student_id
    WHERE
        ( s.first_name LIKE '%$q%' OR s.last_name LIKE '%$q%' OR s.student_id LIKE '%$q%' OR s.department LIKE '%$q%' ) AND s.department = '$deptSafe' ORDER BY s.student_id LIMIT 25
";

try {

    $stmtResult = $db->query($query);

    if ($stmtResult) {
        while ($row = $stmtResult->fetchArray(SQLITE3_ASSOC)) {
            $students[] = $row;
        }
    }

} catch (Throwable $e) {

    $students = [];
    $message = 'No records match the search criteria.';
    $message_type = 'error';
}

if (count($students) === 0) {
    $message = 'No records match the search criteria.';
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Records | Northenbridge College</title>

    <style>
        :root {
            --ink: #1c2a24;
            --parchment: #f6f3ec;
            --hedge: #2f4a3d;
            --hedge-dark: #203329;
            --brass: #a97c33;
            --line: #d9d2c2;
            --muted: #5b6b62;
            --white: #ffffff;
            --red: #b42318;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--parchment);
            color: var(--ink);
            font-family: Arial, sans-serif;
        }

        main {
            max-width: 980px;
            margin: 34px auto;
            padding: 0 4%;
        }

        .heading {
            margin-bottom: 26px;
        }

        .heading h1 {
            font-family: Georgia, serif;
            font-size: 30px;
            margin-bottom: 8px;
        }

        .heading p {
            color: var(--muted);
            font-size: 14px;
        }

        .panel {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        label {
            font-size: 13px;
            font-weight: bold;
            display: block;
            margin-bottom: 6px;
        }

        input[type="text"] {
            flex: 1;
            min-width: 220px;
            padding: 11px 13px;
            border: 1px solid var(--line);
            border-radius: 7px;
            font-size: 14px;
        }

        button {
            border: none;
            border-radius: 7px;
            padding: 11px 18px;
            background: var(--hedge-dark);
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: var(--hedge);
        }

        .scope-note {
            background: #eef2ee;
            border-left: 4px solid var(--hedge);
            padding: 10px 14px;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 18px;
            line-height: 1.5;
        }

        .message {
            padding: 11px 14px;
            border-radius: 7px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .message.error {
            background: #fdecec;
            color: var(--red);
            border: 1px solid #f2c5c5;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th, td {
            text-align: left;
            padding: 11px 12px;
            border-bottom: 1px solid var(--line);
        }

        th {
            background: #f1ece1;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--muted);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .back-link {
            display: inline-block;
            margin-top: 14px;
            color: var(--hedge);
            font-size: 13px;
            text-decoration: none;
            font-weight: bold;
        }

        .flag-box {
            margin-top: 6px;
            padding: 14px 16px;
            background: #f6f3ec;
            border: 1px solid var(--brass);
            border-radius: 8px;
        }

        .flag-label {
            color: var(--brass);
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .flag-value {
            color: var(--hedge-dark);
            font-family: monospace;
            font-size: 14px;
            word-break: break-word;
        }

        .restriction-note {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--line);
        }
    </style>

</head>

<body>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<main>

    <section class="heading">

        <h1>
            Student Records
        </h1>

        <p>
            Limited clerk view — <?= htmlspecialchars($admin_name) ?> (<?= htmlspecialchars($admin_dept) ?>).
        </p>

    </section>


    <section class="panel">

        <form class="search-form" method="GET">

            <div style="flex:1;">
                <label for="q">
                    Search student records
                </label>
                <input
                    type="text"
                    id="q"
                    name="q"
                    value="<?= htmlspecialchars($q) ?>"
                    placeholder="Search by name, ID or department..."
                >
            </div>

            <button type="submit">
                Search
            </button>

        </form>

        <div class="scope-note">
            Clerk view is restricted to <?= htmlspecialchars($admin_dept) ?>.
            Only name, department and enrolled-course count are exposed here.
        </div>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Enrolled Courses</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($students as $student): ?>

                    <tr>
                        <td>
                            <?= htmlspecialchars($student['student_id']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $student['first_name'] . ' ' .
                                $student['last_name']
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($student['department']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($student['enrolled_courses']) ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div class="restriction-note">
            Complete records require registrar approval — clerk view is
            intentionally restricted.
        </div>

        <?php if ($flag2 !== null): ?>
            <div class="flag-box">
                <div class="flag-label">FLAG 02</div>
                <div class="flag-value">
                    <?= htmlspecialchars($flag2) ?>
                </div>
            </div>
        <?php endif; ?>

        <a class="back-link" href="dashboard.php">
            &larr; Back to dashboard
        </a>

    </section>

</main>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>