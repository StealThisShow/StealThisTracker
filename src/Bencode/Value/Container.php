<?php

namespace StealThisShow\StealThisTracker\Bencode\Value;

use StealThisShow\StealThisTracker\Bencode;

/**
 * One piece of a decoded bencode container. Could be dictionary or list.
 *
 * @package    StealThisTracker
 * @subpackage Bencode
 */
abstract class Container extends AbstractValue
{
    /**
     * Initializing the object with its parsed value.
     *
     * Value is iterated and its values (and keys)
     * are getting contained by the object.
     *
     * @param array $value Value
     */
    public function __construct(array $value = null)
    {
        $this->value = array();

        if (!isset($value)) {
            return;
        }

        if (Bencode\Builder::isDictionary($value)) {
            foreach ($value as $key => $sub_value) {
                $this->contain($sub_value, new StringValue($key));
            }
        } else {
            foreach ($value as $sub_value) {
                $this->contain($sub_value);
            }
        }
    }

    /**
     * Represent the value of the object as PHP arrays and scalars.
     *
     * @return array|mixed
     */
    public function represent()
    {
        $representation = array();
        foreach ($this->value as $key => $sub_value) {
            $representation[$key] = $sub_value->represent();
        }
        return $representation;
    }

    /**
     * Adds an item to the list/dictionary.
     *
     * @param AbstractValue $sub_value Subvalue
     * @param StringValue   $key       Only used for dictionaries.
     *
     * @return void
     */
    abstract public function contain(
        AbstractValue $sub_value,
        StringValue $key = null
    );
}
