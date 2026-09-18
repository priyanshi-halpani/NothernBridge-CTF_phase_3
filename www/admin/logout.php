<?php

require_once __DIR__ . '/../includes/init.php';

unset(
    $_SESSION['admin_logged_in'],
    $_SESSION['admin_id'],
    $_SESSION['admin_username']
);

session_regenerate_id(true);

header('Location: index.php');
exit;