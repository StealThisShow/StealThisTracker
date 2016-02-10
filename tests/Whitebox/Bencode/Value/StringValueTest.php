<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

/**
 * Test class for StringTest.
 */
class StringValueTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var String
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new StringValue( 'abcdef' );
    }

    /**
     * Running testcase test__toString().
     */
    public function test__toString()
    {
        $this->assertSame( '6:abcdef', $this->object . '' );
    }

    /**
     * Running testcase testRepresent().
     */
    public function testRepresent()
    {
        $this->assertSame( 'abcdef', $this->object->represent() );
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\InvalidType
     */
    public function testInvalidValue()
    {
        new StringValue( array() );
    }
}
