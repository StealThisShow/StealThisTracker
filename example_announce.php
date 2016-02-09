<?php

use \StealThisShow\StealThisTracker as STT;

// ---------------------------------------
// This is how to set up an announce URL.
// ---------------------------------------

// Composer autoloader
require( dirname(__FILE__).'/vendor/autoload.php' );

// Creating a simple config object. You can replace this with your object
// implementing ConfigInterface.
$config = new STT\Config\Simple( array(
    // Persistense object implementing PersistenceInterface.
    // We use Pdo here. The object is initialized with its own config.
    'persistence' => new STT\Persistence\Pdo(
        new STT\Config\Simple( array(
            'dsn'           => 'localhost',
            'username'      => 'misc',
            'password'      => 'misc',
            'options'       => 'misc',
        ) )
    ),
    // The IP address of the connecting client.
    'ip'        => $_SERVER['REMOTE_ADDR'],
    // Interval of the next announcement in seconds - sent back to the client.
    'interval'  => 60,
) );

// Core class managing the announcements.
$core = new STT\Core( $config );

// We take the parameters the client is sending and initialize a config
// object with them. Again, you can implement your own Config class to do this.
$get = new STT\Config\Simple( $_GET );

// We simply send back the results of the announce method to the client.
echo $core->announce( $get );