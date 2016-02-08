<?php

namespace StealThisShow\StealThisTracker\Logger;

use StealThisShow\StealThisTracker\Config;

/**
 * Logger class to serve as stupid interface of logging - no data is saved.
 *
 * @package StealThisTracker
 * @subpackage Logger
 */
class Blackhole implements LoggerInterface
{
    /**
     * Implementing constructor, doing nothing.
     *
     * @param Config\ConfigInterface $config
     */
    public function  __construct( Config\ConfigInterface $config = null )
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
