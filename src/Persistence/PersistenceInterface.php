<?php

namespace StealThisShow\StealThisTracker\Persistence;

use StealThisShow\StealThisTracker\Torrent;

/**
 * Interface used to give data persistence to the system (database).
 *
 * Feel free to implement your own storage
 * with PersistenceInterface.
 *
 * @package    StealThisTracker
 * @subpackage Persistence
 */
interface PersistenceInterface
{

    /**
     * Save all accessible keys of a Torrent object to be able to recreate it.
     *
     * Use info_hash property as unique key and overwrite attributes when saved
     * multiple times with the same info hash.
     *
     * @param Torrent $torrent Torrent
     *
     * @return void
     */
    public function saveTorrent(Torrent $torrent);

    /**
     * Given a 20 bytes info hash, return an initialized Torrent object.
     *
     * Must return null if the info hash is not found.
     *
     * @param string $info_hash Info hash
     *
     * @return Torrent
     */
    public function getTorrent($info_hash);

    /**
     * Saves peer announcement from a client.
     *
     * Majority of the parameters of this method come from GET.
     *
     * @param string  $info_hash  20 bytes info hash of the announced torrent.
     * @param string  $peer_id    20 bytes peer ID of the announcing peer.
     * @param string  $ip         Dotted IP address of the client.
     * @param integer $port       Port number of the client.
     * @param integer $downloaded Already downloaded bytes.
     * @param integer $uploaded   Already uploaded bytes.
     * @param integer $left       Bytes left to download.
     * @param string  $status     Can be complete, incomplete or NULL.
     *                            Incomplete is default for new rows.
     *                            If once set to complete,
     *                            NULL does not set it back on update.
     * @param integer $ttl        Time to live in seconds meaning the time after we
     *                            should consider peer offline
     *                            (if no more updates come).
     *
     * @return void
     */
    public function saveAnnounce(
        $info_hash,
        $peer_id, $ip, $port,
        $downloaded, $uploaded,
        $left, $status, $ttl
    );

    /**
     * Return all the inf_hashes and lengths of the active arrays.
     *
     * @return array An array of arrays having keys
     *               'info_hash' and 'length' accordingly.
     */
    public function getAllInfoHash();

    /**
     * Gets all the active peers for a torrent.
     *
     * Only considers peers which are not expired (see TTL).
     * Returns:
     *
     * array(
     *  array(
     *      'peer_id' => ... // ID of the peer
     *      'ip' => ... // Dotted IP address of the peer.
     *      'port' => ... // Port number of the peer.
     *  )
     * )
     *
     * @param string $info_hash Info hash of the torrent.
     * @param string $peer_id   Peer ID to exclude
     *                          (peer ID of the client announcing).
     *
     * @return mixed
     */
    public function getPeers( $info_hash, $peer_id );

    /**
     * Returns statistics of seeders and leechers of a torrent.
     *
     * Only considers peers which are not expired (see TTL).
     *
     * @param string $info_hash Info hash of the torrent.
     * @param string $peer_id   Peer ID to exclude
     *                          (peer ID of the client announcing).
     *
     * @return array With keys 'complete' and 'incomplete'
     *               having counters for each group.
     */
    public function getPeerStats( $info_hash, $peer_id );
}
