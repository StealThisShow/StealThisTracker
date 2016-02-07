<?php

/**
 * Logger class to serve as stupid interface of logging - no data is saved.
 *
 * @package StealThisTracker
 * @subpackage Logger
 */
class StealThisTracker_Logger_Blackhole implements StealThisTracker_Logger_Interface
{
    /**
     * Implementing constructor, doing nothing.
     *
     * @param StealThisTracker_Config_Interface $config
     */
    public function  __construct( StealThisTracker_Config_Interface $config = null )
    {
    }

    /**
     * Implementing message logging, doing nothing.
     *
     * @param type $message
     */
    public function logMessage( $message )
    {
    }

    public function logError( $message )
    {
    }
}
