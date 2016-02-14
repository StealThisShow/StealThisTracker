<?php

namespace StealThisShow\StealThisTracker;

/**
 * Test class for Torrent.
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @licene https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class TorrentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The torrent object
     *
     * @var Torrent
     */
    protected $object;

    protected $file_path;

    const TEST_DATA = 'abcdefghijklmnopqrstuvwxyz';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        // File name must be fix for info hashing.
        $this->file_path = sys_get_temp_dir() . '/test_torrent';
        file_put_contents($this->file_path, self::TEST_DATA);

        $file = new File\File($this->file_path);

        $this->object = new Torrent($file, 2);
        $this->object
            ->setPrivate(false)
            ->setAnnounceList(array('http://announce'))
            ->setUrlList(array('http://example.com/test.ext'));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown()
    {
        // Closing file handles.
        unset($this->object);
        // Remove temporary file.
        if (file_exists($this->file_path)) {
            unlink($this->file_path);
        }
    }

    /**
     * Testing if all properties are successfully initialized.
     *
     * @return void
     */
    public function testProperties()
    {
        $this->assertTrue(isset($this->object->pieces));
        $this->assertTrue(isset($this->object->length));
        $this->assertTrue(isset($this->object->name));
        $this->assertTrue(isset($this->object->size_piece));
        $this->assertTrue(isset($this->object->info_hash));
        $this->assertTrue(isset($this->object->file_path));
        $this->assertTrue(isset($this->object->private));
        $this->assertTrue(isset($this->object->announce_list));
        $this->assertTrue(isset($this->object->nodes));
        $this->assertTrue(isset($this->object->url_list));

        $this->assertEquals(
            $this->createPiecesHash(self::TEST_DATA, 2), $this->object->pieces
        );
        $this->assertEquals(strlen(self::TEST_DATA), $this->object->length);
        $this->assertEquals(basename($this->file_path), $this->object->name);
        $this->assertEquals(2, $this->object->size_piece);
        $this->assertEquals(realpath($this->file_path), $this->object->file_path);

        $info_hash_readable = unpack('H*', $this->object->info_hash);
        $info_hash_readable = current($info_hash_readable);

        // We have to hardcode this to the test.
        $this->assertEquals(
            '0a144b67246d58f90ab22c22646952dd1465bf2c', $info_hash_readable
        );
    }

    /**
     * Testing .torrent file creation.
     *
     * @return void
     */
    public function testCreateTorrentFile()
    {
        $bencoded_torrent = (string) $this->object->createTorrentFile();

        $parser = new Bencode\Parser($bencoded_torrent);
        $decoded_torrent = $parser->parse()->represent();

        $this->assertEquals(
            $decoded_torrent['info']['piece length'], $this->object->size_piece
        );
        $this->assertEquals(
            $decoded_torrent['info']['name'], $this->object->name
        );
        $this->assertEquals(
            $decoded_torrent['info']['length'], $this->object->length
        );
        $this->assertEquals(
            $decoded_torrent['info']['pieces'], $this->object->pieces
        );
        $this->assertEquals(
            $decoded_torrent['info']['private'], $this->object->private
        );

        $this->assertEquals($decoded_torrent['announce'], 'http://announce');
        $this->assertContains(
            array('http://announce'), $decoded_torrent['announce-list']
        );
        $this->assertEquals(
            $decoded_torrent['url-list'], array('http://example.com/test.ext')
        );
    }

    /**
     * Testing block read.
     *
     * @return void
     */
    public function testReadBlock()
    {
        $this->assertEquals('c', $this->object->readBlock(1, 0, 1));
        $this->assertEquals('d', $this->object->readBlock(1, 1, 1));
        $this->assertEquals('ef', $this->object->readBlock(2, 0, 2));
    }

    /**
     * Test read block no piece
     *
     * @expectedException \StealThisShow\StealThisTracker\Error\BlockRead
     *
     * @return void
     */
    public function testReadBlockNoPiece()
    {
        // Piece index too high.
        $this->object->readBlock(80, 0, 1);
    }

    /**
     * Test read block too large block
     *
     * @expectedException \StealThisShow\StealThisTracker\Error\BlockRead
     *
     * @return void
     */
    public function testReadBlockTooLargeBlock()
    {
        // Block size too high
        $this->object->readBlock(0, 1, 2);
    }

    /**
     * Create pieces hash
     *
     * @param string $data       Data
     * @param int    $piece_size Piece size
     *
     * @return string
     */
    protected function createPiecesHash($data, $piece_size)
    {
        $pieces = '';
        while ('' != $data) {
            $pieces .= sha1(substr($data, 0, min($piece_size, strlen($data))), true);
            $data = substr($data, min($piece_size, strlen($data)));
        }
        return $pieces;
    }

}
