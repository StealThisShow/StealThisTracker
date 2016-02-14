<?php

/**
 * Torrent creator example
 *
 * @package StealThisTracker
 */

use \StealThisShow\StealThisTracker\Core;
use \StealThisShow\StealThisTracker\Torrent;
use \StealThisShow\StealThisTracker\Persistence\Pdo;
use \StealThisShow\StealThisTracker\File\File;

// -----------------------------------------------------------
// This is how to create a .torrent file from a physical file.
// -----------------------------------------------------------

// Composer autoloader
require dirname(__FILE__).'/vendor/autoload.php';

// Persistence object implementing PersistenceInterface.
// We use Pdo here.
$persistence = new Pdo('sqlite:sqlite_example.db');

// Core class managing creating the file.
$core = new Core($persistence);

// The torrent file
$file = new File('/path/to/file.ext');

// The first parameters is the file,
// the second is the piece size in bytes.
$torrent = new Torrent($file, 524288);
// List of public announce URLs on your server.
$torrent->setAnnounceList(
    array(
        'http://announce'
    )
);

// Setting appropriate HTTP header and sending back the .torrrent file.
// This is VERY inefficient to do! SAVE the .torrent file on your server and
// serve the saved copy!
header('Content-Type: application/x-bittorrent');
header('Content-Disposition: attachment; filename="test.torrent"');

// Outputs torrent content
echo $core->addTorrent($torrent);