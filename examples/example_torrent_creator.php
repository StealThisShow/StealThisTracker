<?php

/**
 * Torrent creator example
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use \StealThisShow\StealThisTracker\Core;
use \StealThisShow\StealThisTracker\Torrent;
use \StealThisShow\StealThisTracker\Persistence\Pdo;
use \StealThisShow\StealThisTracker\File\File;

// -----------------------------------------------------------
// This is how to create a .torrent file from a physical file.
// -----------------------------------------------------------

// Composer autoloader
require dirname(__FILE__) . '/../vendor/autoload.php';

// Persistence object implementing PersistenceInterface.
// We use Pdo here.
$persistence = new Pdo('sqlite:sqlite_example.db');

// Core class managing creating the file.
$core = new Core($persistence);

// The torrent file
$file = new File('/path/to/file.ext');

// The first parameters is the file.
$torrent = new Torrent($file);

$torrent
    // List of public announce URLs on your server.
    ->setAnnounceList(array('http://announce'))
    // Piece length (in bytes) can be set optionally (defaults to 256 kB)
    ->setSizePiece(262144);

// Setting appropriate HTTP header and sending back the .torrrent file.
// This is VERY inefficient to do! SAVE the .torrent file on your server and
// serve the saved copy!
header('Content-Type: application/x-bittorrent');
header('Content-Disposition: attachment; filename="test.torrent"');

// Outputs torrent content
echo (string) $core->addTorrent($torrent);