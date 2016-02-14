<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

/**
 * Test class for Integer.
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @licence https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class IntegerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The integer object
     *
     * @var Integer
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
        $this->object = new Integer(111);
    }

    /**
     * Running testcase test__toString().
     *
     * @return void
     */
    public function testToString()
    {
        $this->assertSame('i111e', $this->object . '');
    }

    /**
     * Running testcase testRepresent().
     *
     * @return void
     */
    public function testRepresent()
    {
        $this->assertSame(111, $this->object->represent());
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
        new Integer('abcdef');
    }

}
