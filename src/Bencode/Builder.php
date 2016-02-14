<?php

namespace StealThisShow\StealThisTracker\Bencode;

use StealThisShow\StealThisTracker\Bencode\Error;
use StealThisShow\StealThisTracker\Bencode\Value;

/**
 * Class creating bencoded values out of PHP values (arrays, scalars).
 *
 * @package    StealThisTracker
 * @subpackage Bencode
 * @author     StealThisShow <info@stealthisshow.com>
 * @licence    https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class Builder
{
    /**
     * Given an input value, converts it to a bencode value class.
     *
     * @param mixed $input Any PHP scalar or array containing arrays of scalars.
     *
     * @throws Error\Build
     * @return Value\AbstractValue
     */
    static public function build($input)
    {
        if (is_int($input)) {
            return new Value\Integer($input);
        }
        if (is_string($input)) {
            return new Value\StringValue($input);
        }
        if (is_array($input)) {
            // Creating sub-elements to construct list/dictionary.
            $constructor_input = array();
            foreach ($input as $key => $value) {
                $constructor_input[$key] = self::build($value);
            }

            if (self::isDictionary($input)) {
                return new Value\Dictionary($constructor_input);
            } else {
                return new Value\ListValue($constructor_input);
            }
        }

        throw new Error\Build(
            "Invalid input type when building: " . gettype($input)
        );
    }

    /**
     * Tries to tell if an array is associative or an indexed list.
     *
     * @param array $array Dictionary
     *
     * @return boolean True if the array looks like associative.
     */
    static public function isDictionary(array $array)
    {
        // Checking if the keys are ordered numbers starting from 0.
        return array_keys($array) !== range(0, (count($array) - 1));
    }
}
