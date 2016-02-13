<?php

namespace StealThisShow\StealThisTracker\Logger;

/**
 * Interface used to log events in different classes of the library.
 *
 * Feel free to implement your own logger with StealThisTracker_Logger_Interface.
 *
 * @package StealThisTracker
 * @subpackage Logger
 */
interface LoggerInterface
{
    /**
     * Method to save non-error text message.
     *
     * @param string $message
     */
    public function logMessage( $message );

    /**
     * Method to save text message representing error.
     *
     * @param string $message
     */
    public function logError( $message );
}
