<?php

namespace StealThisShow\StealThisTracker\Logger;

/**
 * Interface used to log events in different classes of the library.
 *
 * Feel free to implement your own logger with StealThisTracker_Logger_Interface.
 *
 * @package    StealThisTracker
 * @subpackage Logger
 * @author     StealThisShow <info@stealthisshow.com>
 * @license    https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
interface LoggerInterface
{
    /**
     * Method to save non-error text message.
     *
     * @param string $message Message
     *
     * @return void
     */
    public function logMessage($message);

    /**
     * Method to save text message representing error.
     *
     * @param string $message Message
     *
     * @return void
     */
    public function logError($message);
}
