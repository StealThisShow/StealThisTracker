<?php

namespace StealThisShow\StealThisTracker\Logger;

use StealThisShow\StealThisTracker\Config;

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
     * Initializes the object with the config class.
     *
     * @param Config\ConfigInterface $config
     */
    public function  __construct( Config\ConfigInterface $config = null );

    /**
     * Method to save non-error text message.
     *
     * @param string $message
     */
    public function logMessage( $message );

    /**
     * Method to save text message represening error.
     *
     * @param string $message
     */
    public function logError( $message );
}
