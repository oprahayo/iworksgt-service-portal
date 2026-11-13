<?php
declare(strict_types=1);

require __DIR__.'/../app/error_response.php';
$sourceMessage='Database connection failed. password=super-secret PDOException SELECT private_data';
ob_start();
iworks_public_error_response(new RuntimeException($sourceMessage));
$html=(string)ob_get_clean();
if (str_contains($html,$sourceMessage)
    || str_contains($html,'config.php')
    || str_contains($html,'PDOException')
    || str_contains($html,'private_data')
    || !str_contains($html,'Reference:')
    || !str_contains($html,'We could not complete your request')) {
    throw new RuntimeException('Public error page leaked internal details.');
}
$router=(string)file_get_contents(__DIR__.'/../index.php');
if (!str_contains($router,"set_exception_handler('iworks_public_error_response')")
    || str_contains($router,'$e->getMessage()) ?></strong>')
    || !str_contains($router,'iworks_report_error($e)')) {
    throw new RuntimeException('Uncaught errors or webhooks may reveal exception details.');
}
$db=(string)file_get_contents(__DIR__.'/../app/db.php');
if (str_contains($db,"' . $e->getMessage()") || !str_contains($db,'Database service is temporarily unavailable.')) {
    throw new RuntimeException('Database driver details are not redacted.');
}
echo "f22_safe_error_response_test passed\n";
