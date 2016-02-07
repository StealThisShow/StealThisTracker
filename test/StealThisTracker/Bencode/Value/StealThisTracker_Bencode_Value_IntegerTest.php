<?php

/**
 * Test class for StealThisTracker_Bencode_Value_Integer.
 */
class StealThisTracker_Bencode_Value_IntegerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var StealThisTracker_Bencode_Value_Integer
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new StealThisTracker_Bencode_Value_Integer( 111 );
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
     * @expectedException StealThisTracker_Bencode_Error_InvalidType
     */
    public function testInvalidValue()
    {
         new StealThisTracker_Bencode_Value_Integer( 'abcdef' );
    }

}
