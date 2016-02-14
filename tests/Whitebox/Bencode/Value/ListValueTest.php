<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

/**
 * Test class for ListValue.
 *
 * @package StealThisTracker
 */
class ListValueTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The list object
     *
     * @var ListValue
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
        $this->object = new ListValue(
            array(
                new Integer(12),
                new StringValue('abc'),
            )
        );
    }

    /**
     * Running testcase test__toString().
     *
     * @return void
     */
    public function testToString()
    {
        $this->assertSame('li12e3:abce', $this->object . '');
    }

    /**
     * Running testcase testRepresent().
     *
     * @return void
     */
    public function testRepresent()
    {
        $this->assertSame(array(12, 'abc'), $this->object->represent());
    }

}
