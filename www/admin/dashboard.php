<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../db.php';

/*
|--------------------------------------------------------------------------
| Require admin login
|--------------------------------------------------------------------------
*/
if ( !isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Fetch Flag 3 from database
$flag3 = null;

$flagStmt = $db->prepare("
    SELECT flag_value
    FROM flags
    WHERE flag_name = :flag_name
    LIMIT 1
");

$flagStmt->bindValue(
    ':flag_name',
    'Flag_03',
    SQLITE3_TEXT
);

$flagResult = $flagStmt->execute();

if ($flagResult) {
    $flagRow = $flagResult->fetchArray(SQLITE3_ASSOC);

    if ($flagRow) {
        $flag3 = $flagRow['flag_value'];
    }
}

/*
|--------------------------------------------------------------------------
| Fetch administrator information
|--------------------------------------------------------------------------
*/
$adminId = $_SESSION['admin_id'];

$stmt = $db->prepare("
    SELECT
        id,
        username,
        name,
        number,
        salary,
        department
    FROM admins
    WHERE id = :id
    LIMIT 1
");

$stmt->bindValue(':id', $adminId, SQLITE3_INTEGER);

$result = $stmt->execute();
$admin = $result->fetchArray(SQLITE3_ASSOC);

if (!$admin) {
    session_destroy();
    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch only a limited number of students from the clerk's department
|--------------------------------------------------------------------------
|
| Clerk view is restricted: only the admin's own department is shown,
| seeded NB-* records only (fresh NC-* registrations stay hidden), and
| the dashboard exposes only 5 records.
|
*/
$studentStmt = $db->prepare("
    SELECT
        student_id,
        first_name,
        last_name,
        department
    FROM students
    WHERE department = :department
      AND student_id LIKE 'NB-%'
    ORDER BY student_id
    LIMIT 5
");

$studentStmt->bindValue(':department', $admin['department'], SQLITE3_TEXT);

$studentResult = $studentStmt->execute();

$students = [];

while ($row = $studentResult->fetchArray(SQLITE3_ASSOC)) {
    $students[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Northenbridge College</title>

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

        nav {
            background: var(--hedge-dark);
            color: white;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: Georgia, serif;
            font-size: 23px;
            font-weight: bold;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-right span {
            color: #d8dfda;
            font-size: 13px;
        }

        .logout {
            color: white;
            text-decoration: none;
            padding: 8px 14px;
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 6px;
            font-size: 13px;
        }

        .logout:hover {
            background: rgba(255,255,255,.08);
        }

        .container {
            width: min(1150px, 92%);
            margin: 45px auto;
        }

        .heading {
            margin-bottom: 30px;
        }

        .heading h1 {
            font-family: Georgia, serif;
            font-size: 36px;
            margin-bottom: 8px;
        }

        .heading p {
            color: var(--muted);
        }

        /*
        |--------------------------------------------------------------------------
        | Admin cards
        |--------------------------------------------------------------------------
        */

        .admin-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 35px;
        }

        .admin-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 11px;
            padding: 23px;
        }

        .admin-card .label {
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 9px;
        }

        .admin-card .value {
            font-family: Georgia, serif;
            font-size: 22px;
            font-weight: bold;
        }

        /*
        |--------------------------------------------------------------------------
        | Student overview
        |--------------------------------------------------------------------------
        */

        .students-panel {
            background: white;
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
        }

        .panel-header {
            padding: 25px;
            border-bottom: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .panel-header h2 {
            font-family: Georgia, serif;
            margin-bottom: 6px;
        }

        .panel-header p {
            color: var(--muted);
            font-size: 13px;
        }

        .manage-button {
            background: var(--brass);
            color: white;
            text-decoration: none;
            padding: 11px 17px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        .manage-button:hover {
            opacity: .9;
        }

        .panel-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th,
        td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--line);
            text-align: left;
        }

        th {
            background: #eeeae1;
            font-family: Georgia, serif;
            font-size: 14px;
        }

        td {
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .edit-button {
            background: var(--hedge);
            color: white;
            text-decoration: none;
            padding: 8px 13px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
        }

        .edit-button:hover {
            background: var(--hedge-dark);
        }

        .limited-note {
            padding: 17px 20px;
            color: var(--muted);
            background: #faf8f3;
            font-size: 12px;
        }

        footer {
            text-align: center;
            padding: 35px;
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 800px) {

            .admin-cards {
                grid-template-columns: 1fr;
            }

            .panel-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .heading h1 {
                font-size: 30px;
            }
        }
        
        .flag-box {
    margin-top: 20px;
    padding: 16px 18px;
    background: #f6f3ec;
    border: 1px solid var(--brass);
    border-radius: 8px;
}

.flag-label {
    color: var(--brass);
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.flag-value {
    color: var(--hedge-dark);
    font-family: monospace;
    font-size: 14px;
    word-break: break-word;
}
    </style>

</head>

<body>


<?php require_once __DIR__ . '/../includes/header.php'; ?>


<main class="container">

    <section class="heading">

        <h1>
            Administration Dashboard
        </h1>

        <p>
            Welcome to the Northenbridge College administration portal.
        </p>

    </section>


    <!-- ADMIN INFORMATION -->

    <section class="admin-cards">

        <div class="admin-card">

            <div class="label">
                Administrator
            </div>

            <div class="value">
                <?= htmlspecialchars($admin['name']) ?>
            </div>

        </div>


        <div class="admin-card">

            <div class="label">
                Administrator Number
            </div>

            <div class="value">
                <?= htmlspecialchars($admin['number']) ?>
            </div>

        </div>


        <div class="admin-card">

            <div class="label">
                Salary
            </div>

            <div class="value">
                ₹<?= htmlspecialchars($admin['salary']) ?>
            </div>

        </div>

        <div class="admin-card">

            <div class="label">
                Department
            </div>

            <div class="value">
                <?= htmlspecialchars($admin['department']) ?>
            </div>

        </div>

    </section>


    <!-- STUDENT OVERVIEW -->

    <section class="students-panel">

        <div class="panel-header">

            <div>

                <h2>
                    Student Records
                </h2>

                <p>
                    A limited overview of your department's enrolled students.
                </p>

            </div>

            <div class="panel-actions">

                <a
                    href="students.php"
                    class="manage-button"
                >
                    Student Records
                </a>

                <a
                    href="edit-marks.php"
                    class="manage-button"
                >
                    Edit Student Marks
                </a>

            </div>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Action</th>
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

                            <a
                                href="edit-marks.php?id=<?= urlencode($student['student_id']) ?>"
                                class="edit-button"
                            >
                                Edit Marks
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>


        <div class="limited-note">
            Clerk view is deliberately restricted: only records from your
            own department are listed here, and only a limited number are
            displayed. Complete records require registrar approval.
        </div>
                    
    </section>
    <?php if ($flag3 !== null): ?>
        <div class="flag-box">
            <div class="flag-label">FLAG 03</div>
            <div class="flag-value">
                <?= htmlspecialchars($flag3) ?>
            </div>
        </div>
    <?php endif; ?>
</main>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>