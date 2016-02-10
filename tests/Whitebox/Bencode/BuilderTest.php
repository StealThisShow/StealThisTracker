<?php

namespace StealThisShow\StealThisTracker\Bencode;

/**
 * Test class for Builder.
 */
class BuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider buildableInputs
     * @param $input
     * @throws Error\Build
     */
    public function testBuild( $input )
    {
       // Parse method returns StealThisTracker_Bencode_Value_Abstract objects, and they
       // should return PHP representation of themselves when calling represent.
       $this->assertSame( $input, Builder::build( $input )->represent() );
    }

    public static function buildableInputs()
    {
        return array(
            array( 12345 ), // Integer.
            array( 'foobar' ), // String.
            array( array( 'foo', 'bar', 'baz' ) ), // List.
            array( array( 'foo' => 'bar', 'baz' => 'bat' ) ), // Dictionary.
            array( array( 'foo' => array( 'baz', 'bat' ), 'baz' => 123 ) ), // Complex.
        );
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Build
     */
    public function testBuildErrorFloat()
    {
        Builder::build( 1.1111 );
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Build
     */
    public function testBuildErrorObject()
    {
        Builder::build( (object) array( 'attribute' => 'something' ) );
    }

}
