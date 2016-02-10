<?php
/**
 * Simple tracker server implementation for system test.
 * Being run with PHP's built-in web server .
 * @see SeedServerTest
 */
// No tolerance for errors - it's a test.
set_error_handler( function ( $errno, $errstr, $errfile = null, $errline = null )
{
    throw new Exception( "Error $errno: $errstr in $errfile:$errline" );
} );

require( dirname(__FILE__) . '/../../vendor/autoload.php' );

use StealThisShow\StealThisTracker\Persistence\Pdo;
use StealThisShow\StealThisTracker\Core;
use StealThisShow\StealThisTracker\Config\Simple;

$config = new Simple( array(
    'persistence' => new Pdo(new Simple( array(
        'dsn' => 'sqlite:' . sys_get_temp_dir() . '/sqlite_test.db'
    ) ) ),
    'ip'        => $_SERVER['REMOTE_ADDR'],
    'interval'  => 60,
    'load_balancing' => false
) );
$core = new Core( $config );
echo $core->announce( new Simple($_GET) );