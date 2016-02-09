<?php

namespace StealThisShow\StealThisTracker\Persistence;

use StealThisShow\StealThisTracker\Config;
use StealThisShow\StealThisTracker\Torrent;
use StealThisShow\StealThisTracker\File;

/**
 * Test class for Pdo.
 */
class PdoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Pdo
     */
    protected $object;

    protected $db_path;

    protected $sql_path =  __DIR__ . '/../../sqlite.sql';

    protected $torrent_path;

    const TEST_DATA = 'abcdefghijklmnopqrstuvwxyz';

    protected function setUp()
    {
        $this->db_path = sys_get_temp_dir() . '/sqlite_test.db';
        touch( $this->db_path );
        $this->setupDatabaseFixture( $this->db_path, $this->sql_path );

        $this->object = new Pdo(new Config\Simple( array(
            'dsn' => 'sqlite:' . $this->db_path
        ) ) );
    }

    protected function setupDatabaseFixture( $db_file, $sql_file )
    {
        $table_definitions = file_get_contents( $sql_file );
        $driver = new \PDO( 'sqlite:' . $db_file );
        $statements = preg_split( '/;[ \t]*\n/', $table_definitions, -1, PREG_SPLIT_NO_EMPTY );
        foreach ( $statements as $statement )
        {
            if ( !$driver->query( $statement ) )
            {
                $this->fail(
                    'Could not set up database fixture: ' .
                    var_export( $driver->errorInfo(), true )
                );
            }
        }
    }

    protected function tearDown()
    {
        // Closing file handles.
        unset( $this->object );
        // Remove temporary file.
        if ( file_exists( $this->torrent_path ) )
        {
            unlink( $this->torrent_path );
        }
        if ( file_exists( $this->db_path ) )
        {
            unlink( $this->db_path );
        }
    }

    protected function getTorrentObject()
    {
        $this->torrent_path = sys_get_temp_dir() . '/test_torrent';
        file_put_contents( $this->torrent_path, self::TEST_DATA );

        $file = new File\File( $this->torrent_path );

        return new Torrent( $file, 2 , null, null, null, null, null, array( 'http://announce' ), array( 'http://example.com/test.ext' ) );
    }

    public function testSaveTorrent()
    {
        $torrent = $this->getTorrentObject();
        $this->object->saveTorrent( $torrent );
        $db_torrent = $this->object->getTorrent( $torrent->info_hash );

        $info_hash_readable = current( unpack( 'H*', $db_torrent->info_hash ) );

        $this->assertEquals( 'ce604353af13707d499e376cd8672e32a3260e01', $info_hash_readable );
        $this->assertEquals( array( 'http://announce' ), $db_torrent->announce_list );
        $this->assertEquals( array( 'http://example.com/test.ext' ), $db_torrent->url_list );
    }
}