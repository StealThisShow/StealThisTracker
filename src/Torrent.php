<?php

namespace StealThisShow\StealThisTracker;

use StealThisShow\StealThisTracker\Bencode\Builder;
use StealThisShow\StealThisTracker\File\File;

/**
 * Class representing one torrent file.
 *
 * It does lazy-initializing on its attributes intensively, because some of them
 * imply performance-heavy calculations (accessing files, calculating hashes).
 *
 * Be aware of that when using this object!
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 *
 * @property-read string $pieces        Pieces
 * @property-read string $info_hash     Info hash
 * @property-read int    $length        Length
 * @property-read int    $size_piece    Size piece
 * @property-read string $name          Name
 * @property-read string $file_path     File path
 * @property-read bool   $private       Private
 * @property-read string $created_by    Created by
 * @property-read array  $announce_list Announce-list
 * @property-read array  $nodes         Nodes
 * @property-read array  $url_list      URL-list
 */
class Torrent
{
    /**
     * Piece size in bytes used to construct the torrent. 
     * Normally a power of 2 (eg. 256kB).
     *
     * @var integer
     */
    protected $size_piece;

    /**
     * File object of the physical file that belongs to this torrent.
     *
     * @var File
     */
    protected $file;

    /**
     * Concatenated hashes of each piece of this file.
     *
     * @var string
     */
    protected $pieces;

    /**
     * Size of the file.
     *
     * @var integer
     */
    protected $length;

    /**
     * Basename of the file.
     *
     * @var string
     */
    protected $name;

    /**
     * Full path of the physical file of this torrent.
     *
     * @var string
     */
    protected $file_path;

    /**
     * "Info hash" uniquely identifying this torrent.
     *
     * @var string
     */
    protected $info_hash;

    /**
     * Private flag (BEP 27)
     *
     * @var bool
     */
    protected $private;

    /**
     * The announce list of this torrent (BEP 12).
     *
     * @var array
     */
    protected $announce_list;

    /**
     * The DHT nodes of this torrent (BEP 5).
     *
     * @var array
     */
    protected $nodes;

    /**
     * Webseed(s) url-list (BEP 19).
     *
     * @var array
     */
    protected $url_list;

    /**
     * Created by
     *
     * @var string
     */
    protected $created_by;

    /**
     * Initializing object with the piece size and file object,
     * optionally setting attributes from the database.
     *
     * @param File    $file          To initialize 'file' attribute.
     * @param integer $size_piece    Optional. To set 'size_piece' attribute.
     *                               Defaults to 262144 bytes (256 kB)
     * @param string  $file_path     Optional. To set 'file_path' attribute.
     * @param string  $name          Optional. To set 'name' attribute.
     * @param integer $length        Optional. To set 'length' attribute.
     * @param string  $pieces        Optional. To set 'pieces' attribute.
     * @param string  $info_hash     Optional. To set 'info_hash' attribute.
     * @param bool    $private       Private flag.
     * @param array   $announce_list Announce-list.
     * @param array   $nodes         DHT Nodes.
     * @param array   $url_list      Url-list.
     * @param string  $created_by    Created by. Defaults to 'StealThisTracker'
     *
     * @throws Error\InvalidPieceSize When the piece size is invalid.
     */
    public function __construct(
        File $file,
        $size_piece = 262144,
        $file_path = null,
        $name = null,
        $length = null,
        $pieces = null,
        $info_hash = null,
        $private = null,
        array $announce_list = array(),
        array $nodes = array(),
        array $url_list = array(),
        $created_by = 'StealThisTracker'
    ) {
        if (0 >= $size_piece = intval($size_piece)) {
            throw new Error\InvalidPieceSize('Invalid piece size: ' . $size_piece);
        }

        $this->file             = $file;

        // Optional parameters.
        $this->size_piece       = $size_piece;
        $this->length           = is_null($length) ? null : (int) $length;
        $this->name             = $name;
        $this->file_path        = $file_path;
        $this->pieces           = $pieces;
        $this->info_hash        = $info_hash;
        $this->private          = $private;
        $this->announce_list    = $announce_list;
        $this->nodes            = $nodes;
        $this->url_list         = $url_list;
        $this->created_by       = $created_by;
    }

    /**
     * Set file_path.
     * This fluent setter can be used instead of passing the argument
     * through constructor. To be used once only.
     *
     * @param string $file_path File path
     *
     * @return $this
     */
    public function setFilePath($file_path)
    {
        if (!isset($this->file_path)) {
            $this->file_path = (string) $file_path;
        }
        return $this;
    }

    /**
     * Set name.
     * This fluent setter can be used instead of passing the argument
     * through the constructor. To be used once only.
     *
     * @param string $name Name
     *
     * @return $this
     */
    public function setName($name)
    {
        if (!isset($this->name)) {
            $this->name = (string) $name;
        }
        return $this;
    }

    /**
     * Set pieces.
     * This fluent setter can be used instead of passing the argument
     * through the constructor. To be used once only.
     *
     * @param string $pieces Pieces
     *
     * @return $this
     */
    public function setPieces($pieces)
    {
        if (!isset($this->pieces)) {
            $this->pieces = (string) $pieces;
        }
        return $this;
    }

    /**
     * Set info_hash.
     * This fluent setter can be used instead of passing the argument
     * through the constructor. To be used once only.
     *
     * @param string $info_hash Info hash
     *
     * @return $this
     */
    public function setInfoHash($info_hash)
    {
        if (!isset($this->info_hash)) {
            $this->info_hash = $info_hash;
        }
        return $this;
    }

    /**
     * Set announce-list.
     * This fluent setter can be used instead of passing the argument
     * through the constructor. To be used once only.
     *
     * @param array $announce_list Announce-list
     *
     * @return $this
     */
    public function setAnnounceList(array $announce_list)
    {
        if (empty($this->announce_list)) {
            $this->announce_list = $announce_list;
        }
        return $this;
    }

    /**
     * Set DHT nodes.
     * This fluent setter can be used instead of passing the argument
     * through the constructor. To be used once only.
     *
     * @param array $nodes Nodes
     *
     * @return $this
     */
    public function setNodes(array $nodes)
    {
        if (empty($this->nodes)) {
            $this->nodes = $nodes;
        }
        return $this;
    }

    /**
     * Set url-list.
     * This fluent setter can be used instead of passing the argument
     * through the constructor. To be used once only.
     *
     * @param array $url_list Url-list
     *
     * @return $this
     */
    public function setUrlList(array $url_list)
    {
        if (empty($this->url_list)) {
            $this->url_list = $url_list;
        }
        return $this;
    }

    /**
     * Set created by.
     * This fluent setter can be used instead of passing the argument
     * through the constructor. To be used once only.
     *
     * @param string $created_by Created by
     *
     * @return $this
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }

    /**
     * Set created by.
     * This fluent setter can be used instead of passing the argument
     * through the constructor. To be used once only.
     *
     * @param int $size_piece Size piece
     *
     * @return $this
     * @throws Error\InvalidPieceSize
     */
    public function setSizePiece($size_piece)
    {
        if (0 >= $size_piece = intval($size_piece)) {
            throw new Error\InvalidPieceSize('Invalid piece size: ' . $size_piece);
        }
        $this->size_piece = (int) $size_piece;
        return $this;
    }

    /**
     * Set length.
     * This fluent setter can be used instead of passing the argument
     * through the constructor. To be used once only.
     *
     * @param int $length Length
     *
     * @return $this
     */
    public function setLength($length)
    {
        if (!isset($this->length)) {
            $this->length = (int)$length;
        }
        return $this;
    }

    /**
     * Set private flag.
     * This fluent setter can be used instead of passing the argument
     * through the constructor. To be used once only.
     *
     * @param bool $private Private flag
     *
     * @return $this
     */
    public function setPrivate($private)
    {
        if (!isset($this->private)) {
            $this->private = (bool)$private;
        }
        return $this;
    }

    /**
     * Lazy-loading attributes on accessing them using external resources.
     *
     * Object attributes are protected by default, but made read-only with
     * this magic method.
     *
     * @param string $attribute The name of the attribute to access.
     *
     * @throws Error\InvalidTorrentAttribute
     * When trying to access non-existent attribute.
     *
     * @return mixed
     */
    public function __get($attribute)
    {
        switch ($attribute)
        {
            case 'pieces':
                if (!isset($this->pieces)) {
                    $this->pieces = $this->file->getHashesForPieces(
                        $this->size_piece
                    );
                }
                return $this->pieces;
            case 'length':
                if (!isset($this->length)) {
                    $this->length = $this->file->size();
                }
                return (int) $this->length;
            case 'name':
                if (!isset($this->name)) {
                    $this->name = $this->file->basename();
                }
                return $this->name;
            case 'file_path':
                if (!isset($this->file_path)) {
                    $this->file_path = (string)$this->file;
                }
                return $this->file_path;
            case 'info_hash':
                if (!isset($this->info_hash)) {
                    $this->info_hash = $this->calculateInfoHash();
                }
                return $this->info_hash;
            case 'size_piece':
                return (int) $this->size_piece;
            case 'private':
                return (bool) $this->private;
            case 'announce_list':
                return (array) $this->announce_list;
            case 'nodes':
                return (array) $this->nodes;
            case 'announce':
                return reset($this->announce_list);
            case 'url_list':
                return (array) $this->url_list;
            case 'created_by':
                return $this->created_by;
            default:
                throw new Error\InvalidTorrentAttribute(
                    "Can't access attribute $attribute of " . __CLASS__
                );
        }
    }

    /**
     * Telling that "read-only" attributes are set, see __get.
     *
     * All properties accessible via __get should be added here and return true.
     *
     * @param string $attribute The name of the attribute to access.
     *
     * @return bool
     */
    public function __isset($attribute)
    {
        switch($attribute) {
            case 'pieces':
            case 'length':
            case 'name':
            case 'size_piece':
            case 'info_hash':
            case 'file_path':
            case 'private':
            case 'nodes':
            case 'announce_list':
            case 'announce':
            case 'url_list':
            case 'created_by':
                return true;
        }

        return false;
    }

    /**
     * Calculates info hash (unique identifier) of the torrent.
     *
     * @return string
     */
    protected function calculateInfoHash()
    {
        return sha1(Builder::build($this->getInfo()), true);
    }

    /**
     * Returns the torrent info array
     *
     * @return array
     */
    public function getInfo()
    {
        return array(
            'piece length'  => $this->size_piece,
            'private'       => (int) $this->private,
            'pieces'        => $this->__get('pieces'),
            'name'          => $this->__get('name'),
            'length'        => $this->__get('length')
        );
    }

    /**
     * Returns a bencoded string that represents a .torrent file and can be
     * read by BitTorrent clients.
     *
     * First item in the $announce_list will be used in the 'announce' key of
     * the .torrent file, which is compatible with the BitTorrent specification
     * ('announce-list' is an unofficial extension).
     *
     * @return string
     */
    public function createTorrentFile()
    {
        $torrent_data = array();

        // Info
        $torrent_data['info'] = $this->getInfo();

        // Announce-list
        if (!empty($this->announce_list)) {
            // Announce-list is a list of lists of strings.
            $announce_list = Utils::listToListOfLists($this->announce_list);
            // Reset twice because list of lists
            $first = reset($announce_list);
            $torrent_data['announce'] = reset($first);
            $torrent_data['announce-list'] = $announce_list;
        } elseif (!empty($this->nodes)) {
            // DHT nodes
            $torrent_data['nodes'] = $this->nodes;
        }
        // Url-list
        if (!empty($this->url_list)) {
            $torrent_data['url-list'] = $this->url_list;
        }
        // Created by
        if (!empty($this->created_by)) {
            $torrent_data['created by'] = $this->created_by;
        }
        return Builder::build($torrent_data);
    }

    /**
     * Returns a string that represents a magnet URI and can be
     * read by BitTorrent clients.
     *
     * @return string
     */
    public function createMagnetUri()
    {
        // TODO: Add additional data to magnet URI (trackers, webseeds etc.)
        return 'magnet:?xt=urn:btih:' . (string) $this->__get('info_hash');
    }

    /**
     * Reads a block of the physical file that the torrent represents.
     *
     * @param integer $piece_index Index of the piece containing the block.
     * @param integer $block_begin Beginning of the block relative
     *                             to the piece in bytes.
     * @param integer $length      Length of the block in bytes.
     *
     * @return string
     * @throws Error\BlockRead
     */
    public function readBlock($piece_index, $block_begin, $length)
    {
        if ($piece_index > ceil($this->__get('length') / $this->size_piece) - 1) {
            throw new Error\BlockRead('Invalid piece index: ' . $piece_index);
        }
        if ($block_begin + $length > $this->size_piece) {
            throw new Error\BlockRead(
                'Invalid block boundary: ' . $block_begin . ', ' . $length
            );
        }

        return $this->file->readBlock(
            ($piece_index * $this->size_piece) + $block_begin,
            $length
        );
    }

}
