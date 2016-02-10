<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

/**
 * Test class for Integer.
 */
class IntegerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Integer
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Integer( 111 );
    }

    /**
     * Running testcase test__toString().
     */
    public function test__toString()
    {
        $this->assertSame( 'i111e', $this->object . '' );
    }

    /**
     * Running testcase testRepresent().
     */
    public function testRepresent()
    {
        $this->assertSame( 111, $this->object->represent() );
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\InvalidType
     */
    public function testInvalidValue()
    {
         new Integer( 'abcdef' );
    }

}
