<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

use StealThisShow\StealThisTracker\Bencode\Error;

/**
 * Decoded bencode dictionary, consisting of key-value pairs.
 *
 * @package    StealThisTracker
 * @subpackage Bencode
 * @author     StealThisShow <info@stealthisshow.com>
 * @license    https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class Dictionary extends Container
{
    /**
     * Adds an item to the dictionary.
     *
     * @param AbstractValue $sub_value SubValue
     * @param StringValue   $key       Key
     *
     * @throws Error\InvalidType
     * @throws Error\InvalidValue
     *
     * @return void
     */
    public function contain( AbstractValue $sub_value, StringValue $key = null )
    {
        if (!isset($key)) {
            throw new Error\InvalidType(
                "Invalid key value for dictionary: $sub_value"
            );
        }
        if (isset($this->value[$key->value])) {
            throw new Error\InvalidValue("Duplicate key in dictionary: $key->value");
        }
        $this->value[$key->value] = $sub_value;
    }

    /**
     * Convert the object back to a bencoded string when used as string.
     *
     * @return string
     */
    public function __toString()
    {
        // All keys must be byte strings and must appear in lexicographical order.
        ksort($this->value);

        $string_represent = "d";
        foreach ($this->value as $key => $sub_value) {
            $key = new StringValue($key);
            $string_represent .=  $key . $sub_value;
        }
        return $string_represent . "e";
    }
}
