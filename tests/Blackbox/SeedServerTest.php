<?php

declare(ticks = 1);

namespace StealThisShow\StealThisTracker;

/**
 * Seed Server Test
 *
 * @package StealThisTracker
 */
class SeedServerTest extends \PHPUnit_Framework_TestCase
{
    protected $db_path;

    protected $sql_path;

    const SEED_SERVER_IP        = '127.0.0.1';
    const SEED_SERVER_PORT      = 1988;
    const ANNOUNCE_SERVER_IP    = '127.0.0.1';
    const ANNOUNCE_SERVER_PORT  = 80;
    const FILE_TO_DOWNLOAD      = 'cookie_monster.gif';
    const PIECE_LENGTH          = 524288;
    const TEST_TIMEOUT          = 120;

    protected $parent_pid;

    protected $seed_server_pid;

    protected $torrent_client_pid;

    protected $torrent_file;

    protected $download_destination;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        $this->parent_pid = posix_getpid();

        $this->db_path = sys_get_temp_dir() . '/sqlite_test.db';
        $this->sql_path = dirname(__FILE__) . '/../Fixtures/sqlite.sql';
        touch($this->db_path);
        $this->setupDatabaseFixture($this->db_path, $this->sql_path);
    }

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        if ($this->parent_pid != posix_getpid()) {
            // We are in a child so no tear down is needed.
            return;
        }

        if (file_exists($this->db_path)) {
            unlink($this->db_path);
        }

        if (file_exists($this->torrent_file)) {
            unlink($this->torrent_file);
        }

        if (file_exists($this->download_destination)) {
            shell_exec('rm -rf ' . escapeshellarg($this->download_destination));
        }

        if (isset($this->seed_server_pid)) {
            posix_kill($this->seed_server_pid, SIGTERM);
        }

        if (isset($this->torrent_client_pid)) {
            posix_kill($this->torrent_client_pid, SIGTERM);
        }
    }

    /**
     * Set-up SQLite database
     *
     * @param string $db_file  The DB file
     * @param string $sql_file The SQL file
     *
     * @return void
     */
    protected function setupDatabaseFixture($db_file, $sql_file)
    {
        $table_definitions = file_get_contents($sql_file);
        $driver = new \PDO('sqlite:' . $db_file);
        $statements = preg_split(
            '/;[ \t]*\n/', $table_definitions, -1, PREG_SPLIT_NO_EMPTY
        );
        foreach ($statements as $statement) {
            if (!$driver->query($statement)) {
                $this->fail(
                    'Could not set up database fixture: ' .
                    var_export($driver->errorInfo(), true)
                );
            }
        }
    }

    /**
     * Test ping announce server
     *
     * @return void
     */
    public function testPingAnnounceServer()
    {
        $this->assertEquals(
            "pong",
            file_get_contents(
                "http://" . self::ANNOUNCE_SERVER_IP .
                ":" . self::ANNOUNCE_SERVER_PORT . "/ping.php"
            )
        );
    }

    /**
     * Test seeding
     *
     * @return void
     */
    public function testSeeding()
    {
        $this->torrent_file           = $this->createTorrentFile();
        $this->download_destination   = $this->createDownloadDestination();
        $this->seed_server_pid        = $this->startSeedServer();
        $this->torrent_client_pid     = $this->startTorrentClient();

        // We don't want to wait forever.
        $self = $this;
        pcntl_signal(
            SIGALRM,
            function () use ($self) {
                $self->fail('Test timed out.');
            }
        );
        pcntl_alarm(self::TEST_TIMEOUT);

        $pid_exit = pcntl_wait($status);

        switch($pid_exit) {
            case -1:
                $this->fail('Error in child processes.');
                break;
            case $this->seed_server_pid:
                unset($this->seed_server_pid);
                $this->fail('Seed server exited.');
                break;
            case $this->torrent_client_pid:
                unset($this->torrent_client_pid);
                break;
        }

        $download_path = $this->download_destination .
            '/' . self::FILE_TO_DOWNLOAD;

        $this->assertFileExists($download_path);

        $downloaded_hash    = sha1_file($download_path);
        $expected_hash      = sha1_file(
            dirname(__FILE__) . '/../Fixtures/' . self::FILE_TO_DOWNLOAD
        );

        $this->assertEquals($expected_hash, $downloaded_hash);
    }

    /**
     * Starts seed server to seed the file to the torrent client.
     * Needs PHP in your path.
     *
     * @return int
     */
    protected function startSeedServer()
    {
        return $this->spawn(
            self::findExecutable('php'),
            array(
                dirname(__FILE__) . "/../Fixtures/seed.php",
                self::SEED_SERVER_IP,
                self::SEED_SERVER_PORT
            )
        );
    }

    /**
     * Starts torrent client to download the file we generated torrent
     * file for.
     *
     * Needs ctorrent to be installed and in your path.
     *
     * @return int
     */
    protected function startTorrentClient()
    {
        return $this->spawn(
            self::findExecutable('ctorrent'),
            array(
                "-E0",
                "-e0", // Exiting without seeding.
                "-s".
                $this->download_destination .
                '/' .
                self::FILE_TO_DOWNLOAD,
                $this->torrent_file,
            )
        );
    }

    /**
     * Spawn
     *
     * @param string $program   Program
     * @param array  $arguments Arguments
     *
     * @return int
     */
    protected function spawn($program, array $arguments)
    {
        // PHP-style spawn...
        $pid = pcntl_fork();

        if ($pid < 0) {
            $this->fail("Couldn't spawn: $pid");
        }

        if ($pid > 0) {
            return $pid;
        }

        pcntl_exec($program, $arguments);

        // We are in the child, we can finish here.
        // The program only gets here in case of an error.
        die();
    }

    /**
     * Create torrent file
     *
     * @return string
     */
    protected function createTorrentFile()
    {
        $persistence = new Persistence\Pdo('sqlite:' . $this->db_path);

        $core = new Core($persistence);

        $file = new File\File(
            dirname(__FILE__) . '/../Fixtures/' . self::FILE_TO_DOWNLOAD
        );

        $torrent = new Torrent($file, self::PIECE_LENGTH);
        $torrent->setAnnounceList(
            array(
                'http://' . self::ANNOUNCE_SERVER_IP . ':' .
                self::ANNOUNCE_SERVER_PORT . '/announce.php'
            )
        );

        $contents = $core->addTorrent($torrent);

        $file_name = sys_get_temp_dir() .
            "/phptracker_torrent" . uniqid() . '.torrent';
        file_put_contents($file_name, $contents);

        return $file_name;
    }

    /**
     * Create download destinations
     *
     * @return string
     */
    protected function createDownloadDestination()
    {
        $path = sys_get_temp_dir() . "/phptracker_downloads" . uniqid();
        mkdir($path, 0777, true);

        return realpath($path);
    }

    /**
     * Find executable
     *
     * @param string $command_in_path Command
     *
     * @return string
     */
    protected static function findExecutable($command_in_path)
    {
        return rtrim(shell_exec('which ' . $command_in_path), "\n");
    }
}