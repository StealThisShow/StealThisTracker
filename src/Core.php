<?php

namespace StealThisShow\StealThisTracker;

/**
 * Public interface to access some BitTorrent actions 
 * like adding a torrent file, announcing or scraping.
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class Core
{

    /**
     * Persistence class to save/retrieve data.
     *
     * @var Persistence\PersistenceInterface
     */
    protected $persistence;

    /**
     * The IP-address of the peer
     *
     * @var string
     */
    protected $ip;

    /**
     * The announce interval
     *
     * @var integer
     */
    protected $interval;

    /**
     * Required $_GET keys for announce
     *
     * @var array
     */
    protected static $announce_mandatory_keys = array(
        'info_hash',
        'peer_id',
        'port',
        'uploaded',
        'downloaded',
        'left'
    );

    /**
     * Required $_GET keys for scrape
     *
     * @var array
     */
    protected static $scrape_mandatory_keys = array(
        'info_hash'
    );

    /**
     * Initializing the object with persistence.
     *
     * @param Persistence\PersistenceInterface $persistence Persistence
     * @param string                           $ip          IP-address
     * @param int                              $interval    Interval
     * 
     * @throws Error
     */
    public function __construct(
        Persistence\PersistenceInterface $persistence, $ip = null, $interval = 60
    ) {
        $this->persistence  = $persistence;
        $this->interval     = $interval;
        $this->ip           = $ip;
    }

    /**
     * Sets IP
     *
     * @param string $ip IP
     *
     * @return $this
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * Sets interval
     *
     * @param int $interval Interval
     *
     * @return $this
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;
        return $this;
    }

    /**
     * Adds a Torrent to persistence and returns
     * a string representing a .torrent file.
     *
     * @param Torrent $torrent Torrent
     *
     * @return string
     */
    public function addTorrent(Torrent $torrent)
    {
        $this->persistence->saveTorrent($torrent);
        return $torrent->createTorrentFile();
    }

    /**
     * Announce a peer to be tracked and return message to the client.
     *
     * @param array $get $_GET
     *
     * @return Bencode\Value\AbstractValue
     */
    public function announce(array $get)
    {
        try
        {
            // Set IP
            if (!isset($get['ip']) && empty($this->ip)) {
                $get['ip'] = $_SERVER['REMOTE_ADDR'];
            } elseif (!isset($get['ip'])) {
                $get['ip'] = $this->ip;
            }

            if ($failure = $this->isInvalidAnnounceRequest($get)) {
                return $failure;
            }

            $event      = isset($get['event']) ? $get['event'] : '';
            $compact    = isset($get['compact']) ? $get['compact'] : false;
            $no_peer_id = isset($get['no_peer_id']) ? $get['no_peer_id'] : false;

            $this->addAnnounce($get, $event);

            $peers = Utils::applyPeerFilters(
                $this->persistence->getPeers(
                    $get['info_hash'],
                    $get['peer_id']
                ),
                $compact, $no_peer_id
            );
            $peer_stats = $this->persistence->getPeerStats(
                $get['info_hash'],
                $get['peer_id']
            );

            $announce_response = array(
                'interval'      => $this->interval,
                'complete'      => intval($peer_stats['complete']),
                'incomplete'    => intval($peer_stats['incomplete']),
                'peers'         => $peers,
            );

            return Bencode\Builder::build($announce_response);
        } catch (Error $e) {
            trigger_error(
                'Failure while announcing: ' . $e->getMessage(),
                E_USER_WARNING
            );
            return $this->trackerFailure(
                "Failed to announce because of internal server error."
            );
        }
    }

    /**
     * Checks announce request parameters
     *
     * @param array $get $_GET
     *
     * @return bool|Bencode\Value\AbstractValue
     */
    protected function isInvalidAnnounceRequest(array $get)
    {
        if ($failure = $this->isMissingKeys(self::$announce_mandatory_keys, $get)
        ) {
            return $failure;
        }
        if (!filter_var($get['ip'], FILTER_VALIDATE_IP)) {
            return $this->trackerFailure("Invalid IP-address");
        }
        if (20 != strlen($get['info_hash'])) {
            return $this->trackerFailure("Invalid length of info_hash.");
        }
        if (20 != strlen($get['peer_id'])) {
            return $this->trackerFailure("Invalid length of peer_id.");
        }
        if (!Utils::isNonNegativeInteger($get['port'])) {
            return $this->trackerFailure("Invalid port value.");
        }
        if (!Utils::isNonNegativeInteger($get['uploaded'])) {
            return $this->trackerFailure("Invalid uploaded value.");
        }
        if (!Utils::isNonNegativeInteger($get['downloaded'])) {
            return $this->trackerFailure("Invalid downloaded value.");
        }
        if (!Utils::isNonNegativeInteger($get['left'])) {
            return $this->trackerFailure("Invalid left value.");
        }
        if (!$this->persistence->hasTorrent($get['info_hash'])) {
            return $this->trackerFailure("Torrent does not exist.");
        }

        return false;
    }

    /**
     * Checks missing keys
     *
     * @param array $mandatory Mandatory keys
     * @param array $get       The $_GET request
     *
     * @return bool|Bencode\Value\AbstractValue
     */
    protected function isMissingKeys(array $mandatory, array $get)
    {
        $missing_keys = array_diff($mandatory, array_keys($get));
        if (!empty($missing_keys)) {
            return $this->trackerFailure(
                "Invalid get parameters; Missing: " .
                implode(', ', $missing_keys)
            );
        }
        return false;
    }

    /**
     * Stores the announce in persistence
     *
     * @param array  $get   $_GET
     * @param string $event The event
     *
     * @return void
     */
    protected function addAnnounce(array $get, $event)
    {
        $this->persistence->saveAnnounce(
            $get['info_hash'],
            $get['peer_id'],
            $get['ip'],
            $get['port'],
            $get['downloaded'],
            $get['uploaded'],
            $get['left'],
            // Only set to complete if client said so.
            ('completed' == $event) ? 'complete' : null,
            // If the client gracefully exists, we set its ttl to 0,
            // double-interval otherwise.
            ('stopped' == $event) ? 0 : $this->interval * 2
        );
    }

    /**
     * Scrape
     *
     * Currently info_hash is required
     *
     * @param array $get $_GET
     *
     * @return Bencode\Value\AbstractValue
     */
    public function scrape(array $get)
    {
        try {
            // Set peer_id
            if (!isset($get['peer_id'])) {
                $get['peer_id'] = '';
            }

            if ($failure = $this->isInvalidScrapeRequest($get)) {
                return $failure;
            }

            $peer_stats = $this->persistence->getPeerStats(
                $get['info_hash'],
                $get['peer_id']
            );

            $scrape_response = array(
                'files' => array(
                    $peer_stats['info_hash'] => array(
                        'complete'      => intval($peer_stats['complete']),
                        'incomplete'    => intval($peer_stats['incomplete']),
                        'downloaded'    => intval($peer_stats['downloaded'])
                    )
                )
            );

            return Bencode\Builder::build($scrape_response);
        } catch (Error $e) {
            trigger_error(
                'Failure while scraping: ' . $e->getMessage(),
                E_USER_WARNING
            );
            return $this->trackerFailure(
                "Failed to scrape because of internal server error."
            );
        }
    }

    /**
     * Checks scrape request parameters
     *
     * @param array $get $_GET
     *
     * @return bool|Bencode\Value\AbstractValue
     */
    protected function isInvalidScrapeRequest(array $get)
    {
        if ($failure = $this->isMissingKeys(self::$scrape_mandatory_keys, $get)
        ) {
            return $failure;
        }
        if (20 != strlen($get['info_hash'])) {
            return $this->trackerFailure("Invalid length of info_hash.");
        }
        if (isset($get['peer_id'])
            && $get['peer_id'] !== ''
            && 20 != strlen($get['peer_id'])
        ) {
            return $this->trackerFailure("Invalid length of peer_id.");
        }
        if (!$this->persistence->hasTorrent($get['info_hash'])) {
            return $this->trackerFailure("Torrent does not exist.");
        }

        return false;
    }

    /**
     * Creates a bencoded tracker failure message.
     *
     * @param string $message Public description of the failure.
     *
     * @return Bencode\Value\AbstractValue
     */
    protected function trackerFailure($message)
    {
        return Bencode\Builder::build(
            array(
                'failure reason' => $message
            )
        );
    }
}
