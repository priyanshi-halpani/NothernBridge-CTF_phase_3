<?php
require_once __DIR__ . '/includes/init.php';

session_destroy();

header('Location: index.php');
exit;