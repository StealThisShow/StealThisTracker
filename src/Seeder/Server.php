<?php

namespace StealThisShow\StealThisTracker\Seeder;

use StealThisShow\StealThisTracker\Concurrency;
use StealThisShow\StealThisTracker\Logger;
use StealThisShow\StealThisTracker\Persistence;

/**
 * Starts seeding server.
 *
 * Creates 2 different forks from itself. The first starts the peer server
 * (creating its own forks), the second will make announce the peer regularly.
 *
 * @package    StealThisTracker
 * @subpackage Seeder
 * @author     StealThisShow <info@stealthisshow.com>
 * @license    https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
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
    const ANNOUNCE_INTERVAL = 30;

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
     * @param Peer                             $peer        Peer
     * @param Persistence\PersistenceInterface $persistence Persistence
     * @param Logger\LoggerInterface           $logger      Logger
     */
    public function __construct(
        Peer $peer,
        Persistence\PersistenceInterface $persistence,
        Logger\LoggerInterface $logger = null
    ) {
        // It's a daemon
        set_time_limit(0);

        $this->peer         = $peer;
        $this->persistence  = $persistence;
        $this->logger       = $logger;

        if (!$logger) {
            $this->logger = new Logger\Blackhole();
        }
    }

    /**
     * Called before forking children,
     * initializes the object and sets up listening socket.
     *
     * @return Number of forks to create.
     *         If negative, forks are recreated
     *         when exiting and absolute values is used.
     */
    public function startParentProcess()
    {
        // We need 2 processes to run permanently
        // (minus means permanently recreated).
        return -2;
    }

    /**
     * Called on child processes after forking.
     *
     * For slot 0: Starts seeding peer.
     * For slot 1: Starts announcing loop.
     *
     * @param integer $slot The slot (numbered index) of the fork.
     *                      Reused when recreating process.
     *
     * @throws Error
     * @return void
     */
    public function startChildProcess($slot)
    {
        if ($this->persistence instanceof Persistence\ResetWhenForking) {
            $this->persistence->resetAfterForking();
        }

        switch ($slot) {
            case 0:
                $this->peer->start();
                break;
            case 1:
                $this->announce();
                break;
            default:
                throw new Error('Invalid process slot while running seeder server.');
        }
    }

    /**
     * Save announce for all the torrents in the database
     * so clients know where to connect.
     *
     * This method runs in infinite loop repeating
     * announcing every self::ANNOUNCE_INTERVAL seconds.
     *
     * @return void
     */
    protected function announce()
    {
        $persistence    = $this->persistence;
        $iterations     = 0;

        do {
            $all_torrents = $persistence->getAllInfoHash();

            foreach ($all_torrents as $torrent_info) {
                $persistence->saveAnnounce(
                    $torrent_info['info_hash'],
                    $this->peer->getPeerId(),
                    $this->peer->getExternalAddress(),
                    $this->peer->getPort(),
                    $torrent_info['length'], 0, 0, 'complete',
                    self::ANNOUNCE_INTERVAL
                );
            }

            $this->logger->logMessage(
                'Seeder server announced itself for ' .
                count($all_torrents) .
                " torrents at address {$this->peer->getExternalAddress()}:" .
                "{$this->peer->getPort()} (announces every " .
                self::ANNOUNCE_INTERVAL . 's).'
            );

            sleep(self::ANNOUNCE_INTERVAL);
            // Memory leak prevention, see self::STOP_AFTER_ITERATIONS.
        } while (++$iterations < self::STOP_AFTER_ITERATIONS);

        $this->logger->logMessage(
            'Announce process restarts to prevent memory leaks.'
        );
        exit(0);
    }

    /**
     * Set logger
     *
     * @param Logger\LoggerInterface $logger Logger
     *
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
