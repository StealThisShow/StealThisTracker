<?php

namespace StealThisShow\StealThisTracker\Seeder;

use StealThisShow\StealThisTracker\Concurrency;
use StealThisShow\StealThisTracker\Logger;
use StealThisShow\StealThisTracker\Persistence;

/**
 * Starts seeding server.
 *
 * Creates 2 different forks from itself. The first starts the peer server
 * (creating its own forks), the second will make anounce the peer regularly.
 *
 * @package StealThisTracker
 * @subpackage Seeder
 */
class Server extends Concurrency\Forker
{

    /**
     * Holds persistence
     *
     * @var Persistence\PersistenceInterface
     */
    protected $persistence;

    /**
     * Peer object instance to use in this server.
     *
     * @var Peer
     */
    protected $peer;

    /**
     * Logger object used to log messages and errors in this class.
     *
     * @var Logger\LoggerInterface
     */
    protected $logger;

    /**
     * Interval for doing announcements to the database.
     *
     * Be careful with the timeout of DB connections!
     */
    const ANNOUNCE_INTERVAL     = 30;

    /**
     * To prevent possible memory leaks, every fork terminates after X iterations.
     *
     * The fork is automatically recreated by the parent process, so nothing changes.
     * In our case one iterations means one announcement to the database.
     * Peer object forks its own processes and has its own memory leaking prevention.
     */
    const STOP_AFTER_ITERATIONS = 20;

    /**
     * Initializes the object
     *
     * @param Peer $peer
     * @param Persistence\PersistenceInterface $persistence
     * @param Logger\LoggerInterface $logger
     */
    public function  __construct( Peer $peer, Persistence\PersistenceInterface $persistence, Logger\LoggerInterface $logger = false)
    {
        // It's a daemon, right?
        set_time_limit( 0 );

        $this->peer         = $peer;
        $this->persistence  = $persistence;

        if ( !$logger )
            $logger = new Logger\Blackhole();

        $this->logger = $logger;
    }

    /**
     * Called before forking children, initializes the object and sets up listening socket.
     *
     * @return Number of forks to create. If negative, forks are recreated when exiting and absolute values is used.
     */
    public function startParentProcess()
    {
        return -2; // We need 2 processes to run permanently (minus means permanently recreated).
    }

    /**
     * Called on child processes after forking.
     *
     * For slot 0: Starts seeding peer.
     * For slot 1: Starts announcing loop.
     *
     * @param integer $slot The slot (numbered index) of the fork. Reused when recreating process.
     * @throws Error
     */
    public function startChildProcess( $slot )
    {
        if ( $this->persistence instanceof Persistence\ResetWhenForking )
            $this->persistence->resetAfterForking();

        switch( $slot )
        {
            case 0:
                $this->peer->start();
                break;
            case 1:
                $this->announce();
                break;
            default:
                throw new Error( 'Invalid process slot while running seeder server.' );
        }
    }

    /**
     * Save announce for all the torrents in the database so clients know where to connect.
     *
     * This method runs in infinite loop repeating announcing every self::ANNOUNCE_INTERVAL seconds.
     */
    protected function announce()
    {
        $persistence    = $this->persistence;
        $iterations     = 0;

        do
        {
            $all_torrents = $persistence->getAllInfoHash();

            foreach ( $all_torrents as $torrent_info )
                $persistence->saveAnnounce( $torrent_info['info_hash'], $this->peer->peer_id, $this->peer->external_address, $this->peer->port, $torrent_info['length'], 0, 0, 'complete', self::ANNOUNCE_INTERVAL );

            $this->logger->logMessage( 'Seeder server announced itself for ' . count( $all_torrents ) . " torrents at address {$this->peer->external_address}:{$this->peer->port} (announces every " . self::ANNOUNCE_INTERVAL . 's).' );

            sleep( self::ANNOUNCE_INTERVAL );
        } while ( ++$iterations < self::STOP_AFTER_ITERATIONS ); // Memory leak prevention, see self::STOP_AFTER_ITERATIONS.

        $this->logger->logMessage( 'Announce process restarts to prevent memory leaks.' );
        exit( 0 );
    }

    /**
     * @param Logger\LoggerInterface $logger
     * @return Server
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
