<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

use StealThisShow\StealThisTracker\Bencode\Error;

/**
 * Decoded bencode string, representing an ordered set of bytes.
 *
 * @package    StealThisTracker
 * @subpackage Bencode
 * @author     StealThisShow <info@stealthisshow.com>
 * @license    https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class StringValue extends AbstractValue
{
    /**
     * Initializing the object with its parsed value.
     *
     * @param string $value Value
     *
     * @throws Error\InvalidType In the value is not a string.
     */
    public function __construct( $value )
    {
        if (!is_string($value)) {
            throw new Error\InvalidType(
                "Invalid string value: " . var_export($value, true)
            );
        }
        $this->value = $value;
    }

    /**
     * Convert the object back to a bencoded string when used as string.
     *
     * @return string
     */
    public function __toString()
    {
        return strlen($this->value) . ":" . $this->value;
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
