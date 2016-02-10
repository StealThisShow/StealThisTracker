<?php

namespace StealThisShow\StealThisTracker;

class AnnounceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The config object
     *
     * @var Config\Simple
     */
    protected $config;

    /**
     * The database path
     *
     * @var string
     */
    protected $db_path;

    /**
     * The SQL path
     *
     * @var string
     */
    protected $sql_path;

    const CLIENT_IP             = '123.123.123.123';
    const CLIENT_PORT           = '555';
    const ANNOUNCE_INTERVAL     = 60;
    const INFO_HASH             = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    const PEER_ID               = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\1";
    const SEED_PEER_ID          = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\2";
    const SEED_IP               = '124.124.124.124';
    const LEECH_PEER_ID         = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\3";
    const LEECH_IP              = '2001:db8:85a3::8a2e:370:7334';

    public function setUp()
    {
        $this->db_path = sys_get_temp_dir() . '/sqlite_test.db';
        $this->sql_path = dirname( __FILE__ ) . '/../Fixtures/sqlite.sql';
        touch( $this->db_path );
        $this->setupDatabaseFixture( $this->db_path, $this->sql_path );

        $this->config = new Config\Simple( array(
            'persistence' => new Persistence\Pdo(new Config\Simple( array(
                'dsn' => 'sqlite:' . $this->db_path
            ) ) ),
            'ip'        => self::CLIENT_IP,
            'interval'  => self::ANNOUNCE_INTERVAL,
            'load_balancing' => false
        ) );
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

    public function tearDown()
    {
        if (file_exists($this->db_path)) {
            unlink($this->db_path);
        }
    }

    /**
     * @requires pdo
     */
    public function testFirstAnnounce()
    {
        $core = new Core( $this->config );
        $get = new Config\Simple( array(
            'info_hash'     => self::INFO_HASH,
            'peer_id'       => self::PEER_ID,
            'port'          => self::CLIENT_PORT,
            'uploaded'      => 1024,
            'downloaded'    => 2048,
            'left'          => 4096,
        ) );

        $response = $core->announce( $get );
        $parsed_response = $this->parseResponse( $response );

        $this->assertEquals( 0, $parsed_response['complete'] );
        $this->assertEquals( 0, $parsed_response['incomplete'] );
        $this->assertEquals( array(), $parsed_response['peers'] );
        $this->assertEquals(
            self::ANNOUNCE_INTERVAL,
            $parsed_response['interval']
        );
    }

    /**
     * @requires pdo
     */
    public function testAnnounceWithPeers()
    {
        $core = new Core( $this->config );

        $this->announceOtherPeers( $core );

        $get = new Config\Simple( array(
            'info_hash'     => self::INFO_HASH,
            'peer_id'       => self::PEER_ID,
            'port'          => self::CLIENT_PORT,
            'uploaded'      => 1024,
            'downloaded'    => 2048,
            'left'          => 4096,
        ) );

        $response = $core->announce( $get );
        $parsed_response = $this->parseResponse( $response );

        $this->assertEquals( 1, $parsed_response['complete'] );
        $this->assertEquals( 1, $parsed_response['incomplete'] );
        $this->assertContains( array(
            // Using the same port for the other peers.
            'ip'        => self::SEED_IP,
            'port'      => self::CLIENT_PORT,
            'peer id'   => self::SEED_PEER_ID,
        ), $parsed_response['peers'] );
        $this->assertContains( array(
            // Using the same port for the other peers.
            'ip'        => self::LEECH_IP,
            'port'      => self::CLIENT_PORT,
            'peer id'   => self::LEECH_PEER_ID,
        ), $parsed_response['peers'] );
        $this->assertEquals(
            self::ANNOUNCE_INTERVAL,
            $parsed_response['interval']
        );
    }

    private function parseResponse( $response )
    {
        $parser = new Bencode\Parser( $response );
        return $parser->parse()->represent();
    }

    private function announceOtherPeers( Core $core )
    {
        // Announcing a seeder (testing update of peer as well).
        $core->announce( new Config\Simple( array(
            'info_hash'     => self::INFO_HASH,
            'peer_id'       => self::SEED_PEER_ID,
            'port'          => self::CLIENT_PORT,
            'uploaded'      => 0,
            'downloaded'    => 1024,
            'left'          => 0,
            'ip'            => self::SEED_IP
        ) ) );

        $core->announce( new Config\Simple( array(
            'info_hash'     => self::INFO_HASH,
            'peer_id'       => self::SEED_PEER_ID,
            'port'          => self::CLIENT_PORT,
            'uploaded'      => 0,
            'downloaded'    => 7168,
            'left'          => 6144,
            'event'         => 'completed',
            'ip'            => self::SEED_IP
        ) ) );

        // Announcing a leecher.
        $core->announce( new Config\Simple( array(
            'info_hash'     => self::INFO_HASH,
            'peer_id'       => self::LEECH_PEER_ID,
            'port'          => self::CLIENT_PORT,
            'uploaded'      => 1024,
            'downloaded'    => 2048,
            'left'          => 4096,
            'ip'            => self::LEECH_IP
        ) ) );
    }
}