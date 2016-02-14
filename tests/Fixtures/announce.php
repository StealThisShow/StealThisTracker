<?php
/**
 * Simple tracker server implementation for system test.
 * Being run with PHP's built-in web server (or Apache).
 * @package StealThisTracker
 * @see     SeedServerTest
 */
// No tolerance for errors - it's a test.
set_error_handler(
    function ($errno, $errstr, $errfile = null, $errline = null) {
        throw new Exception("Error $errno: $errstr in $errfile:$errline");
    }
);

require dirname(__FILE__) . '/../../vendor/autoload.php';

use StealThisShow\StealThisTracker\Persistence\Pdo;
use StealThisShow\StealThisTracker\Core;

$persistence = new Pdo('sqlite:' . sys_get_temp_dir() . '/sqlite_test.db');

$core = new Core($persistence);
$core
    ->setInterval(60)
    ->setIp($_SERVER['REMOTE_ADDR']);

echo $core->announce($_GET);