<?php
/**
 * Announce example
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use \StealThisShow\StealThisTracker\Core;
use \StealThisShow\StealThisTracker\Persistence\Pdo;

// ---------------------------------------
// This is how to set up an announce URL.
// ---------------------------------------

// Composer autoloader
require dirname(__FILE__).'/../vendor/autoload.php';

// Persistence object implementing PersistenceInterface.
// We use Pdo here.
$persistence = new Pdo('sqlite:sqlite_example.db');

// Core class managing the announcements.
$core = new Core($persistence);
$core
    // Interval of the next announcement in seconds - sent back to the client.
    ->setInterval(60)
    // The IP-address of the connecting client.
    ->setIp($_SERVER['REMOTE_ADDR']);

// We simply send back the results of the announce method to the client.
echo $core->announce($_GET);