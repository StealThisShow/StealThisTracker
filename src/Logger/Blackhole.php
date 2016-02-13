<?php

namespace StealThisShow\StealThisTracker\Logger;

/**
 * Logger class to serve as stupid interface of logging - no data is saved.
 *
 * @package StealThisTracker
 * @subpackage Logger
 */
class Blackhole implements LoggerInterface
{

    /**
     * Implementing message logging, doing nothing.
     *
     * @param string $message
     */
    public function logMessage( $message )
    {
    }

    /**
     * Implementing error logging, doing nothing.
     *
     * @param string $message
     */
    public function logError( $message )
    {
    }
}
