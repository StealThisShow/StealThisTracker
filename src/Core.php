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
            $get['ip'] = $this->getAnnounceIp($get);

            if ($failure = $this->isInvalidAnnounceRequest($get)) {
                return $failure;
            }

            $response = $this->addAnnounce($get);

            return Bencode\Builder::build($response);
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
     * Get IP-address
     *
     * @param array $get $_GET
     *
     * @return string
     */
    protected function getAnnounceIp(array $get)
    {
        if (isset($get['ip'])) {
            return $get['ip'];
        } elseif (empty($this->ip)) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return $this->ip;

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
        } elseif (!filter_var($get['ip'], FILTER_VALIDATE_IP)) {
            return $this->trackerFailure("Invalid IP-address");
        } elseif (20 != strlen($get['info_hash'])) {
            return $this->trackerFailure("Invalid length of info_hash.");
        } elseif (20 != strlen($get['peer_id'])) {
            return $this->trackerFailure("Invalid length of peer_id.");
        } elseif (!Utils::isNonNegativeInteger($get['port'])) {
            return $this->trackerFailure("Invalid port value.");
        } elseif (!Utils::isNonNegativeInteger($get['uploaded'])) {
            return $this->trackerFailure("Invalid uploaded value.");
        } elseif (!Utils::isNonNegativeInteger($get['downloaded'])) {
            return $this->trackerFailure("Invalid downloaded value.");
        } elseif (!Utils::isNonNegativeInteger($get['left'])) {
            return $this->trackerFailure("Invalid left value.");
        } elseif (!$this->persistence->hasTorrent($get['info_hash'])) {
            return $this->trackerFailure("Torrent does not exist.");
        }

        return false;
    }

    /**
     * Stores the announce in persistence
     *
     * @param array $get $_GET
     *
     * @return array
     */
    protected function addAnnounce(array $get)
    {
        $event      = isset($get['event']) ? $get['event'] : '';
        $compact    = isset($get['compact']) ? $get['compact'] : false;
        $no_peer_id = isset($get['no_peer_id']) ? $get['no_peer_id'] : false;

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

        return $this->getAnnounceResponse($get, $compact, $no_peer_id);
    }

    /**
     * Get announce response
     *
     * @param array $get        $_GET
     * @param bool  $compact    Compact
     * @param bool  $no_peer_id No peer ID
     *
     * @return array
     */
    protected function getAnnounceResponse(array $get, $compact, $no_peer_id)
    {
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

        return array(
            'interval'      => $this->interval,
            'complete'      => intval($peer_stats['complete']),
            'incomplete'    => intval($peer_stats['incomplete']),
            'peers'         => $peers,
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
            if ($failure = $this->isInvalidScrapeRequest($get)) {
                return $failure;
            }

            $response = $this->getScrapeResponse($get);

            return Bencode\Builder::build($response);
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
        } elseif (20 != strlen($get['info_hash'])) {
            return $this->trackerFailure("Invalid length of info_hash.");
        } elseif (!$this->persistence->hasTorrent($get['info_hash'])) {
            return $this->trackerFailure("Torrent does not exist.");
        }

        return false;
    }

    /**
     * Get scrape response
     *
     * @param array $get $_GET
     *
     * @return array
     */
    protected function getScrapeResponse(array $get)
    {
        $peer_stats = $this->persistence->getPeerStats(
            $get['info_hash']
        );

        return array(
            'files' => array(
                $peer_stats['info_hash'] => array(
                    'complete'      => intval($peer_stats['complete']),
                    'incomplete'    => intval($peer_stats['incomplete']),
                    'downloaded'    => intval($peer_stats['downloaded'])
                )
            )
        );
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
}
