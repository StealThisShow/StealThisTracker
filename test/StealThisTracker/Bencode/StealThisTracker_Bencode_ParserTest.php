<?php

/**
 * Test class for StealThisTracker_Bencode_Parser.
 */
class StealThisTracker_Bencode_ParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider parseableStrings
     */
    public function testParse( $string_to_parse )
    {
       $object = new StealThisTracker_Bencode_Parser( $string_to_parse );

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
     * @expectedException StealThisTracker_Bencode_Error_Parse
     */
    public function testParseErrorInvalidValue()
    {
        $object = new StealThisTracker_Bencode_Parser( 'something stupid' );
        $object->parse();
    }

    /**
     * @expectedException StealThisTracker_Bencode_Error_Parse
     */
    public function testParseErrorUnstructured()
    {
        $object = new StealThisTracker_Bencode_Parser( 'i456ei222e' );
        $object->parse();
    }

    /**
     * @expectedException StealThisTracker_Bencode_Error_Parse
     */
    public function testParseErrorIncompleteDictionary()
    {
        $object = new StealThisTracker_Bencode_Parser( 'd3:foo3:bar3:baze' );
        $object->parse();
    }

    /**
     * @expectedException StealThisTracker_Bencode_Error_Parse
     */
    public function testParseErrorUnbalancedEnding()
    {
        $object = new StealThisTracker_Bencode_Parser( 'lee' );
        $object->parse();
    }

    /**
     * @expectedException StealThisTracker_Bencode_Error_Parse
     */
    public function testParseErrorMissingIntegerEnding()
    {
        $object = new StealThisTracker_Bencode_Parser( 'i222' );
        $object->parse();
    }

    /**
     * @expectedException StealThisTracker_Bencode_Error_Parse
     */
    public function testParseErrorInvalidStringLength()
    {
        $object = new StealThisTracker_Bencode_Parser( '12abc:string' );
        $object->parse();
    }

    /**
     * @expectedException StealThisTracker_Bencode_Error_Parse
     */
    public function testParseErrorMissingStringColon()
    {
        $object = new StealThisTracker_Bencode_Parser( '123' );
        $object->parse();
    }

    /**
     * @expectedException StealThisTracker_Bencode_Error_Parse
     */
    public function testParseErrorUnendedContainer()
    {
        $object = new StealThisTracker_Bencode_Parser( 'ld2:AB2:CDe' );
        $object->parse();
    }

}
