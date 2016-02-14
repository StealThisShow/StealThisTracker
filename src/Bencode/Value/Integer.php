<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

use StealThisShow\StealThisTracker\Bencode\Error;

/**
 * Decoded bencode integer, representing a number.
 *
 * @package    StealThisTracker
 * @subpackage Bencode
 * @author     StealThisShow <info@stealthisshow.com>
 * @licence    https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class Integer extends AbstractValue
{
    /**
     * Initializing the object with its parsed value.
     *
     * @param integer $value Value
     *
     * @throws Error\InvalidType In the value is not an integer.
     */
    public function __construct( $value )
    {
        if (!(is_numeric($value) && is_int(($value + 0)))) {
            throw new Error\InvalidType("Invalid integer value: $value");
        }
        $this->value = intval($value);
    }

    /**
     * Convert the object back to a bencoded string when used as string.
     *
     * @return string
     */
    public function __toString()
    {
        return "i" . $this->value . "e";
    }

    /**
     * Represent the value of the object as PHP scalar.
     *
     * @return mixed
     */
    public function represent()
    {
        return $this->value;
    }
}
