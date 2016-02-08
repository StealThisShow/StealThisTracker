<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

/**
 * Test class for ListValue.
 */
class ListValueTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ListValue
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new ListValue( array(
            new Integer( 12 ),
            new String( 'abc' ),
        ) );
    }

    /**
     * Running testcase test__toString().
     */
    public function test__toString()
    {
        $this->assertSame( 'li12e3:abce', $this->object . '' );
    }

    /**
     * Running testcase testRepresent().
     */
    public function testRepresent()
    {
        $this->assertSame( array( 12, 'abc' ), $this->object->represent() );
    }

}
