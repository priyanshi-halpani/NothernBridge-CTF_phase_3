<?php
// Records editor

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../db.php';

/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$message = '';
$message_type = '';

$search = trim($_GET['search'] ?? '');
$edit_id = $_GET['id'] ?? '';

/*
|--------------------------------------------------------------------------
| EDIT MARKS
|--------------------------------------------------------------------------
|
| This part intentionally allows the CTF player to submit a student_id
| discovered through the vulnerable search functionality.
|
| The normal UI only exposes the first 5 students.
|
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_marks'])) {

    $student_id = $_POST['student_id'] ?? '';

    $math     = isset($_POST['math']) ? (int)$_POST['math'] : 0;
    $cpp      = isset($_POST['cpp']) ? (int)$_POST['cpp'] : 0;
    $python   = isset($_POST['python']) ? (int)$_POST['python'] : 0;
    $graphics = isset($_POST['graphics']) ? (int)$_POST['graphics'] : 0;

    // Keep marks within the normal 0-100 range.
    $math     = max(0, min(100, $math));
    $cpp      = max(0, min(100, $cpp));
    $python   = max(0, min(100, $python));
    $graphics = max(0, min(100, $graphics));

    if ($student_id === '') {

        $message = 'Invalid student record.';
        $message_type = 'error';

    } else {

        /*
         * The update itself is intentionally available through the
         * Edit Marks interface. The CTF restriction is on DISCOVERY:
         * only the first five records are normally exposed.
         */

        $stmt = $db->prepare(
            'UPDATE marks
             SET math = :math,
                 cpp = :cpp,
                 python = :python,
                 graphics = :graphics
             WHERE student_id = :student_id'
        );

        $stmt->bindValue(':math', $math, SQLITE3_INTEGER);
        $stmt->bindValue(':cpp', $cpp, SQLITE3_INTEGER);
        $stmt->bindValue(':python', $python, SQLITE3_INTEGER);
        $stmt->bindValue(':graphics', $graphics, SQLITE3_INTEGER);
        $stmt->bindValue(':student_id', $student_id, SQLITE3_TEXT);

        if ($stmt->execute()) {

            $message = 'Marks updated successfully.';
            $message_type = 'success';

        } else {

            $message = 'Unable to update marks.';
            $message_type = 'error';
        }
    }
}


/*
|--------------------------------------------------------------------------
| FETCH EDITED STUDENT
|--------------------------------------------------------------------------
|
| If ?id=... is supplied, show that student's edit form.
|
*/

$selected_student = null;

if ($edit_id !== '') {

    $stmt = $db->prepare(
        'SELECT
            s.student_id,
            s.first_name,
            s.last_name,
            s.department,
            m.math,
            m.cpp,
            m.python,
            m.graphics
         FROM students s
         LEFT JOIN marks m
            ON s.student_id = m.student_id
         WHERE s.student_id = :student_id
         LIMIT 1'
    );

    $stmt->bindValue(':student_id', $edit_id, SQLITE3_TEXT);

    $result = $stmt->execute();

    if ($result) {
        $selected_student = $result->fetchArray(SQLITE3_ASSOC);
    }

    if (!$selected_student) {
        $message = 'Student not found.';
        $message_type = 'error';
    }
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
 * |--------------------------------------------------------------------------
 * | STUDENT SEARCH
 * |--------------------------------------------------------------------------
 *
 * Normal behavior:
 *     Only seeded NB-* student records are searchable.
 *
 * CTF behavior:
 *     The search query intentionally concatenates user input directly
 *     into SQL. A SQL injection payload can manipulate the WHERE clause
 *     and bypass the NB-* restriction.
 *
 * Normal player registrations use NC-* student IDs, so:
 *
 *     NB-*  -> seeded records -> searchable normally
 *     NC-*  -> registered CTF players -> hidden from normal search
 *
 * |--------------------------------------------------------------------------
 */

$students = [];

if ($search === '') {

    // Normal/default view.
    // Only seeded NB records are visible and only five are shown.

    $query = "
        SELECT
            s.student_id,
            s.first_name,
            s.last_name,
            s.department,
            m.math,
            m.cpp,
            m.python,
            m.graphics
        FROM students s
        LEFT JOIN marks m
            ON s.student_id = m.student_id
        WHERE s.student_id LIKE 'NB-%'
        ORDER BY s.student_id
        LIMIT 5
    ";

    $result = $db->query($query);

    if ($result) {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $students[] = $row;
        }
    }

} else {

    /*
     * INTENTIONAL CTF SQL INJECTION
     *
     * Normal searches are restricted to NB-* records.
     * The search value is deliberately concatenated into SQL.
     * There is no PHP-side filtering of returned rows.
     */

    $query = "
        SELECT
            s.student_id,
            s.first_name,
            s.last_name,
            s.department,
            m.math,
            m.cpp,
            m.python,
            m.graphics
        FROM students s
        LEFT JOIN marks m
            ON s.student_id = m.student_id
        WHERE
            (
                s.first_name LIKE '%$search%'
                OR s.last_name LIKE '%$search%'
                OR s.student_id LIKE '%$search%'
                OR s.department LIKE '%$search%'
            )
            AND s.student_id LIKE 'NB-%'
        ORDER BY s.student_id
    ";

    try {

        $result = $db->query($query);

        if ($result) {
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $students[] = $row;
            }
        }

    } catch (Throwable $e) {

        $students = [];

        $message = 'Student not found.';
        $message_type = 'error';
    }

    if (count($students) === 0) {
        $message = 'Student not found.';
        $message_type = 'error';
    }
}
/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Marks | Northenbridge College</title>

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
            --danger: #9b2c2c;
            --success: #28734b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--parchment);
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
        }

        header {
            background: var(--hedge-dark);
            color: white;
            border-bottom: 4px solid var(--brass);
        }

        .nav {
            max-width: 1200px;
            margin: auto;
            padding: 18px 24px;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            font-family: Georgia, serif;
            font-size: 22px;
            font-weight: bold;
        }

        .admin-label {
            font-size: 13px;
            opacity: .75;
        }

        .logout {
            color: white;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,.35);
            padding: 8px 14px;
            border-radius: 6px;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 24px;
        }

        h1,
        h2 {
            font-family: Georgia, "Times New Roman", serif;
        }

        h1 {
            margin-bottom: 8px;
        }

        .subtitle {
            color: var(--muted);
            margin-top: 0;
        }

        .panel {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 24px;
            margin-top: 24px;
            box-shadow: 0 8px 25px rgba(28, 42, 36, .05);
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-form input {
            flex: 1;
            min-width: 0;

            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 6px;

            font-size: 15px;
        }

        button {
            border: none;
            border-radius: 6px;
            padding: 11px 17px;
            cursor: pointer;
            font-weight: bold;
        }

        .search-btn {
            background: var(--hedge);
            color: white;
        }

        .edit-btn {
            display: inline-block;
            background: var(--brass);
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th,
        td {
            text-align: left;
            padding: 13px 10px;
            border-bottom: 1px solid var(--line);
        }

        th {
            background: #f0ece2;
            font-family: Georgia, serif;
        }

        td {
            font-size: 14px;
        }

        .marks {
            text-align: center;
        }

        .notice {
            margin-top: 20px;
            padding: 13px 16px;
            border-radius: 6px;
            font-size: 14px;
        }

        .notice.success {
            background: #e5f3ea;
            color: var(--success);
            border: 1px solid #b9ddc6;
        }

        .notice.error {
            background: #fae8e8;
            color: var(--danger);
            border: 1px solid #e5bcbc;
        }

        .edit-panel {
            border-left: 5px solid var(--brass);
        }

        .student-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .student-id {
            color: var(--muted);
            font-size: 14px;
        }

        .marks-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        .field input {
            width: 100%;
            padding: 11px;

            border: 1px solid var(--line);
            border-radius: 6px;

            font-size: 15px;
        }

        .update-btn {
            margin-top: 20px;
            background: var(--hedge);
            color: white;
        }

        .back {
            display: inline-block;
            margin-top: 15px;
            color: var(--hedge);
            text-decoration: none;
        }

        .empty {
            padding: 25px;
            text-align: center;
            color: var(--muted);
        }

        .search-note {
            color: var(--muted);
            font-size: 13px;
            margin-top: 10px;
        }

        @media (max-width: 800px) {

            .marks-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 500px) {

            .nav {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-form {
                flex-direction: column;
            }

            .marks-grid {
                grid-template-columns: 1fr;
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

    <h1>Edit Student Marks</h1>

    <p class="subtitle">
        Manage examination marks for student records.
    </p>


    <?php if ($message): ?>

        <div class="notice <?= e($message_type) ?>">
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <!-- SEARCH -->

    <section class="panel">

        <h2>Find Student</h2>

        <form
            method="get"
            action="edit-marks.php"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                value="<?= e($search) ?>"
                placeholder="Search student..."
                autocomplete="off"
            >

            <button
                type="submit"
                class="search-btn"
            >
                Search
            </button>

        </form>

        <p class="search-note">
            Search the student records available to the administration portal.
        </p>

    </section>


    <!-- EDIT FORM -->

    <?php if ($selected_student): ?>

        <section class="panel edit-panel">

            <div class="student-heading">

                <div>

                    <h2>
                        Edit Marks
                    </h2>

                    <strong>
                        <?= e(
                            $selected_student['first_name']
                            . ' '
                            . $selected_student['last_name']
                        ) ?>
                    </strong>

                    <div class="student-id">
                        Student ID:
                        <?= e($selected_student['student_id']) ?>
                    </div>

                    <div class="student-id">
                        Department:
                        <?= e($selected_student['department']) ?>
                    </div>

                </div>

            </div>


            <form method="post">

                <input
                    type="hidden"
                    name="student_id"
                    value="<?= e($selected_student['student_id']) ?>"
                >

                <div class="marks-grid">

                    <div class="field">

                        <label for="math">
                            Math
                        </label>

                        <input
                            id="math"
                            type="number"
                            name="math"
                            min="0"
                            max="100"
                            value="<?= e($selected_student['math'] ?? 0) ?>"
                            required
                        >

                    </div>


                    <div class="field">

                        <label for="cpp">
                            C++
                        </label>

                        <input
                            id="cpp"
                            type="number"
                            name="cpp"
                            min="0"
                            max="100"
                            value="<?= e($selected_student['cpp'] ?? 0) ?>"
                            required
                        >

                    </div>


                    <div class="field">

                        <label for="python">
                            Python
                        </label>

                        <input
                            id="python"
                            type="number"
                            name="python"
                            min="0"
                            max="100"
                            value="<?= e($selected_student['python'] ?? 0) ?>"
                            required
                        >

                    </div>


                    <div class="field">

                        <label for="graphics">
                            Graphics
                        </label>

                        <input
                            id="graphics"
                            type="number"
                            name="graphics"
                            min="0"
                            max="100"
                            value="<?= e($selected_student['graphics'] ?? 0) ?>"
                            required
                        >

                    </div>

                </div>


                <button
                    type="submit"
                    name="update_marks"
                    value="1"
                    class="update-btn"
                >
                    Update Marks
                </button>
<?php if ($flag3 !== null): ?>
        <div class="flag-box">
            <div class="flag-label">FLAG 03</div>
            <div class="flag-value">
                <?= e($flag3) ?>
            </div>
        </div>
    <?php endif; ?>
            </form>


            <a
                class="back"
                href="edit-marks.php"
            >
                ← Back to student records
            </a>

        </section>

    <?php endif; ?>


    <!-- STUDENT RECORDS -->

    <section class="panel">

        <?php if ($search === ''): ?>

            <h2>
                Student Records
            </h2>

            <p class="search-note">
                Showing the default limited student records.
            </p>

        <?php else: ?>

            <h2>
                Search Results
            </h2>

        <?php endif; ?>


        <?php if (count($students) > 0): ?>

            <table>

                <thead>

                    <tr>

                        <th>
                            Student ID
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Department
                        </th>

                        <th class="marks">
                            Math
                        </th>

                        <th class="marks">
                            C++
                        </th>

                        <th class="marks">
                            Python
                        </th>

                        <th class="marks">
                            Graphics
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($students as $student): ?>

                        <tr>

                            <td>
                                <?= e($student['student_id']) ?>
                            </td>

                            <td>
                                <?= e(
                                    $student['first_name']
                                    . ' '
                                    . $student['last_name']
                                ) ?>
                            </td>

                            <td>
                                <?= e($student['department']) ?>
                            </td>

                            <td class="marks">
                                <?= e($student['math'] ?? 0) ?>
                            </td>

                            <td class="marks">
                                <?= e($student['cpp'] ?? 0) ?>
                            </td>

                            <td class="marks">
                                <?= e($student['python'] ?? 0) ?>
                            </td>

                            <td class="marks">
                                <?= e($student['graphics'] ?? 0) ?>
                            </td>

                            <td>

                                <a
                                    class="edit-btn"
                                    href="edit-marks.php?id=<?= urlencode($student['student_id']) ?>"
                                >
                                    Edit Marks
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty">

                Student not found.

            </div>

        <?php endif; ?>

    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>