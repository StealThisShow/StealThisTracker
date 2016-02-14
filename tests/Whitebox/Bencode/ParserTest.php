<?php

namespace StealThisShow\StealThisTracker\Bencode;

/**
 * Test class for Parser.
 *
 * @package StealThisTracker
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test parse
     *
     * @param string $string_to_parse String
     *
     * @throws Error\Parse
     * @return void
     *
     * @dataProvider parseableStrings
     */
    public function testParse($string_to_parse)
    {
        $object = new Parser($string_to_parse);

        // Parse method returns AbstractValue objects,
        // and they are converted back to string by calling __toString.
        $this->assertEquals($string_to_parse, $object->parse() . '');
    }

    /**
     * Returns array
     *
     * @return array
     */
    public static function parseableStrings()
    {
        return array(
            array('i123e'), // Integer.
            array('i-55e'), // Integer.
            array('5:funny'), // String.
            array('li123e5:funnye'), // List.
            array('d5:funnyi555e4:test2:OKe'), // Dictionary.
            array(
                'd7:Address17:1 Time Square, NY6:Phonesli123456e10:0012567890ee'
            ), // Complex.
        );
    }

    /**
     * Test parse error invalid value
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     *
     * @return void
     */
    public function testParseErrorInvalidValue()
    {
        $object = new Parser('something stupid');
        $object->parse();
    }

    /**
     * Test parse error unstructured
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     *
     * @return void
     */
    public function testParseErrorUnstructured()
    {
        $object = new Parser('i456ei222e');
        $object->parse();
    }

    /**
     * Test parse error incomplete dictionary
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     *
     * @return void
     */
    public function testParseErrorIncompleteDictionary()
    {
        $object = new Parser('d3:foo3:bar3:baze');
        $object->parse();
    }

    /**
     * Test parse error unbalanced ending
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     *
     * @return void
     */
    public function testParseErrorUnbalancedEnding()
    {
        $object = new Parser('lee');
        $object->parse();
    }

    /**
     * Test parse error missing integer ending
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     *
     * @return void
     */
    public function testParseErrorMissingIntegerEnding()
    {
        $object = new Parser('i222');
        $object->parse();
    }

    /**
     * Test parse error invalid string length
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     *
     * @return void
     */
    public function testParseErrorInvalidStringLength()
    {
        $object = new Parser('12abc:string');
        $object->parse();
    }

    /**
     * Test parse error missing string colon
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     *
     * @return void
     */
    public function testParseErrorMissingStringColon()
    {
        $object = new Parser('123');
        $object->parse();
    }

    /**
     * Test parse error unended container
     *
     * @expectedException \StealThisShow\StealThisTracker\Bencode\Error\Parse
     *
     * @return void
     */
    public function testParseErrorUnendedContainer()
    {
        $object = new Parser('ld2:AB2:CDe');
        $object->parse();
    }

}
