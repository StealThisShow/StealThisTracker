<?php

namespace StealThisShow\StealThisTracker\Logger;

/**
 * Logger class appending messages to a file or files.
 *
 * @package    StealThisTracker
 * @subpackage Logger
 * @author     StealThisShow <info@stealthisshow.com>
 * @license    https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
class File implements LoggerInterface
{

    /**
     * Path of the log file for normal messages.
     *
     * @var string
     */
    protected $file_path_messages;

    /**
     * Path of the log file for error messages.
     *
     * @var string
     */
    protected $file_path_errors;

    /**
     * Default log file path. If not specified, 
     * the same is used for messages and errors.
     */
    const DEFAULT_LOG_PATH = '/var/log/stealthistracker.log';

    /**
     * Initializes the object.
     *
     * File logging can use 'file_path_messages' and file_path_errors params,
     * or logs to self::DEFAULT_LOG_PATH by default (both errors and messages).
     *
     * @param string $file_path_messages File path
     * @param string $file_path_errors   Error path
     */
    public function __construct(
        $file_path_messages = self::DEFAULT_LOG_PATH, 
        $file_path_errors = null
    ) {
        $this->file_path_messages = $file_path_messages;
        $this->file_path_errors = $file_path_errors;

        if ($file_path_errors === null) {
            $this->file_path_errors = $file_path_messages;
        }
    }

    /**
     * Method to save non-error text message.
     *
     * @param string $message Message
     *
     * @return void
     */
    public function logMessage($message)
    {
        $this->write($message);
    }

    /**
     * Method to save text message representing error.
     *
     * @param string $message Message
     *
     * @return void
     */
    public function logError($message)
    {
        $this->write($message, true);
    }

    /**
     * Writing operation to the log file.
     *
     * @param string  $message Log message to write.
     * @param boolean $error   If true, we are using the error log,
     *                         if not, the normal.
     *
     * @return void
     */
    protected function write($message, $error = false)
    {
        $path = $error ? $this->file_path_errors : $this->file_path_messages;
        file_put_contents(
            $path,
            $this->formatMessage($message, $error),
            FILE_APPEND
        );
    }

    /**
     * Formats log message adding timestamp and EOL, escaping new lines.
     *
     * @param string  $message Log message to format.
     * @param boolean $error   If true, [ERROR] prefix is added.
     *
     * @return string
     */
    protected function formatMessage($message, $error)
    {
        return date("[Y-m-d H:i:s] ") .
        ($error ? '[ERROR] ' : '') .
        addcslashes($message, "\n\r") .
        PHP_EOL;
    }

    /**
     * Set file path
     *
     * @param string $file_path_messages File path
     *
     * @return File
     */
    public function setFilePathMessages($file_path_messages)
    {
        $this->file_path_messages = $file_path_messages;
        return $this;
    }

    /**
     * Set error path
     *
     * @param string $file_path_errors Error path
     *
     * @return File
     */
    public function setFilePathErrors($file_path_errors)
    {
        $this->file_path_errors = $file_path_errors;
        return $this;
    }
}
