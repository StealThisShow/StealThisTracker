<?php

/**
 * Simple seed server implementation for system test.
 * @see SeedServerTest
 */

// No tolerance for errors - it's a test.
set_error_handler( function ( $errno, $errstr, $errfile = null, $errline = null )
{
    throw new Exception( "Error $errno: $errstr in $errfile:$errline" );
} );

require( dirname(__FILE__) . '/../../vendor/autoload.php' );

use StealThisShow\StealThisTracker\Persistence\Pdo;
use StealThisShow\StealThisTracker\Config\Simple;
use StealThisShow\StealThisTracker\Seeder\Peer;
use StealThisShow\StealThisTracker\Seeder\Server;

$ip     = $argv[1];
$port   = $argv[2];

fwrite( STDERR, "Starting seed server at $ip:$port" );

$config = new Simple( array(
    'persistence' => new Pdo(new Simple( array(
        'dsn' => 'sqlite:' . sys_get_temp_dir() . '/sqlite_test.db'
    ) ) ),
    'seeder_address' => $ip,
    'seeder_internal_address' => $ip,
    'seeder_port' => $port,
    'peer_forks' => 5,
    'seeders_stop_seeding' => 5,
) );

$peer = new Peer( $config );

$config = new Simple( array(
    'peer' => $peer,
    'persistence' => new Pdo(new Simple( array(
        'dsn' => 'sqlite:' . sys_get_temp_dir() . '/sqlite_test.db'
    ) ) )
) );

$server = new Server( $config );

$server->start();
