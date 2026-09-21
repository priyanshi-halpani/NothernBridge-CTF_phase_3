<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../db.php';

$error = '';
// Fetch Flag 1 from database
$flag1 = null;

$flagStmt = $db->prepare("
    SELECT flag_value
    FROM flags
    WHERE flag_name = :flag_name
    LIMIT 1
");

$flagStmt->bindValue(
    ':flag_name',
    'Flag_01',
    SQLITE3_TEXT
);

$flagResult = $flagStmt->execute();

if ($flagResult) {
    $flagRow = $flagResult->fetchArray(SQLITE3_ASSOC);

    if ($flagRow) {
        $flag1 = $flagRow['flag_value'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {

        /*
         * Database-backed authentication.
         *
         * For the CTF lab, this compares the submitted password
         * against the password stored in the admins table.
         */
        $stmt = $db->prepare("
            SELECT id, username, name, number, salary, password
            FROM admins
            WHERE username = :username
            LIMIT 1
        ");

        $stmt->bindValue(':username', $username, SQLITE3_TEXT);

        $result = $stmt->execute();
        $admin = $result->fetchArray(SQLITE3_ASSOC);

        if ($admin && hash_equals($admin['password'], $password)) {

            session_regenerate_id(true);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            header('Location: dashboard.php');
            exit;

        } else {
            $error = 'Invalid administrator username or password.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Portal | Northenbridge College</title>

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
            min-height: 100vh;
            background: var(--parchment);
            color: var(--ink);
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
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

        .admin-label {
            font-size: 13px;
            color: #d8dfda;
            letter-spacing: 1px;
        }

        .login-wrapper {
            width: min(900px, 92%);
            margin: auto;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            background: white;
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 12px 35px rgba(32, 51, 41, 0.08);
        }

        .portal-panel {
            background: var(--hedge);
            color: white;
            padding: 50px 40px;
        }

        .portal-panel .small {
            color: #d7e0da;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 15px;
        }

        .portal-panel h1 {
            font-family: Georgia, serif;
            font-size: 34px;
            line-height: 1.15;
            margin-bottom: 18px;
        }

        .portal-panel p {
            color: #dce5df;
            line-height: 1.7;
            font-size: 15px;
        }

        .form-panel {
            padding: 50px 45px;
        }

        .form-panel h2 {
            font-family: Georgia, serif;
            font-size: 30px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 30px;
        }

        .error {
            background: #fdecec;
            color: var(--red);
            border: 1px solid #f2c5c5;
            padding: 12px 14px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: #fff;
            color: var(--ink);
            font-size: 15px;
            outline: none;
        }

        input:focus {
            border-color: var(--brass);
            box-shadow: 0 0 0 3px rgba(169, 124, 51, 0.12);
        }

        button {
            width: 100%;
            border: none;
            border-radius: 7px;
            padding: 14px;
            background: var(--hedge-dark);
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: var(--hedge);
        }

        .notice {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 700px) {

            .login-wrapper {
                grid-template-columns: 1fr;
                margin: 35px auto;
            }

            .portal-panel,
            .form-panel {
                padding: 35px 28px;
            }

            .portal-panel h1 {
                font-size: 29px;
            }
        }
        /* style for flag*/
        .flag-box {
             margin-top: 100px;
             padding: 10px;
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


<main class="login-wrapper">

    <section class="portal-panel">

        <div class="small">
            Restricted Area
        </div>

        <h1>
            Administrator Portal
        </h1>

        <p>
            Access the Northenbridge College administration
            system to manage student academic records and
            administrative information.
        </p>
         <?php if ($flag1 !== null): ?>
            <div class="flag-box">
                <div class="flag-label">FLAG 01</div>
                <div class="flag-value">
                    <?= htmlspecialchars($flag1) ?>
                </div>
            </div>
        <?php endif; ?>

    </section>


    <section class="form-panel">

        <h2>Admin Login</h2>

        <p class="subtitle">
            Sign in with your administrator credentials.
        </p>

        <?php if ($error): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <!-- backup: /.env -->

        <form method="POST">

            <div class="field">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter administrator username"
                    required
                    autocomplete="username"
                >

            </div>


            <div class="field">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter administrator password"
                    required
                    autocomplete="current-password"
                >

            </div>


            <button type="submit">
                Sign In
            </button>

        </form>

        <div class="notice">
            This portal is restricted to authorized
            Northenbridge College administrators.
        </div>

    </section>

</main>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>