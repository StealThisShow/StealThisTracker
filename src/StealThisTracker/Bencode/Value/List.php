<?php

/**
 * Decoded bencode list, consisting of mutiple values.
 *
 * @package StealThisTracker
 * @subpackage Bencode
 */
class StealThisTracker_Bencode_Value_List extends StealThisTracker_Bencode_Value_Container
{
    /**
     * Adds an item to the list.
     *
     * @param StealThisTracker_Bencode_Value_Abstract $sub_value
     * @param StealThisTracker_Bencode_Value_String $key Not used here.
     */
    public function contain( StealThisTracker_Bencode_Value_Abstract $sub_value, StealThisTracker_Bencode_Value_String $key = null )
    {
        $this->value[] = $sub_value;
    }

    /**
     * Convert the object back to a bencoded string when used as string.
     */
    public function __toString()
    {
        $string_represent = "l";
        foreach ( $this->value as $sub_value )
        {
            $string_represent .= $sub_value;
        }
        return $string_represent . "e";
    }
}
