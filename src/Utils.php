<?php

namespace StealThisShow\StealThisTracker;

/**
 * Utils. Functions used throughout the package.
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class Utils
{
    /**
     * Converts a list (array) to a lists of lists
     *
     * @param array $array Array
     * 
     * @return array
     */
    public static function listToListOfLists(array $array)
    {
        foreach ($array as &$item) {
            if (is_array($item)) {
                continue;
            }
            $item = array($item);
        }
        return $array;
    }

    /**
     * Tells if a passed value (user input) is a non-negative integer.
     *
     * @param int $value Value
     *
     * @return bool
     */
    public static function isNonNegativeInteger($value)
    {
        return
            is_numeric($value)
            &&
            is_int($value = $value + 0)
            &&
            0 <= $value;
    }

    /**
     * Applies filters to peers array
     *
     * @param array $peers      Peers
     * @param bool  $compact    Compact
     * @param bool  $no_peer_id No peer ID
     *
     * @return array|string
     */
    public static function applyPeerFilters(array $peers, $compact, $no_peer_id)
    {
        if ($compact) {
            $peers = self::compactPeers($peers);
        } elseif ($no_peer_id) {
            $peers = self::removePeerId($peers);
        }
        return $peers;
    }

    /**
     * As per request of the announcing client we might need to compact peers.
     *
     * Compacting means representing the IP in a big-endian long and the port
     * as a big-endian short and concatenating all of them in a string.
     *
     * @param array $peers List of peers with their IP address and port.
     *
     * @see http://wiki.theory.org/BitTorrentSpecification#Tracker_Response
     *
     * @return string
     */
    protected static function compactPeers(array $peers)
    {
        $compact_peers = "";
        foreach ($peers as $peer) {
            // Do not add IP-addressess if they are not IPv4 (e.g. IPv6)
            if (!filter_var($peer['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }
            $compact_peers .=
                pack('N', ip2long($peer['ip'])) .
                pack('n', $peer['port']);
        }
        return $compact_peers;
    }

    /**
     * As per request of the announcing client we might need to remove peer IDs.
     *
     * @param array $peers List of peers with their IP address and port.
     *
     * @see http://wiki.theory.org/BitTorrentSpecification#Tracker_Response
     *
     * @return string
     */
    protected static function removePeerId(array $peers)
    {
        foreach ($peers as $peer_index => $peer) {
            unset($peers[$peer_index]['peer id']);
        }
        return $peers;
    }

    /**
     * Return if an array is missing mandatory keys
     *
     * @param array $mandatory_keys Mandatory keys
     * @param array $array          Array to check
     *
     * @return array
     */
    public static function hasMissingKeys(array $mandatory_keys, array $array)
    {
        $missing_keys = array_diff($mandatory_keys, array_keys($array));
        return $missing_keys;
    }
}