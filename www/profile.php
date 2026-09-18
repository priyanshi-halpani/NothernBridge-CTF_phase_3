<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db.php';
$errors = [];
$updated = false;


/*
|--------------------------------------------------------------------------
| Require student login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['student_logged']) || !isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$studentId = $_SESSION['student_id'];


/*
|--------------------------------------------------------------------------
| Fetch student from SQLite
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        student_id,
        first_name,
        last_name,
        contact_number,
        dob,
        address,
        department,
        email,
        password
    FROM students
    WHERE student_id = :student_id
    LIMIT 1
");

$stmt->bindValue(':student_id', $studentId, SQLITE3_TEXT);

$result = $stmt->execute();
$profile = $result->fetchArray(SQLITE3_ASSOC);

if (!$profile) {
    session_destroy();
    die('Student record not found.');
}

/*
|--------------------------------------------------------------------------
| Update profile
|--------------------------------------------------------------------------
| Editable:
| - First name
| - Last name
| - Contact number
| - Address
|
| Not editable:
| - Student ID
| - Email
| - Password
| - Department
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName     = trim($_POST['first_name'] ?? '');
    $lastName      = trim($_POST['last_name'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $address       = trim($_POST['address'] ?? '');


    /* Validation */

    if ($firstName === '') {
        $errors[] = 'First name is required.';
    }


    if ($lastName === '') {
        $errors[] = 'Last name is required.';
    }


    if ($contactNumber === '') {

        $errors[] = 'Contact number is required.';

    } elseif (
        !preg_match(
            '/^[0-9+\-\s()]{7,15}$/',
            $contactNumber
        )
    ) {

        $errors[] = 'Please enter a valid contact number.';
    }


    if ($address === '') {
        $errors[] = 'Residential address is required.';
    }

    /*
    |--------------------------------------------------------------------------
    | Save changes
    |--------------------------------------------------------------------------
    */

if (empty($errors)) {

    $stmt = $db->prepare("
        UPDATE students
        SET
            first_name = :first_name,
            last_name = :last_name,
            contact_number = :contact_number,
            address = :address
        WHERE student_id = :student_id
    ");

    $stmt->bindValue(':first_name', $firstName, SQLITE3_TEXT);
    $stmt->bindValue(':last_name', $lastName, SQLITE3_TEXT);
    $stmt->bindValue(':contact_number', $contactNumber, SQLITE3_TEXT);
    $stmt->bindValue(':address', $address, SQLITE3_TEXT);
    $stmt->bindValue(':student_id', $studentId, SQLITE3_TEXT);

    if ($stmt->execute()) {

        $profile['first_name'] = $firstName;
        $profile['last_name'] = $lastName;
        $profile['contact_number'] = $contactNumber;
        $profile['address'] = $address;

        $updated = true;

    } else {

        $errors[] = 'Unable to update your information.';
    }
}
}

/*
|--------------------------------------------------------------------------
| Student initials
|--------------------------------------------------------------------------
*/

$initials =
    strtoupper(
        substr($profile['first_name'], 0, 1) .
        substr($profile['last_name'], 0, 1)
    );

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        My Profile — Northedgebridge College
    </title>


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
        }


        body {
            margin: 0;

            background: var(--parchment);
            color: var(--ink);

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }


        /* ================= Navigation ================= */

        nav {

            min-height: 72px;

            padding: 0 7%;

            background: var(--hedge-dark);
            color: white;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .brand {

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 21px;

            font-weight: bold;
        }


        .brand span {
            color: #d9b878;
        }


        .nav-links {

            display: flex;

            align-items: center;

            gap: 24px;
        }


        .nav-links a {

            color: #edf1ed;

            text-decoration: none;

            font-size: 14px;
        }


        .nav-links a:hover,
        .nav-links a.active {

            color: #d9b878;
        }


        .logout {

            padding: 9px 16px;

            border: 1px solid #d9b878;

            border-radius: 5px;
        }


        /* ================= Header ================= */

        .page-header {

            background: var(--hedge);

            color: white;

            padding: 45px 7%;
        }


        .header-inner {

            max-width: 1100px;

            margin: auto;
        }


        .eyebrow {

            color: #d9b878;

            font-size: 11px;

            font-weight: bold;

            letter-spacing: 2px;

            margin-bottom: 10px;
        }


        .page-header h1 {

            margin: 0 0 8px;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 36px;
        }


        .page-header p {

            margin: 0;

            color: #e0e8e2;

            font-size: 14px;
        }


        /* ================= Main ================= */

        .page {

            max-width: 1100px;

            margin: 40px auto;

            padding: 0 20px;
        }


        .layout {

            display: grid;

            grid-template-columns: 280px 1fr;

            gap: 25px;

            align-items: start;
        }


        /* ================= Identity ================= */

        .identity {

            background: var(--hedge-dark);

            color: white;

            padding: 28px;

            border-top: 4px solid var(--brass);
        }


        .avatar {

            width: 72px;

            height: 72px;

            margin-bottom: 18px;

            border-radius: 50%;

            background: var(--brass);

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 25px;

            font-weight: bold;
        }


        .identity h2 {

            margin: 0 0 7px;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 22px;
        }


        .email {

            margin: 0 0 25px;

            color: #d7e0da;

            font-size: 12px;

            word-break: break-word;
        }


        .identity-item {

            padding: 14px 0;

            border-top:
                1px solid
                rgba(255,255,255,0.13);
        }


        .identity-label {

            display: block;

            color: #d9b878;

            font-size: 10px;

            font-weight: bold;

            letter-spacing: 1px;

            margin-bottom: 5px;
        }


        .identity-value {

            font-size: 13px;

            color: white;
        }


        /* ================= Panel ================= */

        .panel {

            background: white;

            border: 1px solid var(--line);

            padding: 35px;
        }


        .panel h2 {

            margin: 0 0 7px;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 27px;
        }


        .panel-sub {

            margin: 0 0 28px;

            color: var(--muted);

            font-size: 13px;

            line-height: 1.5;
        }


        /* ================= Alerts ================= */

        .alert {

            padding: 14px 16px;

            margin-bottom: 22px;

            font-size: 13px;
        }


        .alert.error {

            background: #f7e9e6;

            border-left: 4px solid #7d3027;

            color: #7d3027;
        }


        .alert.success {

            background: #e7f0e9;

            border-left: 4px solid #245837;

            color: #245837;
        }


        .alert ul {

            margin: 8px 0 0;

            padding-left: 20px;
        }


        /* ================= Form ================= */

        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;
        }


        .field {

            display: flex;

            flex-direction: column;
        }


        .field.full {

            grid-column: 1 / -1;
        }


        label {

            margin-bottom: 8px;

            font-size: 13px;

            font-weight: bold;
        }


        input,
        textarea {

            width: 100%;

            padding: 12px 13px;

            border: 1px solid var(--line);

            border-radius: 4px;

            background: white;

            color: var(--ink);

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            font-size: 14px;

            outline: none;
        }


        input:focus,
        textarea:focus {

            border-color: var(--brass);

            box-shadow:
                0 0 0 2px
                rgba(169,124,51,0.10);
        }


        textarea {

            min-height: 110px;

            resize: vertical;
        }


        .readonly {

            background: #f0eee8;

            color: var(--muted);

            cursor: not-allowed;
        }


        .help {

            margin-top: 6px;

            color: var(--muted);

            font-size: 11px;
        }


        .save-btn {

            margin-top: 25px;

            padding: 13px 25px;

            border: none;

            border-radius: 5px;

            background: var(--hedge);

            color: white;

            font-size: 14px;

            font-weight: bold;

            cursor: pointer;
        }


        .save-btn:hover {

            background: var(--hedge-dark);
        }


        /* ================= Exam Result ================= */

        .result-section {

            margin-top: 30px;

            padding-top: 28px;

            border-top: 1px solid var(--line);
        }


        .result-section h3 {

            margin: 0 0 8px;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 21px;
        }


        .result-section p {

            margin: 0 0 18px;

            color: var(--muted);

            font-size: 13px;
        }


        .result-btn {

            display: inline-block;

            padding: 12px 20px;

            background: var(--brass);

            color: white;

            text-decoration: none;

            border-radius: 4px;

            font-size: 13px;

            font-weight: bold;
        }


        .result-btn:hover {

            background: #8e6729;
        }


        /* ================= Footer ================= */

        footer {

            margin-top: 50px;

            padding: 22px 7%;

            border-top: 1px solid var(--line);

            background: #f1eee6;

            color: var(--muted);

            text-align: center;

            font-size: 12px;
        }


        /* ================= Responsive ================= */

        @media (max-width: 800px) {

            .layout {

                grid-template-columns: 1fr;
            }


            .nav-links a:not(.logout) {

                display: none;
            }

        }


        @media (max-width: 600px) {

            .page-header {

                padding: 35px 5%;
            }


            .page {

                margin-top: 25px;
            }


            .panel {

                padding: 25px;
            }


            .form-grid {

                grid-template-columns: 1fr;
            }


            .field.full {

                grid-column: auto;
            }


            .page-header h1 {

                font-size: 30px;
            }

        }
        /* ================= Reassessment ================= */

        .reassessment-btn {
        display: inline-block;
        margin-left: 10px;
        padding: 12px 20px;
        background: var(--hedge);
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 13px;
        font-weight: bold;
        cursor: pointer;
        }

        .reassessment-btn:hover {
         background: var(--hedge-dark);
        }

        .reassessment-error {
         display: none;
         margin-top: 18px;
         padding: 14px 16px;
         background: #f7e9e6;
         border-left: 4px solid #7d3027;
         color: #7d3027;
         font-size: 13px;
         line-height: 1.6;
        }

        .reassessment-error strong {
         display: block;
         margin-bottom: 4px;
        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<!-- ================= Header ================= -->

<header class="page-header">

    <div class="header-inner">

        <div class="eyebrow">
            STUDENT PORTAL
        </div>

        <h1>
            My Profile
        </h1>

        <p>
            View your student information and manage your personal details.
        </p>

    </div>

</header>


<!-- ================= Main ================= -->

<main class="page">

    <div class="layout">


        <!-- ================= Identity Card ================= -->

        <aside class="identity">

            <div class="avatar">
                <?php echo htmlspecialchars($initials); ?>
            </div>


            <h2>

                <?php

                echo htmlspecialchars(
                    $profile['first_name'] .
                    ' ' .
                    $profile['last_name']
                );

                ?>

            </h2>


            <p class="email">

                <?php
                echo htmlspecialchars(
                    $profile['email']
                );
                ?>

            </p>


            <div class="identity-item">

                <span class="identity-label">
                    STUDENT ID
                </span>

                <span class="identity-value">

                    <?php
                    echo htmlspecialchars(
                        $profile['student_id']
                    );
                    ?>

                </span>

            </div>


            <div class="identity-item">

                <span class="identity-label">
                    DEPARTMENT
                </span>

                <span class="identity-value">

                    <?php
                    echo htmlspecialchars(
                        $profile['department']
                    );
                    ?>

                </span>

            </div>


            <div class="identity-item">

                <span class="identity-label">
                    STATUS
                </span>

                <span class="identity-value">
                    Active
                </span>

            </div>

        </aside>


        <!-- ================= Information Panel ================= -->

        <section class="panel">

            <h2>
                My Information
            </h2>

            <p class="panel-sub">
                Update your personal information below.
                Student ID, email, department and password
                cannot be changed from this page.
            </p>


            <!-- Error -->

            <?php if (!empty($errors)): ?>

                <div class="alert error">

                    <strong>
                        Please fix the following:
                    </strong>

                    <ul>

                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?php
                                echo htmlspecialchars($error);
                                ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- Success -->

            <?php if ($updated): ?>

                <div class="alert success">
                    Your information has been updated successfully.
                </div>

            <?php endif; ?>


            <form
                method="POST"
                action="profile.php"
            >

                <div class="form-grid">


                    <!-- First Name -->

                    <div class="field">

                        <label for="first_name">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            value="<?php
                                echo htmlspecialchars(
                                    $profile['first_name']
                                );
                            ?>"
                            required
                        >

                    </div>


                    <!-- Last Name -->

                    <div class="field">

                        <label for="last_name">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            value="<?php
                                echo htmlspecialchars(
                                    $profile['last_name']
                                );
                            ?>"
                            required
                        >

                    </div>


                    <!-- Contact -->

                    <div class="field">

                        <label for="contact_number">
                            Contact Number
                        </label>

                        <input
                            type="text"
                            id="contact_number"
                            name="contact_number"
                            value="<?php
                                echo htmlspecialchars(
                                    $profile['contact_number']
                                );
                            ?>"
                            required
                        >

                    </div>


                    <!-- DOB - Read Only -->

                    <div class="field">

                        <label for="dob">
                            Date of Birth
                        </label>

                        <input
                            type="date"
                            id="dob"
                            value="<?php
                                echo htmlspecialchars(
                                    $profile['dob']
                                );
                            ?>"
                            class="readonly"
                            readonly
                        >

                        <span class="help">
                            Date of birth cannot be changed here.
                        </span>

                    </div>


                    <!-- Department - Read Only -->

                    <div class="field">

                        <label for="department">
                            Department
                        </label>

                        <input
                            type="text"
                            id="department"
                            value="<?php
                                echo htmlspecialchars(
                                    $profile['department']
                                );
                            ?>"
                            class="readonly"
                            readonly
                        >

                        <span class="help">
                            Department is assigned during enrollment.
                        </span>

                    </div>


                    <!-- Email - Read Only -->

                    <div class="field">

                        <label for="email">
                            Student Email
                        </label>

                        <input
                            type="text"
                            id="email"
                            value="<?php
                                echo htmlspecialchars(
                                    $profile['email']
                                );
                            ?>"
                            class="readonly"
                            readonly
                        >

                        <span class="help">
                            Generated institutional email cannot be changed.
                        </span>

                    </div>


                    <!-- Student ID - Read Only -->

                    <div class="field">

                        <label for="student_id">
                            Student ID
                        </label>

                        <input
                            type="text"
                            id="student_id"
                            value="<?php
                                echo htmlspecialchars(
                                    $profile['student_id']
                                );
                            ?>"
                            class="readonly"
                            readonly
                        >

                        <span class="help">
                            Student ID cannot be changed.
                        </span>

                    </div>


                    <!-- Address -->

                    <div class="field full">

                        <label for="address">
                            Residential Address
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            required
                        ><?php
                            echo htmlspecialchars(
                                $profile['address']
                            );
                        ?></textarea>

                    </div>

                </div>


                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Changes
                </button>

            </form>


            <!-- ================= Exam Result ================= -->

            <div class="result-section">

                <h3>
                    Examination Result
                </h3>

                <p>
                    View your marks, percentage and overall
                    examination result.
                </p>

                <a
                    href="marks.php"
                    class="result-btn"
                >
                    View Exam Result
                </a>

            </div>

        </section>

    </div>

</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
