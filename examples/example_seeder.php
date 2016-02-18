<?php
/**
 * Seeder example
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use \StealThisShow\StealThisTracker\Persistence\Pdo;
use \StealThisShow\StealThisTracker\Seeder\Peer;
use \StealThisShow\StealThisTracker\Seeder\Server;
use \StealThisShow\StealThisTracker\Logger\File;

// --------------------------------------
// This is how to start a seeding server.
// --------------------------------------

// [!] Run this file in CLI only!
// /usr/bin/php example_seeder.php

// Composer autoloader
require dirname(__FILE__) . '/../vendor/autoload.php';

// Persistence object implementing PersistenceInterface.
// We use Pdo here.
$persistence = new Pdo('sqlite:sqlite_example.db');


// Setting up seeder peer. This will listen to connections and serve files.
$peer = new Peer($persistence);
$peer
    // Private address of the seeder server.
    // (Optional parameter for IP to open socket on if differs from external.)
    ->setInternalAddress('192.168.2.123')
    // Public address of the seeder server.
    // This will be used for announcements (ie. sent to the clients).
    ->setExternalAddress('192.168.2.123')
    // Don't forget the firewall!
    ->setPort(6881)
    // Number telling how many processes
    // should be forked to listen to incoming connections.
    ->setPeerForks(10)
    // If specified, gives a number of outsider seeders to make self-seeding stop.
    // This saves you bandwidth - once your
    // file is seeded by others, you can stop serving it.
    // Number of seeders is permanently checked,
    // but probably 1 is too few if you want your file to be available always.
    ->setSeedersStopSeeding(5)
    // Initializing file logger with default
    // file path (/var/log/stealthistracker.log).
    ->setLogger(new File());

// We set up a seeding server which starts the seeding peer, and makes regular
// announcements to the database adding itself to the peer list for all
// active torrents.
$server = new Server($peer, $persistence);
// Initializing file logger with default file path (/var/log/stealthistracker.log).
$server->setLogger(new File());

// Starting "detached" means that process
// will unrelate from terminal and run as daemon.
// To run in terminal, you can use start().
// Detached running requires php-posix.
$server->startDetached();
