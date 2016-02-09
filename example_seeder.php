<?php

use \StealThisShow\StealThisTracker as STT;

// --------------------------------------
// This is how to start a seeding server.
// --------------------------------------

// [!] Run this file in CLI only!
// /usr/bin/php example_seeder.php

// Composer autoloader
require( dirname(__FILE__).'/vendor/autoload.php' );

// Persistense object implementing PersistenceInterface.
// We use Pdo here. The object is initialized with its own config.
$persistence = new STT\Persistence\Pdo(
    new STT\Config\Simple( array(
        'dsn'           => 'localhost',
        'username'      => 'misc',
        'password'      => 'misc',
        'options'       => 'misc',
    ) )
);

// Setting up seeder peer. This will listen to connections and serve files.
$peer = new STT\Seeder\Peer(
    new STT\Config\Simple( array(
        'persistence'               => $persistence,
        // PUBLIC address of the seeder server. This will be used for announcements (ie. sent to the clients).
        'seeder_address'            => '192.168.2.123',
        // Don't forget the firewall!
        'seeder_port'               => 6881,
        // Optional parameter for IP to open socket on if differs from external.
        //'seeder_internal_address'   => '192.168.2.123',
        // Number telling how many processes should be forked to listen to incoming connections.
        'peer_forks'                => 10,
        // If specified, gives a number of outsider seeders to make self-seeding stop.
        // This saves you bandwidth - once your file is seeded by others, you can stop serving it.
        // Number of seeders is permanently checked, but probably 1 is too few if you want your file to be available always.
        'seeders_stop_seeding'      => 5,
        // Intializing file logger with default file path (/var/log/stealthistracker.log).
        'logger'  => new STT\Logger\File(),
    )
) );

// We set up a seeding server which starts the seeding peer, and makes regular
// announcements to the database adding itself to the peer list for all
// active torrents.
$server = new STT\Seeder\Server(
     new STT\Config\Simple( array(
        'persistence'           => $persistence,
        'peer'                  => $peer,
         // Intializing file logger with default file path (/var/log/stealthistracker.log).
        'logger'  => new STT\Logger\File(),
    )
) );

// Starting "detached" means that process will unrelate from terminal and run as deamon.
// To run in terminal, you can use start().
// Detached running requires php-posix.
$server->startDetached();
