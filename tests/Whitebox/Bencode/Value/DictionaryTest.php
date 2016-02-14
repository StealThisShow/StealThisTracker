<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

/**
 * Test class for Dictionary.
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @licence https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class DictionaryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The dictionary object
     *
     * @var Dictionary
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
        $this->object = new Dictionary(
            array(
                'b' => new Integer(12),
                'a' => new StringValue('abc'),
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
        // Keys are ABC ordered.
        $this->assertSame('d1:a3:abc1:bi12ee', $this->object . '');
    }

    /**
     * Running testcase testRepresent().
     *
     * @return void
     */
    public function testRepresent()
    {
        $this->assertSame(
            array('b' => 12, 'a' => 'abc'), $this->object->represent()
        );
    }

    /**
     * Test duplicate
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\InvalidValue
     *
     * @return void
     */
    public function testDuplicate()
    {
        $this->object->contain(new StringValue('xxx'), new StringValue('a'));
    }

    /**
     * Test no key
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\InvalidType
     *
     * @return void
     */
    public function testNoKey()
    {
        $this->object->contain(new StringValue('xxx'));
    }

}
