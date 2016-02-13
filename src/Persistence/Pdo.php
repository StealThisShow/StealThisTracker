<?php

namespace StealThisShow\StealThisTracker\Persistence;

use StealThisShow\StealThisTracker\File\File;
use StealThisShow\StealThisTracker\Torrent;

/**
 * Persistence class implementation using PDO and so supporting many database drivers.
 *
 * @package StealThisTracker
 * @subpackage Persistence
 */
class Pdo implements PersistenceInterface, ResetWhenForking
{
    /**
     * @var \PDO
     */
    protected $driver;

    /**
     * @var string
     */
    protected $dsn;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $options;

    /**
     * Setting up object instance.
     *
     * @param string $dsn
     * @param null $username
     * @param null $password
     * @param array $options
     */
    public function __construct( $dsn, $username = null, $password = null, array $options = array() )
    {
        $this->dsn      = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options  = $options;
    }

    public function setUsername( $username )
    {
        $this->username = $username;
        return $this;
    }

    public function setPassword( $password )
    {
        $this->password = $password;
        return $this;
    }

    public function setOptions( array $options )
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Save all available info of a Torrent object to be able to recreate it.
     *
     * Uses info_hash property as primary key and overwrite attributes when saved
     * multiple times with the same info hash.
     *
     * @param Torrent $torrent
     */
    public function saveTorrent( Torrent $torrent )
    {

        $sql = <<<SQL
SELECT
    1
FROM
    `stealthistracker_torrents`
WHERE
    `info_hash` = :info_hash
SQL;
        $statement = $this->query( $sql, array( ':info_hash' => $torrent->info_hash ) );

        if ( $statement->fetchColumn( 0 ) )
        {
            $sql = <<<SQL
UPDATE
    `stealthistracker_torrents`
SET
    `info_hash`     = :info_hash,
    `length`        = :length,
    `pieces_length` = :pieces_length,
    `pieces`        = :pieces,
    `name`          = :name,
    `path`          = :path,
    `private`       = :private,
    `announce_list` = :announce_list,
    `nodes`         = :nodes,
    `url_list`      = :url_list
WHERE
    `info_hash` = :info_hash
SQL;
        }
        else
        {
            $sql = <<<SQL
INSERT INTO
    `stealthistracker_torrents`
(
    `info_hash`,
    `length`,
    `pieces_length`,
    `pieces`,
    `name`,
    `path`,
    `private`,
    `announce_list`,
    `nodes`,
    `url_list`
)
VALUES
(
    :info_hash,
    :length,
    :pieces_length,
    :pieces,
    :name,
    :path,
    :private,
    :announce_list,
    :nodes,
    :url_list
)
SQL;
        }

        $this->query( $sql, array(
            ':info_hash'         => $torrent->info_hash,
            ':length'            => $torrent->length,
            ':pieces_length'     => $torrent->size_piece,
            ':pieces'            => $torrent->pieces,
            ':name'              => $torrent->name,
            ':path'              => $torrent->file_path,
            ':private'           => $torrent->private,
            ':announce_list'     => serialize( $torrent->announce_list ),
            ':nodes'             => serialize( $torrent->nodes ),
            ':url_list'          => serialize( $torrent->url_list )
        ) );
    }

    /**
     * Given a 20 bytes info hash, returns an initialized Torrent object.
     *
     * Must return null if the info hash is not found.
     *
     * @param string $info_hash
     * @return Torrent
     */
    public function getTorrent( $info_hash )
    {
        $sql = <<<SQL
SELECT
    `info_hash`,
    `length`,
    `pieces_length`,
    `pieces`,
    `name`,
    `path`,
    `private`,
    `announce_list`,
    `nodes`,
    `url_list`
FROM
    `stealthistracker_torrents`
WHERE
    `info_hash` = :info_hash
    AND
    `status` = 'active'
SQL;

        $statement = $this->query( $sql, array(
            ':info_hash' => $info_hash,
        ) );

        $row = $statement->fetch();

        if ( $row )
        {
            return ( new Torrent( new File( $row['path'] ), $row['pieces_length'] ) )
                ->setFilePath( $row['path'] )
                ->setName( $row['name'] )
                ->setLength( $row['length'] )
                ->setPieces( $row['pieces'] )
                ->setPrivate( $row['private'] )
                ->setAnnounceList( unserialize( $row['announce_list'] ) )
                ->setNodes( unserialize( $row['nodes'] ) )
                ->setUrlList( unserialize( $row['url_list'] ) )
                ->setInfoHash( $row['info_hash'] );
        }
        return null;
    }

    /**
     * Saves peer announcement from a client.
     *
     * Majority of the parameters of this method come from GET.
     *
     * @param string $info_hash 20 bytes info hash of the announced torrent.
     * @param string $peer_id 20 bytes peer ID of the announcing peer.
     * @param string $ip Dotted IP address of the client.
     * @param integer $port Port number of the client.
     * @param integer $downloaded Already downloaded bytes.
     * @param integer $uploaded Already uploaded bytes.
     * @param integer $left Bytes left to download.
     * @param string $status Can be complete, incomplete or NULL. Incomplete is default for new rows. If once set to complete, NULL does not set it back on update.
     * @param integer $ttl Time to live in seconds meaning the time after we should consider peer offline (if no more updates come).
     */
    public function saveAnnounce( $info_hash, $peer_id, $ip, $port, $downloaded, $uploaded, $left, $status, $ttl )
    {
        $sql = <<<SQL
SELECT
    1
FROM
    `stealthistracker_peers`
WHERE
    `peer_id`   = :peer_id
    AND
    `info_hash` = :info_hash
SQL;

        $statement = $this->query( $sql, array(
            ':info_hash'    => $info_hash,
            ':peer_id'      => $peer_id,
        ) );

        if ( $statement->fetchColumn( 0 ) )
        {
            $sql = <<<SQL
UPDATE
    `stealthistracker_peers`
SET
    `ip_address`          = :ip,
    `port`                = :port,
    `bytes_downloaded`    = :downloaded,
    `bytes_uploaded`      = :uploaded,
    `bytes_left`          = :left,
    `status`              = COALESCE( :status, `status` ),
    `expires`             = :expires
WHERE
    `peer_id` = :peer_id
    AND
    `info_hash` = :info_hash
SQL;
        }
        else
        {
            $sql = <<<SQL
INSERT INTO
    `stealthistracker_peers`
(
    `info_hash`,
    `peer_id`,
    `ip_address`,
    `port`,
    `bytes_downloaded`,
    `bytes_uploaded`,
    `bytes_left`,
    `status`,
    `expires`
)
VALUES
(
    :info_hash,
    :peer_id,
    :ip,
    :port,
    :downloaded,
    :uploaded,
    :left,
    COALESCE( :status, 'incomplete' ),
    :expires
)
SQL;
        }

        if ( is_null( $ttl ) )
        {
            $ttl = 31536000; // One year.
        }
        $expires = new \DateTime();
        $expires->add( new \DateInterval( 'PT' . $ttl . 'S' ) );

        $this->query( $sql, array(
            ':info_hash'    => $info_hash,
            ':peer_id'      => $peer_id,
            ':ip'           => inet_pton( $ip ),
            ':port'         => $port,
            ':downloaded'   => $uploaded,
            ':uploaded'     => $downloaded,
            ':left'         => $left,
            ':status'       => $status,
            ':expires'      => $expires->format( 'Y-m-d H:i:s' ),
        ) );
    }

    /**
     * Returns all the info_hashes and lengths of the active torrents.
     *
     * @return array An array of arrays having keys 'info_hash' and 'length' accordingly.
     */
    public function getAllInfoHash()
    {
        $sql = <<<SQL
SELECT
    `info_hash`,
    `length`
FROM
    `stealthistracker_torrents`
WHERE
    `status` = 'active'
SQL;

        $statement = $this->query( $sql );

        $data = array();
        while ( $row = $statement->fetch() )
        {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Gets all the active peers for a torrent.
     *
     * Only considers peers which are not expired (see TTL).
     * Returns:
     *
     * array(
     *  array(
     *      'peer_id'   => ... // ID of the peer, if $no_peer_id is false.
     *      'ip'        => ... // Dotted IP address of the peer.
     *      'port'      => ... // Port number of the peer.
     *  )
     * )
     *
     * @param string $info_hash Info hash of the torrent.
     * @param string $peer_id Peer ID to exclude (peer ID of the client announcing).
     * @return array
     */
    public function getPeers( $info_hash, $peer_id )
    {
        $sql = <<<SQL
SELECT
    `peer_id`,
    `ip_address`,
    `port`
FROM
    `stealthistracker_peers`
WHERE
    `info_hash`           = :info_hash
    AND
    `peer_id`             != :peer_id
    AND
    (
        `expires` IS NULL
        OR
        `expires` > :now
    )
SQL;

        $now = new \DateTime();

        $statement = $this->query( $sql, array(
            ':info_hash'    => $info_hash,
            ':peer_id'      => $peer_id,
            ':now'          => $now->format( 'Y-m-d H:i:s' ),
        ));

        $peers = array();
        while ( $row = $statement->fetch() )
        {
            $peers[] = array(
                'peer id'   => $row['peer_id'],
                'ip'        => inet_ntop( $row['ip_address'] ),
                'port'      => $row['port'],
            );
        }

        return $peers;
    }

    /**
     * Returns statistics of seeders and leechers of a torrent.
     *
     * Only considers peers which are not expired (see TTL).
     *
     * @param string $info_hash Info hash of the torrent.
     * @param string $peer_id Peer ID to exclude (peer ID of the client announcing).
     * @return array With keys 'complete' and 'incomplete' having counters for each group.
     */
    public function getPeerStats( $info_hash, $peer_id )
    {
        $sql = <<<SQL
SELECT
    COALESCE( SUM( `status` = 'complete' ), 0 ) AS 'complete',
    COALESCE( SUM( `status` != 'complete' ), 0 ) AS 'incomplete'
FROM
    `stealthistracker_peers`
WHERE
    `info_hash`           = :info_hash
    AND
    `peer_id`             != :peer_id
    AND
    (
        `expires` IS NULL
        OR
        `expires` > :now
    )
SQL;

        $now = new \DateTime();

        $statement = $this->query( $sql, array(
            ':info_hash'    => $info_hash,
            ':peer_id'      => $peer_id,
            ':now'          => $now->format( 'Y-m-d H:i:s' ),
        ));

        $row = $statement->fetch();

        return $row;
    }

    /**
     * If the object is used in a forked child process, this method is called after forking.
     *
     * Re-establishes the connection for the fork.
     *
     * @see StealThisTracker\Persistence\ResetWhenForking
     */
    public function resetAfterForking()
    {
        $this->reconnect();
    }

    /**
     * Reconnects to the database using PDO and returns the new PDO object
     * @return \PDO
     */
    protected function reconnect()
    {
        $this->disconnect();
        return $this->connect();
    }

    /**
     * Disconnects from the database
     * @return void
     */
    protected function disconnect()
    {
        $this->driver = null;
    }

    /**
     * Retuns the existing PDO object, or a new one if we haven't got one yet
     * @return \PDO
     */
    protected function connection()
    {
        return $this->driver instanceof \PDO ? $this->driver : $this->connect();
    }

    /**
     * Connects to the database using PDO and stores and returns the PDO object
     * @return \PDO
     */
    protected function connect()
    {
        $this->driver = new \PDO(
            $this->dsn,
            $this->username,
            $this->password,
            $this->options
        );
        $this->driver->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );

        return $this->driver;
    }

    /**
     * Magic method, passes the call off to the PDO object. If the call throws a
     * PDOException that the server has gone away, and the reconnect flag is set to
     * true, reconnect and try to issue the call to the PDO object again.
     * @param  string $function The function that was called
     * @param  array  $args The arguments supplied
     * @return mixed Whatever PDO::$function($args) returns
     */
    public function __call($function, array $args = array())
    {
        try {
            $result = call_user_func_array(array($this->connection(), $function), $args);
        } catch(\PDOException $e) {
            if ($e->getCode() != 'HY000' || !stristr($e->getMessage(), 'server has gone away')) {
                throw $e;
            }
            $this->reconnect();
            $result = call_user_func_array(array($this->connection(), $function), $args);
        }
        return $result;
    }

    /**
     * Helper method for preparing a PDO statement, binding parameters and executing it
     * @param  string $sql
     * @param  array  $params
     * @param  array  $options
     * @return \PDOStatement
     */
    protected function query($sql, array $params = array(), array $options = array())
    {
        $stmt = $this->prepare( $sql );
        if (!empty($params))
        {
            foreach ($params as $key => $value)
            {
                $param  = (is_int($key) ? ($key + 1) : $key);
                $stmt->bindParam( $param, $params[$key] );
            }
        }
        $stmt->execute();
        return $stmt;
    }
}
