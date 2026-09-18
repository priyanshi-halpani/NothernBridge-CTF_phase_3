<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db.php';

$errors = [];
$success = false;
$generated = [];

/*
|--------------------------------------------------------------------------
| Generate Student ID
|--------------------------------------------------------------------------
| Example:
| Yash Sharma -> NC-YS26-4821 */
function generateStudentId(string $firstName, string $lastName): string
{
    $initials = strtoupper(
        substr($firstName, 0, 1) .
        substr($lastName, 0, 1)
    );

    $year = date('y');

    $suffix = str_pad(
        (string) random_int(0, 9999),
        4,
        '0',
        STR_PAD_LEFT
    );

    return "NC-{$initials}{$year}-{$suffix}";
}


/*
|--------------------------------------------------------------------------
| Generate Email
|--------------------------------------------------------------------------
| Requirement:firstname + lastname + @northenbridge */
function generateEmail(string $firstName, string $lastName): string
{
    $firstName = strtolower(
        preg_replace('/[^a-zA-Z0-9]/', '', $firstName)
    );

    $lastName = strtolower(
        preg_replace('/[^a-zA-Z0-9]/', '', $lastName)
    );

    return $firstName . $lastName . '@northenbridge.lab';
}


/*
|------------------
| Generate Password
|------------------
| Requirement: first name + DOB year */
function generatePassword(string $firstName, string $dob): string
{
    $birthYear = date('Y', strtotime($dob));

    return strtolower(
        preg_replace('/[^a-zA-Z0-9]/', '', $firstName)
    ) . $birthYear;
}


/* Collect Registration Details */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName     = trim($_POST['first_name'] ?? '');
    $lastName      = trim($_POST['last_name'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $dob           = trim($_POST['dob'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $department    = trim($_POST['department'] ?? '');

    $allowedDepartments = [
        'Information Technology',
        'Computer Science',
        'Civil Engineering',
        'Mechanical Engineering'
    ];

    /*Input field Validation */
    if ($firstName === '') {
        $errors[] = 'First name is required.';
    }

    if ($lastName === '') {
        $errors[] = 'Last name is required.';
    }

    if ($contactNumber === '') {
        $errors[] = 'Contact number is required.';
    } elseif (!preg_match('/^[0-9+\-\s()]{7,15}$/', $contactNumber)) {
        $errors[] = 'Please enter a valid contact number.';
    }

    if ($dob === '') {
        $errors[] = 'Date of birth is required.';
    } elseif (
        strtotime($dob) === false ||
        strtotime($dob) > time()
    ) {
        $errors[] = 'Please enter a valid date of birth.';
    }

    if ($address === '') {
        $errors[] = 'Residential address is required.';
    }

    if (!in_array($department, $allowedDepartments, true)) {
        $errors[] = 'Please select a valid department.';
    }

    /* Create Student */
    if (empty($errors)) {

        $studentId = generateStudentId(
            $firstName,
            $lastName
        );

        $email = generateEmail(
            $firstName,
            $lastName
        );

        $password = generatePassword(
            $firstName,
            $dob
        );

        /* Dummy marks */
        $marks = [
            'Math'     => 70,
            'C++'      => 85,
            'Python'   => 20,
            'Graphics' => 50
        ];

        /* Insert student into SQLite */
        $studentStmt = $db->prepare("
            INSERT INTO students
            (
                student_id,
                first_name,
                last_name,
                contact_number,
                dob,
                address,
                department,
                email,
                password
            )
            VALUES
            (
                :student_id,
                :first_name,
                :last_name,
                :contact_number,
                :dob,
                :address,
                :department,
                :email,
                :password
            )
        ");

        $studentStmt->bindValue(
            ':student_id',
            $studentId,
            SQLITE3_TEXT
        );

        $studentStmt->bindValue(
            ':first_name',
            $firstName,
            SQLITE3_TEXT
        );

        $studentStmt->bindValue(
            ':last_name',
            $lastName,
            SQLITE3_TEXT
        );

        $studentStmt->bindValue(
            ':contact_number',
            $contactNumber,
            SQLITE3_TEXT
        );

        $studentStmt->bindValue(
            ':dob',
            $dob,
            SQLITE3_TEXT
        );

        $studentStmt->bindValue(
            ':address',
            $address,
            SQLITE3_TEXT
        );

        $studentStmt->bindValue(
            ':department',
            $department,
            SQLITE3_TEXT
        );

        $studentStmt->bindValue(
            ':email',
            $email,
            SQLITE3_TEXT
        );

        $studentStmt->bindValue(
            ':password',
            $password,
            SQLITE3_TEXT
        );

        if (!$studentStmt->execute()) {

            $errors[] = 'Unable to create student account.';

        } else {

            /* Seed initial marks */
            $marksStmt = $db->prepare("
                INSERT INTO marks
                (
                    student_id,
                    math,
                    cpp,
                    python,
                    graphics
                )
                VALUES
                (
                    :student_id,
                    :math,
                    :cpp,
                    :python,
                    :graphics
                )
            ");

            $marksStmt->bindValue(
                ':student_id',
                $studentId,
                SQLITE3_TEXT
            );

            $marksStmt->bindValue(
                ':math',
                70,
                SQLITE3_INTEGER
            );

            $marksStmt->bindValue(
                ':cpp',
                85,
                SQLITE3_INTEGER
            );

            $marksStmt->bindValue(
                ':python',
                20,
                SQLITE3_INTEGER
            );

            $marksStmt->bindValue(
                ':graphics',
                50,
                SQLITE3_INTEGER
            );

            if (!$marksStmt->execute()) {

                $errors[] =
                    'Student was created, but marks could not be saved.';

            } else {

                /*Return generated credentials */
                $generated = [
                    'student_id' => $studentId,
                    'email'      => $email,
                    'password'   => $password
                ];

                $success = true;
            }
        }
    }
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

    <title>
        Student Registration — Northenbridge College
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


        /* Navigation */

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


        .nav-links a:hover {
            color: #d9b878;
        }


        .login-link {
            padding: 9px 16px;

            border: 1px solid #d9b878;
            border-radius: 5px;
        }


        /* Page */

        .page {
            max-width: 1050px;

            margin: 50px auto;

            padding: 0 20px;
        }


        .intro {
            text-align: center;

            margin-bottom: 35px;
        }


        .eyebrow {
            color: var(--brass);

            font-size: 11px;
            font-weight: bold;

            letter-spacing: 2px;

            margin-bottom: 10px;
        }


        h1 {
            margin: 0 0 10px;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 38px;
        }


        .intro p {
            max-width: 600px;

            margin: auto;

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;
        }


        /* Form Card */

        .card {
            background: var(--white);

            border: 1px solid var(--line);

            border-top: 4px solid var(--brass);

            padding: 35px;
        }


        .card h2 {
            margin: 0 0 8px;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 25px;
        }


        .card-subtitle {
            margin: 0 0 28px;

            color: var(--muted);

            font-size: 13px;
        }


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
        select,
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
        select:focus,
        textarea:focus {

            border-color: var(--brass);

            box-shadow:
                0 0 0 2px
                rgba(169,124,51,0.10);
        }


        textarea {
            min-height: 115px;

            resize: vertical;
        }


        .submit-btn {

            margin-top: 28px;

            padding: 13px 28px;

            border: none;
            border-radius: 5px;

            background: var(--hedge);
            color: white;

            font-size: 14px;
            font-weight: bold;

            cursor: pointer;
        }


        .submit-btn:hover {
            background: var(--hedge-dark);
        }


        /* Errors */

        .error-box {

            margin-bottom: 25px;

            padding: 15px;

            background: #f7e9e6;

            border-left: 4px solid #7d3027;

            color: #7d3027;

            font-size: 13px;
        }


        .error-box ul {
            margin: 8px 0 0;

            padding-left: 20px;
        }


        /* Success Tile */

        .success-overlay {

            position: fixed;

            inset: 0;

            background:
                rgba(20,32,26,0.72);

            display: flex;

            align-items: center;
            justify-content: center;

            padding: 20px;

            z-index: 1000;
        }


        .success-tile {

            width: 100%;
            max-width: 500px;

            background: white;

            border-top: 5px solid var(--brass);

            padding: 35px;

            box-shadow:
                0 20px 60px
                rgba(0,0,0,0.25);
        }


        .success-icon {

            width: 55px;
            height: 55px;

            margin-bottom: 18px;

            border-radius: 50%;

            background: var(--hedge);

            color: white;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 25px;
        }


        .success-tile h2 {

            margin: 0 0 8px;

            font-family:
                Georgia,
                "Times New Roman",
                serif;
        }


        .success-tile p {

            margin: 0 0 22px;

            color: var(--muted);

            font-size: 13px;

            line-height: 1.5;
        }


        .credential {

            margin-bottom: 12px;

            padding: 13px 15px;

            background: #f5f3ed;

            border: 1px solid var(--line);
        }


        .credential-label {

            display: block;

            margin-bottom: 5px;

            color: var(--muted);

            font-size: 10px;

            font-weight: bold;

            letter-spacing: 1px;
        }


        .credential-value {

            font-size: 15px;

            font-weight: bold;

            word-break: break-word;
        }


        .credential-value.password {
            color: var(--brass);
        }


        .success-note {

            margin-top: 20px !important;

            padding: 12px;

            background: #e7f0e9;

            color: #245837 !important;

            font-size: 12px !important;
        }
        .success-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 22px;
        }

        .success-actions a,
        .success-actions button {
            width: 100%;
            padding: 12px;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            font-family: inherit;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }

        .primary-action {
            border: none;
            background: var(--hedge);
            color: white;
        }

        .primary-action:hover {
            background: var(--hedge-dark);
        }

        .secondary-action {
            border: 1px solid var(--line);
            color: var(--ink);
            background: white;
        }

        .secondary-action:hover {
            background: #f5f3ed;
        }

        .download-status {
            margin: 16px 0 0 !important;
            padding: 12px;
            background: #e7f0e9;
            color: #245837 !important;
            font-size: 12px !important;
            line-height: 1.4;
        }


        /* Footer */

        footer {

            margin-top: 50px;

            padding: 22px 7%;

            border-top: 1px solid var(--line);

            background: #f1eee6;

            color: var(--muted);

            text-align: center;

            font-size: 12px;
        }


        /* Responsive */

        @media (max-width: 700px) {

            nav {
                padding: 15px 5%;
            }


            .nav-links a:not(.login-link) {
                display: none;
            }


            .page {
                margin-top: 30px;
            }


            .card {
                padding: 25px;
            }


            .form-grid {
                grid-template-columns: 1fr;
            }


            .field.full {
                grid-column: auto;
            }


            h1 {
                font-size: 31px;
            }

        }

    </style>

</head>


<body>


<!-- Registration -->

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="page">

    <div class="intro">

        <div class="eyebrow">
            STUDENT ADMISSIONS
        </div>

        <h1>
            Create Your Student Account
        </h1>

        <p>
            Complete the registration form below to enroll at
            Northenbridge College. Your student credentials
            will be generated automatically after registration.
        </p>

    </div>


    <div class="card">

        <h2>
            Registration Information
        </h2>

        <p class="card-subtitle">
            Please provide accurate information for your student record.
        </p>


        <?php if (!empty($errors)): ?>

            <div class="error-box">

                <strong>
                    Please correct the following:
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


        <form
            method="POST"
            action="register.php"
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
                                $_POST['first_name'] ?? ''
                            );
                        ?>"
                        placeholder="Enter first name"
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
                                $_POST['last_name'] ?? ''
                            );
                        ?>"
                        placeholder="Enter last name"
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
                                $_POST['contact_number'] ?? ''
                            );
                        ?>"
                        placeholder="+91 98765 43210"
                        required
                    >

                </div>


                <!-- DOB -->

                <div class="field">

                    <label for="dob">
                        Date of Birth
                    </label>

                    <input
                        type="date"
                        id="dob"
                        name="dob"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST['dob'] ?? ''
                            );
                        ?>"
                        required
                    >

                </div>


                <!-- Department -->

                <div class="field full">

                    <label for="department">
                        Department to Enroll In
                    </label>

                    <select
                        id="department"
                        name="department"
                        required
                    >

                        <option value="">
                            Select your department
                        </option>

                        <?php

                        $departments = [
                            'Information Technology',
                            'Computer Science',
                            'Civil Engineering',
                            'Mechanical Engineering'
                        ];

                        foreach ($departments as $dept):

                        ?>

                            <option
                                value="<?php
                                    echo htmlspecialchars($dept);
                                ?>"
                                <?php
                                echo (
                                    ($_POST['department'] ?? '') === $dept
                                )
                                ? 'selected'
                                : '';
                                ?>
                            >
                                <?php
                                echo htmlspecialchars($dept);
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Address -->

                <div class="field full">

                    <label for="address">
                        Residential Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        placeholder="Enter your complete residential address"
                        required
                    ><?php
                        echo htmlspecialchars(
                            $_POST['address'] ?? ''
                        );
                    ?></textarea>

                </div>

            </div>


            <button
                type="submit"
                class="submit-btn"
            >
                Complete Registration
            </button>

        </form>

    </div>

</main>


<!-- Success Tile -->

<?php if ($success): ?>

<div class="success-overlay">

    <div class="success-tile">

        <div class="success-icon">
            ✓
        </div>

        <h2>
            Registration Successful
        </h2>

        <!-- Student ID -->

        <div class="credential">

            <span class="credential-label">
                STUDENT ID
            </span>

            <span class="credential-value">
                <?php
                echo htmlspecialchars(
                    $generated['student_id']
                );
                ?>
            </span>

        </div>


        <!-- Email -->

        <div class="credential">

            <span class="credential-label">
                STUDENT EMAIL
            </span>

            <span class="credential-value">
                <?php
                echo htmlspecialchars(
                    $generated['email']
                );
                ?>
            </span>

        </div>


        <!-- Password -->

        <div class="credential">

            <span class="credential-label">
                PASSWORD
            </span>

            <span class="credential-value password">
                <?php
                echo htmlspecialchars(
                    $generated['password']
                );
                ?>
            </span>

        </div>

        <div class="success-actions">
            <button
                type="button"
                id="downloadCredentials"
                class="primary-action"
            >
                Download Credentials
            </button>

            <a
                href="login.php"
                id="continueLogin"
                class="secondary-action continue-action"
                hidden
            >
                Continue to Student Login
            </a>
        </div>

        <p class="download-status" id="downloadStatus" hidden>
            Credentials downloaded successfully. Keep the file safe.
        </p>

    </div>

</div>

<?php endif; ?>



<script>
document.addEventListener('DOMContentLoaded', function () {
    const downloadButton = document.getElementById('downloadCredentials');
    const continueLogin = document.getElementById('continueLogin');
    const downloadStatus = document.getElementById('downloadStatus');

    if (!downloadButton) {
        return;
    }

    downloadButton.addEventListener('click', function () {
        const studentId = <?php echo json_encode($generated['student_id'] ?? ''); ?>;
        const email = <?php echo json_encode($generated['email'] ?? ''); ?>;
        const password = <?php echo json_encode($generated['password'] ?? ''); ?>;

        if (!studentId || !email || !password) {
            return;
        }

        const credentials = [
            'NORTHENBRIDGE COLLEGE',
            'STUDENT LOGIN CREDENTIALS',
            '',
            'Student ID : ' + studentId,
            'Email      : ' + email,
            'Password   : ' + password,
            '',
            'Please keep these credentials safe.',
            'Use them to log in to the student portal.',
            ''
        ].join('\r\n');

        const blob = new Blob([credentials], {
            type: 'text/plain;charset=utf-8'
        });

        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = url;
        link.download = studentId + '-cred.txt';
        document.body.appendChild(link);
        link.click();
        link.remove();

        setTimeout(function () {
            URL.revokeObjectURL(url);
        }, 1000);

        downloadButton.textContent = 'Credentials Downloaded';
        downloadButton.disabled = true;
        downloadButton.style.cursor = 'default';

        downloadStatus.hidden = false;
        continueLogin.hidden = false;
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
