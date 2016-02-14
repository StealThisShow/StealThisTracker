<?php

namespace StealThisShow\StealThisTracker\File;

/**
 * Test class for File.
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class FileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The file object
     *
     * @var File
     */
    protected $object;

    protected $original_path;

    const TEST_DATA = 'abcdefghijklmnopqrstuvwxyz';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->original_path = sys_get_temp_dir() . '/test_' . md5(uniqid());
        file_put_contents($this->original_path, self::TEST_DATA);

        $this->object = new File($this->original_path);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown()
    {
        // We have to destroy the object to close open handles.
        unset($this->object);
        // Then we can delete the test file.
        if (file_exists($this->original_path)) {
            unlink($this->original_path);
        }
    }

    /**
     * Running testcase test__toString().
     *
     * @return void
     */
    public function testToString()
    {
        $this->assertEquals(realpath($this->original_path), $this->object . '');
    }

    /**
     * Running testcase testSize().
     *
     * @return void
     */
    public function testSize()
    {
        $this->assertEquals(strlen(self::TEST_DATA), $this->object->size());
    }

    /**
     * Test non-existent
     *
     * @expectedException \StealThisShow\StealThisTracker\File\Error\NotExists
     *
     * @return void
     */
    public function testNonExistent()
    {
        $non_existent = new File(sys_get_temp_dir() . '/no_way_this_exists');
    }

    /**
     * Running testcase testGetHashesForPieces().
     *
     * @return void
     */
    public function testGetHashesForPieces()
    {
        // Generating test hash for 1 byte length pieces.
        $expected_hash = '';
        for ($i = 0; $i < strlen(self::TEST_DATA); ++$i) {
            $expected_hash .= sha1(substr(self::TEST_DATA, $i, 1), true);
        }

        $this->assertSame($expected_hash, $this->object->getHashesForPieces(1));
    }

    /**
     * Test get hashes for pieces unreadable
     *
     * @expectedException \StealThisShow\StealThisTracker\File\Error\Unreadable
     *
     * @return void
     */
    public function testGetHashesForPiecesUnreadable()
    {
        unlink($this->original_path);
        $this->object->getHashesForPieces(10);
    }

    /**
     * Test basename
     *
     * @return void
     */
    public function testBasename()
    {
        $this->assertEquals(
            basename($this->original_path), $this->object->basename()
        );
    }

    /**
     * Test read block
     *
     * @throws Error\Unreadable
     * @return void
     */
    public function testReadBlock()
    {
        $this->assertEquals(
            substr(self::TEST_DATA, 2, 2), $this->object->readBlock(2, 2)
        );
    }

}
