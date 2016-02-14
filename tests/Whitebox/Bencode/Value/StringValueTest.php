<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

/**
 * Test class for StringTest.
 *
 * @package StealThisTracker
 */
class StringValueTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The string object
     *
     * @var String
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
        $this->object = new StringValue('abcdef');
    }

    /**
     * Running testcase test__toString().
     *
     * @return void
     */
    public function testToString()
    {
        $this->assertSame('6:abcdef', $this->object . '');
    }

    /**
     * Running testcase testRepresent().
     *
     * @return void
     */
    public function testRepresent()
    {
        $this->assertSame('abcdef', $this->object->represent());
    }

    /**
     * Test invalid value
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\InvalidType
     *
     * @return void
     */
    public function testInvalidValue()
    {
        new StringValue(array());
    }
}
