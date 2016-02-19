<?php

namespace StealThisShow\StealThisTracker\Bencode;

/**
 * Bencode parser creating Bencode value classes our of a bencoded string.
 *
 * @package    StealThisTracker
 * @subpackage Bencode
 * @author     StealThisShow <info@stealthisshow.com>
 * @license    https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class Parser
{
    protected $pointer;
    protected $container_stack;
    protected $string;

    /**
     * Setting up object.
     *
     * @param string $string String to decode.
     */
    public function __construct($string)
    {
        $this->string = (string) $string;
    }

    /**
     * Parsing the string attribute of the object.
     *
     * @throws Error\Parse In case of parse error.
     * @return Value\AbstractValue Parsed value.
     */
    public function parse()
    {
        $value = null;
        $this->pointer = 0;
        $this->container_stack = array();

        $string_length = strlen($this->string);
        while ($this->pointer < $string_length) {
            if (isset($value) && 0 == count($this->container_stack)) {
                $this->throwParseErrorAtPointer(
                    "Unstructured values following each other. Use list/dictionary!"
                );
            }

            $this->setValue($possible_key, $last_container, $value);

            // We store the current value in the current
            // deepest container (list/dictionary).
            if (0 != count($this->container_stack) && isset($value)) {
                $last_container = end($this->container_stack);

                // With list it's easy: you just throw in the values.
                if ($last_container instanceof Value\ListValue) {
                    $last_container->contain($value);
                } elseif (isset($possible_key)) {
                    // For the dictionary you have to have a key-value pair.
                    $last_container->contain($value, $possible_key);
                    unset($possible_key);
                } else {
                    // We save the last parsed value as a possible key
                    // for a dictionary.
                    $possible_key = $value;
                }
            }

            // If the currently parsed value is a container,
            // we set it as current container.
            if ($value instanceof Value\Container) {
                $this->container_stack[] = $value;
            }
        }

        // At this point we should not have anything in the stack,
        // because we closed all the dictionaries/lists.
        if (0 != count($this->container_stack)) {
            $this->throwParseErrorAtPointer("Unclosed dictionary/list");
        }

        // If the whole string is a scalar (int/string), it's OK.
        return isset($last_container) ? $last_container : $value;
    }

    /**
     * Set the value
     *
     * @param string              $possible_key   Possible key
     * @param Value\AbstractValue $last_container Container
     * @param Value\AbstractValue $value          Value
     *
     * @throws Error\Parse
     * @return void
     */
    protected function setValue(
        &$possible_key,
        Value\AbstractValue &$last_container,
        Value\AbstractValue &$value
    ) {
        switch ($this->string[$this->pointer]) {
            case 'i':
                $value = $this->parseValueInteger();
                break;
            case 'l':
                $value = $this->parseValueList();
                break;
            case 'd':
                $value = $this->parseValueDictionary();
                break;
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
                $value = $this->parseValueString();
                break;
            case 'e':
                if (0 == count($this->container_stack)) {
                    $this->throwParseErrorAtPointer("Unexpected ending.");
                }
                if (isset($possible_key)) {
                    // If we have a saved possible key,
                    // it means that the number of values in
                    // a dictionary is odd, that is, there is no value for a key.
                    $this->throwParseErrorAtPointer("Incomplete dictionary.");
                }

                // We remove the deepest container from the stack.
                // This might be the final value.
                $last_container = array_pop($this->container_stack);
                $value = null;
                ++$this->pointer;

                break;
            default:
                $this->throwParseErrorAtPointer("Invalid value.");
        }
    }

    /**
     * Parses an integer type at the current cursor position and proceeds the cursor.
     *
     * @throws Error\Parse In case of parse error.
     * @return Value\Integer
     */
    protected function parseValueInteger()
    {
        // This can be FALSE or 0, both are wrong.
        if (0 == ($end_pointer = strpos($this->string, 'e', $this->pointer))) {
            $this->throwParseErrorAtPointer("Missing ending in integer.");
        }

        $value = new Value\Integer(
            substr(
                $this->string,
                ($this->pointer + 1),
                ($end_pointer - $this->pointer - 1)
            )
        );
        $this->pointer = $end_pointer + 1;

        return $value;
    }

    /**
     * Parses a string type at the current cursor position and proceeds the cursor.
     *
     * @throws Error\Parse In case of parse error.
     * @return Value\StringValue
     */
    protected function parseValueString()
    {
        // This can be FALSE or 0, both are wrong.
        if (0 == ($colon_pointer = strpos($this->string, ':', $this->pointer))) {
            $this->throwParseErrorAtPointer("Missing colon in string.");
        }

        $length = substr(
            $this->string,
            $this->pointer,
            ($colon_pointer - $this->pointer)
        );
        if (!(strlen($length) < 20
            && is_numeric($length)
            && is_int(($length + 0))
            && $length >= 0)
        ) {
            $this->throwParseErrorAtPointer("Invalid length definition in string.");
        }

        $value = new Value\StringValue(
            substr($this->string, ($colon_pointer + 1), $length)
        );
        $this->pointer = $colon_pointer + $length + 1;

        return $value;
    }

    /**
     * Parses a list type at the current cursor position and proceeds the cursor.
     *
     * The list is initialized as empty, and will be populated with the upcoming
     * values.
     *
     * @return Value\ListValue
     */
    protected function parseValueList()
    {
        ++$this->pointer;
        return new Value\ListValue();
    }

    /**
     * Parses a dictionary type at the current cursor position
     * and proceeds the cursor.
     *
     * The dictionary is initialized as empty, and will be populated with the
     * upcoming values.
     *
     * @return Value\Dictionary
     */
    protected function parseValueDictionary()
    {
        ++$this->pointer;
        return new Value\Dictionary();
    }

    /**
     * Throws parse error
     *
     * @param string $error Error message
     *
     * @throws Error\Parse
     * @return void
     */
    protected function throwParseErrorAtPointer($error)
    {
        throw new Error\Parse(
            "Bencode parse error at pointer {$this->pointer}. " .
            $error,
            $this->pointer
        );
    }
}
