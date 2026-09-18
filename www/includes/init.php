<?php
/*
|--------------------------------------------------------------------------
| Shared runtime bootstrap
|--------------------------------------------------------------------------
| - Starts the student session once.
| - Enforces production error handling: no details sent to the browser,
|   failures are written to the server error log and surfaced as a
|   generic 500 page.
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

/*
|--------------------------------------------------------------------------
| Generic 500 page renderer
|--------------------------------------------------------------------------
*/
function nc_render_500(): void
{
    if (!headers_sent()) {
        http_response_code(500);
    }

    $errorPage = __DIR__ . '/../500.html';

    if (is_file($errorPage)) {
        readfile($errorPage);
    } else {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
           . '<title>500 Internal Server Error</title></head><body>'
           . '<h1>Something went wrong</h1><p>Please try again shortly.</p>'
           . '</body></html>';
    }
}

set_exception_handler(static function (Throwable $e): void {
    error_log('[Northbridge] Uncaught exception: ' . $e->getMessage()
            . ' @ ' . $e->getFile() . ':' . $e->getLine());
    nc_render_500();
});

register_shutdown_function(static function (): void {
    $err = error_get_last();

    if ($err !== null && in_array(
        $err['type'],
        [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR],
        true
    )) {
        error_log('[Northbridge] Fatal error: ' . $err['message']
                . ' @ ' . $err['file'] . ':' . $err['line']);
        nc_render_500();
    }
});