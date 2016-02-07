<?php

/**
 * One piece of a decoded bencode container. Could be dictionary or list.
 *
 * @package StealThisTracker
 * @subpackage Bencode
 */
abstract class StealThisTracker_Bencode_Value_Container extends StealThisTracker_Bencode_Value_Abstract
{
    /**
     * Intializing the object with its parsed value.
     *
     * Value is iterated and its values (and keys) are getting contained by the object.
     *
     * @param array $value
     */
    public function __construct( array $value = null )
    {
        $this->value = array();

        if ( !isset( $value ) )
        {
            return;
        }

        if ( StealThisTracker_Bencode_Builder::isDictionary( $value ) )
        {
            foreach( $value as $key => $sub_value )
            {
                $this->contain( $sub_value, new StealThisTracker_Bencode_Value_String( $key ) );
            }
        }
        else
        {
            foreach( $value as $sub_value )
            {
                $this->contain( $sub_value );
            }
        }
    }

    /**
     * Represent the value of the object as PHP arrays and scalars.
     */
    public function represent()
    {
        $representation = array();
        foreach ( $this->value as $key => $sub_value )
        {
            $representation[$key] = $sub_value->represent();
        }
        return $representation;
    }

    /**
     * Adds an item to the list/dictionary.
     *
     * @param StealThisTracker_Bencode_Value_Abstract $sub_value
     * @param StealThisTracker_Bencode_Value_String $key Only used for dictionaries.
     */
    abstract public function contain( StealThisTracker_Bencode_Value_Abstract $sub_value, StealThisTracker_Bencode_Value_String $key = null );
}
