<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

/**
 * Test class for Container.
 *
 * @package StealThisTracker
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The container object
     *
     * @var Container
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        // Don't call constructor.
        $this->object = $this->getMockForAbstractClass(
            '\StealThisShow\StealThisTracker\Bencode\Value\Container',
            array(), '', false
        );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown()
    {

    }

    /**
     * Running testcase testRepresent().
     *
     * @return void
     */
    public function testConstructAssociative()
    {
        $test_array = array(
            'key1' => new Integer(1),
            'key2' => new Integer(2),
        );

        $this->object->expects($this->at(0))
            ->method('contain')
            ->with(
                $this->equalTo($test_array['key1']),
                $this->isInstanceOf(
                    '\StealThisShow\StealThisTracker\Bencode\Value\StringValue'
                )
            );
        $this->object->expects($this->at(1))
            ->method('contain')
            ->with(
                $this->equalTo($test_array['key2']),
                $this->isInstanceOf(
                    '\StealThisShow\StealThisTracker\Bencode\Value\StringValue'
                )
            );

        $this->object->__construct($test_array);
    }

    /**
     * Running testcase testRepresent().
     *
     * @return void
     */
    public function testConstructList()
    {
        $test_array = array(
            new Integer(3),
            new Integer(4),
        );

        $this->object->expects($this->at(0))
            ->method('contain')
            ->with(
                $this->equalTo($test_array[0])
            );
        $this->object->expects($this->at(1))
            ->method('contain')
            ->with(
                $this->equalTo($test_array[1])
            );

        $this->object->__construct($test_array);
    }

}
