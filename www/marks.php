<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db.php';

/*
|--------------------------------------------------------------------------
| Require student login
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$studentId = $_SESSION['student_id'];

/*
|--------------------------------------------------------------------------
| Fetch student information from database
|--------------------------------------------------------------------------
*/
$stmt = $db->prepare("
    SELECT
        student_id,
        first_name,
        last_name,
        department
    FROM students
    WHERE student_id = :student_id
    LIMIT 1
");

$stmt->bindValue(':student_id', $studentId, SQLITE3_TEXT);

$studentResult = $stmt->execute();
$student = $studentResult->fetchArray(SQLITE3_ASSOC);

if (!$student) {
    die("Student record not found.");
}

/*
|--------------------------------------------------------------------------
| Fetch marks from database
|--------------------------------------------------------------------------
*/
$stmt = $db->prepare("
    SELECT
        math,
        cpp,
        python,
        graphics
    FROM marks
    WHERE student_id = :student_id
    LIMIT 1
");

$stmt->bindValue(':student_id', $studentId, SQLITE3_TEXT);

$marksResult = $stmt->execute();
$marks = $marksResult->fetchArray(SQLITE3_ASSOC);

if (!$marks) {
    die("Marks record not found.");
}

/*
|--------------------------------------------------------------------------
| Convert database values to numbers
|--------------------------------------------------------------------------
*/
$math     = (float)$marks['math'];
$cpp      = (float)$marks['cpp'];
$python   = (float)$marks['python'];
$graphics = (float)$marks['graphics'];

/*
|--------------------------------------------------------------------------
| Calculate result
|--------------------------------------------------------------------------
*/
$totalSubjects = 4;
$totalMarks = $math + $cpp + $python + $graphics;
$percentage = ($totalMarks / ($totalSubjects * 100)) * 100;

/*
|--------------------------------------------------------------------------
| Fail if ANY subject is below 35
|--------------------------------------------------------------------------
*/
$failedSubject = (
    $math < 35 ||
    $cpp < 35 ||
    $python < 35 ||
    $graphics < 35
);

$status = $failedSubject ? "FAILED" : "PASSED";
$statusClass = $failedSubject ? "failed" : "passed";
/*
|--------------------------------------------------------------------------
| Fetch Flag 04 only after student passes
|--------------------------------------------------------------------------
*/
$flag4 = null;

if (!$failedSubject) {
    $flagStmt = $db->prepare("
        SELECT flag_value
        FROM flags
        WHERE flag_name = :flag_name
        LIMIT 1
    ");

    $flagStmt->bindValue(
        ':flag_name',
        'Flag_04',
        SQLITE3_TEXT
    );

    $flagResult = $flagStmt->execute();

    if ($flagResult) {
        $flagRow = $flagResult->fetchArray(SQLITE3_ASSOC);

        if ($flagRow) {
            $flag4 = $flagRow['flag_value'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Exam Result | Northedgebridge College</title>

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
            --red: #c62828;
            --green: #238636;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: var(--parchment);
            color: var(--ink);
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
            color: #fff;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 24px;
            font-size: 14px;
        }

        nav a:hover {
            color: #d6b46a;
        }

        .container {
            width: min(900px, 92%);
            margin: 50px auto;
        }

        .heading {
            margin-bottom: 25px;
        }

        .heading h1 {
            font-family: Georgia, serif;
            font-size: 36px;
            margin-bottom: 8px;
        }

        .heading p {
            color: var(--muted);
        }

        .student-card {
            background: var(--hedge);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .student-card h2 {
            font-family: Georgia, serif;
            margin-bottom: 12px;
        }

        .student-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px 30px;
        }

        .student-info span {
            color: #dce5df;
        }

        .result-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 18px 20px;
            text-align: left;
            border-bottom: 1px solid var(--line);
        }

        th {
            background: #eeeae1;
            font-family: Georgia, serif;
        }

        td:last-child,
        th:last-child {
            text-align: right;
        }

        .low-mark {
            font-weight: bold;
        }

        .summary {
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .percentage {
            font-family: Georgia, serif;
            font-size: 25px;
        }

        .status {
            font-family: Georgia, serif;
            font-size: 25px;
            font-weight: bold;
        }

        .status.failed {
            color: var(--red);
        }

        .status.passed {
            color: var(--green);
        }

        .back {
            display: inline-block;
            margin-top: 25px;
            background: var(--brass);
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 7px;
            font-weight: bold;
        }

        .back:hover {
            opacity: 0.9;
        }

        @media (max-width: 650px) {
            .student-info {
                grid-template-columns: 1fr;
            }

            th,
            td {
                padding: 14px 10px;
            }

            .heading h1 {
                font-size: 29px;
            }
        }

        .flag-box {
            width: 100%;
        margin-top: 10px;
        padding: 18px;
        background: #f6f3ec;
        border: 1px solid var(--brass);
        border-radius: 8px;
        }

        .flag-label {
        font-size: 12px;
        font-weight: bold;
        letter-spacing: 1px;
        color: var(--brass);
        margin-bottom: 8px;
        }

        .flag-value {
        font-family: "Courier New", monospace;
        font-weight: bold;
        word-break: break-all;
        }

        .reassessment {
        margin-top: 25px;
        }   

        .reassessment-btn {
        display: inline-block;
        padding: 12px 20px;
        background: var(--hedge);
        color: white;
        border: none;
        border-radius: 7px;
        font-weight: bold;
        font-size: 14px;
        cursor: pointer;
        }

        .reassessment-btn:hover {
        background: var(--hedge-dark);
        }

        .reassessment-error {
        display: none;
        margin-top: 18px;
        padding: 16px 18px;
        background: #f7e9e6;
        border-left: 4px solid var(--red);
        color: #7d3027;
        border-radius: 6px;
        font-size: 13px;
        line-height: 1.6;
        }

        .reassessment-error strong {
        display: block;
        margin-bottom: 5px;
        }

        .reassessment-error code {
        font-family: "Courier New", monospace;
        font-size: 12px;
        }
    </style>
</head>

<body>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="container">

    <div class="heading">
        <h1>Examination Result</h1>
        <p>Official academic result for the current examination.</p>
    </div>

    <section class="student-card">
        <h2>
            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
        </h2>

        <div class="student-info">
            <div>
                Student ID:
                <span><?= htmlspecialchars($student['student_id']) ?></span>
            </div>

            <div>
                Department:
                <span><?= htmlspecialchars($student['department']) ?></span>
            </div>
        </div>
    </section>

    <section class="result-card">

        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Marks / 100</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>Math</td>
                    <td class="<?= $math < 35 ? 'low-mark' : '' ?>">
                        <?= htmlspecialchars($math) ?>
                    </td>
                </tr>

                <tr>
                    <td>C++</td>
                    <td class="<?= $cpp < 35 ? 'low-mark' : '' ?>">
                        <?= htmlspecialchars($cpp) ?>
                    </td>
                </tr>

                <tr>
                    <td>Python</td>
                    <td class="<?= $python < 35 ? 'low-mark' : '' ?>">
                        <?= htmlspecialchars($python) ?>
                    </td>
                </tr>

                <tr>
                    <td>Graphics</td>
                    <td class="<?= $graphics < 35 ? 'low-mark' : '' ?>">
                        <?= htmlspecialchars($graphics) ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="summary">

            <div class="percentage">
                Percentage:
                <strong><?= number_format($percentage, 2) ?>%</strong>
            </div>

            <div class="status <?= $statusClass ?>">
                <?= $status ?>
            </div>
            <?php if ($flag4 !== null): ?>
    <div class="flag-box">
        <div class="flag-label">CTF FLAG 4</div>
        <div class="flag-value">
            <?= htmlspecialchars($flag4) ?>
        </div>
    </div>
<?php endif; ?>

        </div>

    </section>
        
        <div class="reassessment">

            <button type="button" class="reassessment-btn" onclick="showReassessmentError()" > 
                Apply for Reassessment
            </button>

            <div
                id="reassessmentError"
                class="reassessment-error"
                role="alert">
                <strong>Reassessment Service Unavailable</strong>

                Reassessment requests are currently unavailable.<br>

                Please check back later.<br>

                <code>ERR_SERVICE_UNAVAILABLE</code>
            </div>

        </div>
        <a href="profile.php" class="back">
            ← Back to Profile
        </a>

</main>
<script>
function showReassessmentError() {
    const errorBox = document.getElementById('reassessmentError');

    errorBox.style.display = 'block';

    errorBox.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
    });
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>