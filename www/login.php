<?php
// ---------------------------------------------------------
// Northenbridge College — Student Login
// ---------------------------------------------------------
// Database authentication will be connected later.
// This version provides the complete login UI and flow.
// ---------------------------------------------------------
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation for now.
    // Real credential verification will be added
    // when the database is connected.

    if ($email === '') {
        $error = 'Please enter your student email.';
    } elseif ($password === '') {
        $error = 'Please enter your password.';
    } else {

    $stmt = $db->prepare("
        SELECT
            student_id,
            email,
            password
        FROM students
        WHERE email = :email
        LIMIT 1
    ");

    $stmt->bindValue(':email', $email, SQLITE3_TEXT);

    $result = $stmt->execute();
    $student = $result->fetchArray(SQLITE3_ASSOC);

    if (!$student) {

        $error = 'Invalid student email or password.';

    } elseif ($student['password'] !== $password) {

        $error = 'Invalid student email or password.';

    } else {

        session_regenerate_id(true);

        $_SESSION['student_logged'] = true;
        $_SESSION['student_id'] = $student['student_id'];

        header('Location: profile.php');
        exit;
    }
  }
}

$year = date('Y');
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
        Student Login — Northenbridge College
    </title>

    <style>

        :root {
            --ink:        #1c2a24;
            --parchment:  #f6f3ec;
            --hedge:      #2f4a3d;
            --hedge-dark: #203329;
            --brass:      #a97c33;
            --line:       #d9d2c2;
            --muted:      #5b6b62;
            --white:      #ffffff;

            --error-bg:   #f7e9e6;
            --error-text: #7d3027;
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


        /* =================================================
           Navigation
        ================================================= */

        nav {
            height: 72px;

            background: var(--hedge-dark);
            color: white;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 7%;
        }

        .brand {
            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 21px;
            font-weight: bold;

            letter-spacing: 0.3px;
        }

        .brand span {
            color: #d9b878;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .nav-links a {
            color: #edf1ed;

            text-decoration: none;

            font-size: 14px;
        }

        .nav-links a:hover {
            color: #d9b878;
        }

        .register-btn {
            border: 1px solid #d9b878;

            color: #fff !important;

            padding: 10px 17px;

            border-radius: 5px;
        }


        /* =================================================
           Main
        ================================================= */

        main {
            min-height: calc(100vh - 72px);

            padding: 60px 7%;

            display: flex;
            align-items: center;
            justify-content: center;
        }


        /* =================================================
           Login container
        ================================================= */

        .login-wrapper {
            width: 100%;
            max-width: 1050px;

            display: grid;
            grid-template-columns: 0.9fr 1.1fr;

            background: var(--white);

            border: 1px solid var(--line);

            box-shadow:
                0 18px 45px
                rgba(32, 51, 41, 0.09);
        }


        /* =================================================
           Left information panel
        ================================================= */

        .login-intro {
            background: var(--hedge);

            color: white;

            padding: 55px 45px;

            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .eyebrow {
            color: #d9b878;

            font-size: 11px;
            font-weight: bold;

            letter-spacing: 2px;

            margin-bottom: 18px;
        }

        .login-intro h1 {
            margin: 0 0 20px;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 40px;
            line-height: 1.1;
        }

        .login-intro p {
            margin: 0;

            color: #e4ebe6;

            font-size: 15px;
            line-height: 1.7;
        }


        /* Portal information */

        .portal-info {
            margin-top: 45px;

            padding-top: 24px;

            border-top:
                1px solid
                rgba(255,255,255,0.18);
        }

        .portal-info-title {
            color: #e9d9b8;

            font-size: 11px;
            font-weight: bold;

            letter-spacing: 1.5px;

            margin-bottom: 9px;
        }

        .portal-info p {
            font-size: 13px;
            line-height: 1.6;
        }


        /* =================================================
           Login form panel
        ================================================= */

        .login-form-panel {
            padding: 55px 55px;
        }

        .login-form-panel h2 {
            margin: 0 0 8px;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size: 31px;
        }

        .sub {
            margin: 0 0 32px;

            color: var(--muted);

            font-size: 14px;
            line-height: 1.5;
        }


        /* =================================================
           Error
        ================================================= */

        .alert {
            background: var(--error-bg);

            border-left:
                4px solid
                var(--error-text);

            color: var(--error-text);

            padding: 14px 16px;

            margin-bottom: 25px;

            font-size: 14px;
        }


        /* =================================================
           Fields
        ================================================= */

        .field {
            display: flex;
            flex-direction: column;

            margin-bottom: 21px;
        }

        label {
            margin-bottom: 8px;

            font-size: 13px;
            font-weight: bold;

            color: var(--ink);
        }

        input {
            width: 100%;

            padding: 13px 14px;

            border:
                1px solid
                var(--line);

            border-radius: 4px;

            background: #fff;

            color: var(--ink);

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            font-size: 14px;

            outline: none;
        }

        input:focus {
            border-color: var(--brass);

            box-shadow:
                0 0 0 2px
                rgba(169,124,51,0.10);
        }


        /* =================================================
           Login options
        ================================================= */

        .login-options {
            display: flex;

            justify-content: space-between;
            align-items: center;

            margin-top: 3px;
            margin-bottom: 27px;
        }

        .remember {
            display: flex;
            align-items: center;

            gap: 8px;

            color: var(--muted);

            font-size: 13px;
        }

        .remember input {
            width: auto;

            accent-color: var(--hedge);
        }

        .forgot {
            color: var(--hedge);

            font-size: 13px;
            font-weight: bold;

            text-decoration: none;
        }

        .forgot:hover {
            color: var(--brass);
        }


        /* =================================================
           Login button
        ================================================= */

        .login-submit {
            width: 100%;

            border: none;
            border-radius: 5px;

            padding: 14px 20px;

            background: var(--hedge);

            color: white;

            font-size: 14px;
            font-weight: bold;

            cursor: pointer;
        }

        .login-submit:hover {
            background: var(--hedge-dark);
        }


        /* =================================================
           Registration link
        ================================================= */

        .register-line {
            margin-top: 24px;

            text-align: center;

            color: var(--muted);

            font-size: 13px;
        }

        .register-line a {
            color: var(--hedge);

            font-weight: bold;

            text-decoration: none;
        }

        .register-line a:hover {
            color: var(--brass);
        }


        /* =================================================
           Footer
        ================================================= */

        footer {
            border-top:
                1px solid
                var(--line);

            padding: 22px 7%;

            background: #f1eee6;
        }

        .footer-inner {
            max-width: 1050px;

            margin: 0 auto;

            display: flex;
            justify-content: space-between;

            color: var(--muted);

            font-size: 12px;
        }


        /* =================================================
           Responsive
        ================================================= */

        @media (max-width: 850px) {

            nav {
                padding: 0 5%;
            }

            .nav-links a:not(.register-btn) {
                display: none;
            }

            main {
                padding: 35px 5%;
            }

            .login-wrapper {
                grid-template-columns: 1fr;
            }

            .login-intro {
                padding: 38px;
            }

            .login-form-panel {
                padding: 38px;
            }
        }


        @media (max-width: 600px) {

            .login-intro h1 {
                font-size: 32px;
            }

            .login-options {
                flex-direction: column;
                align-items: flex-start;

                gap: 14px;
            }

            .footer-inner {
                flex-direction: column;

                gap: 8px;
            }
        }

    </style>

</head>


<body>


<?php require_once __DIR__ . '/includes/header.php'; ?>


<!-- =====================================================
     Login
===================================================== -->

<main>

    <div class="login-wrapper">


        <!-- Left panel -->

        <section class="login-intro">

            <div>

                <div class="eyebrow">
                    NORTHENBRIDGE COLLEGE
                </div>

                <h1>
                    Student
                    Portal
                </h1>

                <p>
                    Access your student profile,
                    academic information and examination
                    results through the Northenbridge
                    student portal.
                </p>

            </div>


            <div class="portal-info">

                <div class="portal-info-title">
                    YOUR PORTAL
                </div>

                <p>
                    Use the institutional email and
                    password provided to you during
                    registration.
                </p>

            </div>

        </section>


        <!-- Right form -->

        <section class="login-form-panel">

            <h2>
                Student Login
            </h2>

            <p class="sub">
                Sign in to continue to your student account.
            </p>


            <?php if ($error !== ''): ?>

                <div class="alert">
                    <?php
                    echo htmlspecialchars($error);
                    ?>
                </div>

            <?php endif; ?>


            <form
                method="POST"
                action="login.php"
            >


                <!-- Email -->

                <div class="field">

                    <label for="email">
                        Student Email
                    </label>

                    <input
                        type="text"
                        id="email"
                        name="email"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST['email'] ?? ''
                            );
                        ?>"
                        placeholder="e.g. studentname@northenbridge"
                        autocomplete="username"
                        required
                    >

                </div>


                <!-- Password -->

                <div class="field">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <!-- Options -->

                <div class="login-options">

                    <label class="remember">

                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                        >

                        <span>
                            Keep me signed in
                        </span>

                    </label>


                    <a
                        href="#"
                        class="forgot"
                    >
                        Forgot password?
                    </a>

                </div>


                <!-- Submit -->

                <button
                    type="submit"
                    class="login-submit"
                >
                    Sign In
                </button>

            </form>


            <div class="register-line">

                Don't have a student account?

                <a href="register.php">
                    Register here
                </a>

            </div>

        </section>

    </div>

</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>