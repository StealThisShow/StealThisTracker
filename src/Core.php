<?php

namespace StealThisShow\StealThisTracker;

/**
 * Public interface to access some Bittorrent actions like creating torrent file and announcing peer.
 *
 * @package StealThisTracker
 */
class Core
{
    /**
     * Configuration of this class.
     *
     * @var Config\ConfigInterface
     */
    protected $config;

    /**
     * Persistence class to save/retrieve data.
     *
     * @var Persistence\PersistenceInterface
     */
    protected $persistence;

    /**
     * Intializing the object with the config.
     *
     * @param Config\ConfigInterface $config
     */
    public function  __construct( Config\ConfigInterface $config )
    {
        $this->config       = $config;
        $this->persistence  = $this->config->get( 'persistence' );
    }

    /**
     * Creates a string representing a .torrent file.
     *
     * @param string $file_path Full path of the file to use to generate torrent file (will be opened and hashed).
     * @param integer $size_piece Size of one piece in bytes. Normally a power of 2, defaults to 256KB.
     * @param string $basename
     * @return string When the announce-list is empty.
     * @throws Error
     */
    public function createTorrent( $file_path, $size_piece = 262144, $basename = null )
    {
        $torrent = new Torrent( new File\File( $file_path ), $size_piece, $file_path, $basename );

        $announce = $this->config->get( 'announce' );
        if ( !is_array( $announce ) )
        {
            $announce = array( $announce );
        }
        if ( empty( $announce ) )
        {
            throw new Error( 'Empty announce list!' );
        }

        $this->persistence->saveTorrent( $torrent );

        return $torrent->createTorrentFile( $announce );
    }

    /**
     * Announce a peer to be tracked and return message to the client.
     *
     * This methods needs 'interval' key to be set in the config of the class
     * (not i the GET!). This is a number representing seconds for the client to
     * wait for the next announcement.
     *
     * Optional config key 'load_balancing' (ON by defailt) adds 10% dispersion
     * to the interval value to avoid possible announce peeks.
     *
     * @param Config\ConfigInterface $get Config-like representation of the CGI parameters (aka. GET) sent.
     * @return string
     */
    public function announce( Config\ConfigInterface $get )
    {
        try
        {
            try
            {
                list( $info_hash, $peer_id, $port, $uploaded, $downloaded, $left ) = $get->getMulti( array(
                    'info_hash',
                    'peer_id',
                    'port',
                    'uploaded',
                    'downloaded',
                    'left',
                ), true );
            }
            catch ( Config\Error\Missing $e )
            {
                return $this->announceFailure( "Invalid get parameters; " . $e->getMessage() );
            }

            // IP address might be set explicitly in the GET.
            $ip         = $get->get( 'ip', false, $this->config->get( 'ip' ) );
            $compact    = $get->get( 'compact', false, 0 );
            $no_peer_id = $get->get( 'no_peer_id', false, 0 );
            $event      = $get->get( 'event', false, '' );

            if ( 20 != strlen( $info_hash ) )
            {
                return $this->announceFailure( "Invalid length of info_hash." );
            }
            if ( 20 != strlen( $peer_id ) )
            {
                return $this->announceFailure( "Invalid length of info_hash." );
            }
            if ( !( is_numeric( $port ) && is_int( $port = $port + 0 ) && 0 <= $port ) )
            {
                return $this->announceFailure( "Invalid port value." );
            }
            if ( !( is_numeric( $uploaded ) && is_int( $uploaded = $uploaded + 0 ) && 0 <= $uploaded ) )
            {
                return $this->announceFailure( "Invalid uploaded value." );
            }
            if ( !( is_numeric( $downloaded ) && is_int( $downloaded = $downloaded + 0 ) && 0 <= $downloaded ) )
            {
                return $this->announceFailure( "Invalid downloaded value." );
            }
            if ( !( is_numeric( $left ) && is_int( $left = $left + 0 ) && 0 <= $left ) )
            {
                return $this->announceFailure( "Invalid left value." );
            }

            $interval       = intval( $this->config->get( 'interval' ) );

            $this->persistence->saveAnnounce(
                $info_hash,
                $peer_id,
                $ip,
                $port,
                $downloaded,
                $uploaded,
                $left,
                ( 'completed' == $event ) ? 'complete' : null, // Only set to complete if client said so.
                ( 'stopped' == $event ) ? 0 : $interval * 2 // If the client gracefully exists, we set its ttl to 0, double-interval otherwise.
            );

            $peers          = $this->persistence->getPeers( $info_hash, $peer_id );
            $peer_stats     = $this->persistence->getPeerStats( $info_hash, $peer_id );

            if ( $compact )
            {
                $peers = $this->compactPeers( $peers );
            }
            elseif ( $no_peer_id )
            {
                $peers = $this->removePeerId( $peers );
            }

            if ( true === $this->config->get( 'load_balancing', false, true ) )
            {
                // Load balancing for tracker announcements.
                $interval = $interval + mt_rand( round( $interval / -10 ), round( $interval / 10 ) );
            }

            $announce_response = array(
                'interval'      => $interval,
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

    /**
     * As per request of the announcing client we might need to compact peers.
     *
     * Compacting means representing the IP in a big-endian long and the port
     * as a big-endian short and concatenating all of them in a string.
     *
     * @see http://wiki.theory.org/BitTorrentSpecification#Tracker_Response
     * @param array $peers List of peers with their IP address and port.
     * @return string
     */
    private function compactPeers( array $peers )
    {
        $compact_peers = "";
        foreach ( $peers as $peer )
        {
            if ( !filter_var( $peer['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
                continue;
            $compact_peers .=
                pack( 'N', ip2long( $peer['ip'] ) ) .
                pack( 'n', $peer['port'] );
        }
        return $compact_peers;
    }
    /**
     * As per request of the announcing client we might need to remove peer IDs.
     *
     * @see http://wiki.theory.org/BitTorrentSpecification#Tracker_Response
     * @param array $peers List of peers with their IP address and port.
     * @return string
     */
    private function removePeerId( array $peers )
    {
        foreach ( $peers as $peer_index => $peer )
        {
            unset( $peers[$peer_index]['peer id'] );
        }
        return $peers;
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