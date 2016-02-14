<?php

namespace StealThisShow\StealThisTracker\Bencode;

/**
 * Test class for Builder.
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @licence https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class BuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test build
     *
     * @param mixed $input Input
     *
     * @dataProvider buildableInputs
     *
     * @throws Error\Build
     * @return void
     */
    public function testBuild($input)
    {
        // Parse method returns AbstractValue objects, and they
        // should return PHP representation of themselves when calling represent.
        $this->assertSame($input, Builder::build($input)->represent());
    }

    /**
     * Returns an array
     *
     * @return array
     */
    public static function buildableInputs()
    {
        return array(
            array( 12345 ), // Integer.
            array( 'foobar' ), // String.
            array( array( 'foo', 'bar', 'baz' ) ), // List.
            array( array( 'foo' => 'bar', 'baz' => 'bat' ) ), // Dictionary.
            array(
                array( 'foo' => array( 'baz', 'bat' ), 'baz' => 123 )
            ), // Complex.
        );
    }

    /**
     * Test build error float
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Build
     *
     * @return void
     */
    public function testBuildErrorFloat()
    {
        Builder::build(1.1111);
    }

    /**
     * Test build error boolean
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Build
     *
     * @return void
     */
    public function testBuildErrorBoolean()
    {
        Builder::build(true);
    }

    /**
     * Test build error object
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Build
     *
     * @return void
     */
    public function testBuildErrorObject()
    {
        Builder::build((object) array('attribute' => 'something'));
    }

}
