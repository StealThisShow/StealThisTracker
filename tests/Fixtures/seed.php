<?php
/**
 * Simple seed server implementation for system test.
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @licence https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
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
use StealThisShow\StealThisTracker\Seeder\Peer;
use StealThisShow\StealThisTracker\Seeder\Server;

$ip     = $argv[1];
$port   = $argv[2];

fwrite(STDERR, "Starting seed server at $ip:$port");

$persistence = new Pdo('sqlite:' . sys_get_temp_dir() . '/sqlite_test.db');

$peer = new Peer($persistence);
$peer
    ->setExternalAddress($ip)
    ->setInternalAddress($ip)
    ->setPort($port)
    ->setPeerForks(5)
    ->setSeedersStopSeeding(5);

$server = new Server($peer, $persistence);

$server->start();
