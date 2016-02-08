<?php

namespace StealThisShow\StealThisTracker\Bencode;

/**
 * Test class for Parser.
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider parseableStrings
     * @param $string_to_parse
     * @throws Error\Parse
     */
    public function testParse( $string_to_parse )
    {
       $object = new Parser( $string_to_parse );

       // Parse method returns StealThisTracker_Bencode_Value_Abstract objects, and they are converted back to string by calling __toString.
       $this->assertEquals( $string_to_parse, $object->parse() . '' );
    }

    public static function parseableStrings()
    {
        return array(
            array( 'i123e' ), // Integer.
            array( 'i-55e' ), // Integer.
            array( '5:funny' ), // String.
            array( 'li123e5:funnye' ), // List.
            array( 'd5:funnyi555e4:test2:OKe' ), // Dictionary.
            array( 'd7:Address17:1 Time Square, NY6:Phonesli123456e10:0012567890ee' ), // Complex.
        );
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     */
    public function testParseErrorInvalidValue()
    {
        $object = new Parser( 'something stupid' );
        $object->parse();
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     */
    public function testParseErrorUnstructured()
    {
        $object = new Parser( 'i456ei222e' );
        $object->parse();
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     */
    public function testParseErrorIncompleteDictionary()
    {
        $object = new Parser( 'd3:foo3:bar3:baze' );
        $object->parse();
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     */
    public function testParseErrorUnbalancedEnding()
    {
        $object = new Parser( 'lee' );
        $object->parse();
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     */
    public function testParseErrorMissingIntegerEnding()
    {
        $object = new Parser( 'i222' );
        $object->parse();
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     */
    public function testParseErrorInvalidStringLength()
    {
        $object = new Parser( '12abc:string' );
        $object->parse();
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     */
    public function testParseErrorMissingStringColon()
    {
        $object = new Parser( '123' );
        $object->parse();
    }

    /**
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     */
    public function testParseErrorUnendedContainer()
    {
        $object = new Parser( 'ld2:AB2:CDe' );
        $object->parse();
    }

}
