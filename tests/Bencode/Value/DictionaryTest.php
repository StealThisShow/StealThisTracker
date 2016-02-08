<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

/**
 * Test class for Dictionary.
 */
class DictionaryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Dictionary
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Dictionary( array(
            'b' => new Integer( 12 ),
            'a' => new String( 'abc' ),
        ) );
    }

    /**
     * Running testcase test__toString().
     */
    public function test__toString()
    {
        // Keys are ABC ordered.
        $this->assertSame( 'd1:a3:abc1:bi12ee', $this->object . '' );
    }

    /**
     * Running testcase testRepresent().
     */
    public function testRepresent()
    {
        $this->assertSame( array( 'b' => 12, 'a' => 'abc' ), $this->object->represent() );
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\InvalidValue
     */
    public function testDuplicate()
    {
        $this->object->contain( new String( 'xxx' ), new String( 'a' ) );
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\InvalidType
     */
    public function testNoKey()
    {
        $this->object->contain( new String( 'xxx' ) );
    }

}
