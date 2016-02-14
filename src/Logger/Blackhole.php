<?php

namespace StealThisShow\StealThisTracker\Logger;

/**
 * Logger class to serve as stupid interface of logging - no data is saved.
 *
 * @package    StealThisTracker
 * @subpackage Logger
 * @author     StealThisShow <info@stealthisshow.com>
 * @license    https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class Blackhole implements LoggerInterface
{

    /**
     * Implementing message logging, doing nothing.
     *
     * @param string $message Message
     *
     * @return void
     */
    public function logMessage( $message )
    {
    }

    /**
     * Implementing error logging, doing nothing.
     *
     * @param string $message Message
     *
     * @return void
     */
    public function logError( $message )
    {
    }
}
