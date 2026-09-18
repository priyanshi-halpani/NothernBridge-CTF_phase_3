<?php

try {
    $db = new SQLite3(__DIR__ . '/database/college.db');
    $db->busyTimeout(5000);
    $db->exec('PRAGMA foreign_keys = ON;');
} catch (Throwable $e) {
    error_log('[Northbridge] Database connection failed: ' . $e->getMessage());

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(500);
    }

    $errorPage = __DIR__ . '/500.html';

    if (is_file($errorPage)) {
        readfile($errorPage);
    } else {
        echo '<!DOCTYPE html><html><head><title>500 Internal Server Error</title></head>'
           . '<body><h1>Something went wrong</h1><p>The records service is temporarily unavailable.</p></body></html>';
    }

    exit;
}