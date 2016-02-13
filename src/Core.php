<?php

namespace StealThisShow\StealThisTracker;

/**
 * Public interface to access some BitTorrent actions like adding a torrent file and a announcing peer.
 *
 * @package StealThisTracker
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
     * Initializing the object with persistence.
     *
     * @param Persistence\PersistenceInterface $persistence
     * @param bool $ip
     * @param int $interval
     * @throws Error
     */
    public function  __construct( Persistence\PersistenceInterface $persistence, $ip = false, $interval = 60 )
    {
        $this->persistence  = $persistence;
        $this->interval     = $interval;
        if ( isset($_SERVER['REMOTE_ADDR']) )
            $this->ip = $_SERVER['REMOTE_ADDR'];
    }

    public function setIp( $ip )
    {
        $this->ip = $ip;
        return $this;
    }

    public function setInterval( $interval )
    {
        $this->interval = $interval;
        return $this;
    }

    /**
     * Adds a Torrent to persistence and returns a string representing a .torrent file.
     * @param Torrent $torrent
     * @return string
     */
    public function addTorrent( Torrent $torrent )
    {
        $this->persistence->saveTorrent( $torrent );
        return $torrent->createTorrentFile();
    }

    /**
     * Announce a peer to be tracked and return message to the client.
     *
     * @param array $get $_GET
     * @return string
     */
    public function announce( array $get )
    {
        try
        {
            $mandatory_keys = array(
                'info_hash',
                'peer_id',
                'port',
                'uploaded',
                'downloaded',
                'left',
            );
            $missing_keys = array_diff( $mandatory_keys, array_keys( $get ) );
            if ( !empty( $missing_keys ) )
                return $this->announceFailure( "Invalid get parameters; Missing: " . implode( ', ', $missing_keys ) );

            // IP address might come from $_GET.
            $ip         = isset( $get['ip'] )           ? $get['ip']            : $this->ip;
            $event      = isset( $get['event'] )        ? $get['event']         : '';
            $compact    = isset( $get['compact'] )      ? $get['compact']       : false;
            $no_peer_id = isset( $get['no_peer_id'] )   ? $get['no_peer_id']    : false;

            if ( !filter_var( $ip, FILTER_VALIDATE_IP ) )
                return $this->announceFailure( "Invalid IP-address" );
            if ( 20 != strlen( $get['info_hash'] ) )
                return $this->announceFailure( "Invalid length of info_hash." );
            if ( 20 != strlen( $get['peer_id'] ) )
                return $this->announceFailure( "Invalid length of peer_id." );
            if ( !Utils::isNonNegativeInteger( $get['port'] ) )
                return $this->announceFailure( "Invalid port value." );
            if ( !Utils::isNonNegativeInteger( $get['uploaded'] ) )
                return $this->announceFailure( "Invalid uploaded value." );
            if ( !Utils::isNonNegativeInteger( $get['downloaded'] ) )
                return $this->announceFailure( "Invalid downloaded value." );
            if ( !Utils::isNonNegativeInteger( $get['left'] ) )
                return $this->announceFailure( "Invalid left value." );

            $this->persistence->saveAnnounce(
                $get['info_hash'],
                $get['peer_id'],
                $ip,
                $get['port'],
                $get['downloaded'],
                $get['uploaded'],
                $get['left'],
                ( 'completed' == $event ) ? 'complete' : null, // Only set to complete if client said so.
                ( 'stopped' == $event ) ? 0 : $this->interval * 2 // If the client gracefully exists, we set its ttl to 0, double-interval otherwise.
            );

            $peers          = Utils::applyPeerFilters($this->persistence->getPeers( $get['info_hash'], $get['peer_id'] ), $compact, $no_peer_id );
            $peer_stats     = $this->persistence->getPeerStats( $get['info_hash'], $get['peer_id'] );

            $announce_response = array(
                'interval'      => $this->interval,
                'complete'      => intval( $peer_stats['complete'] ),
                'incomplete'    => intval( $peer_stats['incomplete'] ),
                'peers'         => $peers,
            );

            return Bencode\Builder::build( $announce_response );
        }
        catch ( Error $e )
        {
            trigger_error( 'Failure while announcing: ' . $e->getMessage(), E_USER_WARNING );
            return $this->announceFailure( "Failed to announce because of internal server error." );
        }
    }

    public function scrape()
    {
        // TODO: Implement scrape
    }

    /**
     * Creates a bencoded announce failure message.
     *
     * @param string $message Public description of the failure.
     * @return string
     */
    protected function announceFailure( $message )
    {
        return Bencode\Builder::build( array(
            'failure reason' => $message
        ) );
    }
}
