<?php

namespace StealThisShow\StealThisTracker\Concurrency;

/**
 * Declaring ticks to pass between callbacks.
 *
 * Setting this higher than 1 to always listen to process exists even if they
 * happen quickly after each other, ie. always having pcntl_wait being executed
 * when a child process exists.
 * Therefore this is more or less the ticks elapsed
 * between pcntl_fork and pcntl_wait.
 */
declare(ticks = 10);

/**
 * Class to fork its process to N child processes executing the same code.
 *
 * Ideal for maintaining one listening socket and accept connections in multiple
 * processes.
 *
 * @package    StealThisTracker
 * @subpackage Concurrency
 * @author     StealThisShow <info@stealthisshow.com>
 * @license    https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
abstract class Forker
{
    /**
     * Number of child processes wanted.
     *
     * @var integer
     */
    protected $wanted_children;

    /**
     * Array of active child processes' PIDs. Keys represent "slot" indexes.
     *
     * @var array
     */
    protected $children = array();

    /**
     * Executing setup method of the inheriting class, then fork child processes.
     *
     * The number of children forked is a number returned 
     * by the constructorParentProcess method of the inheriting class. 
     * If it's negative, processes are automatically recreated.
     * The method passes all its parameters to the setup method of the inheriting
     * class.
     * 
     * @return void
     */
    final public function start()
    {
        $arguments = func_get_args();

        // Calling parent set-up method with the same parameters as this constructor.
        $this->wanted_children = call_user_func_array(
            array($this, 'startParentProcess'), $arguments
        );

        // If children are negative, they are automatically recreated when terminate.
        $permanent = $this->wanted_children < 0;
        $this->wanted_children = (int) abs($this->wanted_children);

        $this->forkChildren($this->wanted_children, $permanent);
    }

    /**
     * Detaches process from console and starts forking.
     *
     * Requires php-posix!
     *
     * @see self::start()
     *
     * @return mixed
     * @throws Error
     */
    final public function startDetached()
    {
        // Forking one child process and closing the parent,
        // because if the parent is already a session leader, it cannot leave it.
        // It is because session group and process group has the same ID as their
        // leader process. Now if you assign the leader process to another
        // session/process group, the IDs will collide.
        $this->fork(true);

        // Becoming leader of a new session/process group - detaching from shell.
        $sid = posix_setsid();
        if (false === $sid) {
            throw new Error('Unable to become session leader (detaching).');
        }

        // We have to handle hangup signals (send when session leader terminates),
        // otherwise it makes child process to stop.
        pcntl_signal(SIGHUP, SIG_IGN);

        // Forking again for not being session/process group leaders
        // will disallow the process
        // to "accidentally" open a controlling terminal for itself (System V OSs).
        $this->fork(true);

        // Releasing current directory and closing open
        // file descriptors (standard IO).
        chdir('/');
        fclose(STDIN);
        fclose(STDOUT);

        // PHP still thinks we are a webpage, and since we closed standard output,
        // whenever we echo, it will assume that the client abandoned the connection,
        // so it silently stops running.
        // We can tell it here not to do it.
        ignore_user_abort(true);

        // Let the world know about our process ID in a standard way.
        file_put_contents('/var/run/stealthistracker', getmypid());

        // Finally we start the procedure as we would without detaching.
        $arguments = func_get_args();
        return call_user_func_array(array($this, 'start'), $arguments);
    }

    /**
     * Initializing method to call before forking. Gets params from constructor.
     *
     * @return Number of forks to create. If negative,
     * forks are recreated when exiting and absolute values is used.
     */
    abstract public function startParentProcess();

    /**
     * Initializing method to call after forking. Called on each children.
     *
     * @param integer $slot The slot (numbered index) of the fork.
     * Reused when recreating process.
     *
     * @return mixed
     */
    abstract public function startChildProcess($slot);

    /**
     * Forking N child processes, initializing them and maintaining their number.
     *
     * This method constantly monitors exiting child processes and recreates them.
     *
     * @param integer $n_children Number of children to fork first.
     * @param boolean $permanent  If true, exiting processes will be recreated.
     *
     * @throws Error When forking is unsuccessful.
     * @return mixed
     */
    public function forkChildren($n_children, $permanent)
    {
        $status = null;

        if (0 >= $n_children) {
            return false;
        }

        do {
            for ($slot = 0; $slot < $n_children; ++$slot) {
                if (isset($this->children[$slot])) {
                    // Process already running in this slot.
                    continue;
                }

                $pid = $this->fork();

                if ($pid) {
                    $this->children[$slot] = $pid;
                } else {
                    return $this->startChildProcess($slot);
                }
            }

            while (!$permanent && pcntl_wait($status)) {
                // If we don't need to recreate child processes on exit
                // we just wait for them to die to avoid zombies.
                continue;
            }

            $pid_exit = pcntl_wait($status); // Check the status?

            if (false !== ($slot = array_search($pid_exit, $this->children))) {
                unset($this->children[$slot]);
            }
        } while (true);

        return false;
    }

    /**
     * Forks the currently running process.
     *
     * @param bool $exit_parent Exit if parent process
     *
     * @return int Forked process ID or 0 if you are in the child already.
     * @throws Error if the forking is unsuccessful.
     */
    protected function fork($exit_parent = false)
    {
        $pid = pcntl_fork();

        if (-1 == $pid) {
            throw new Error('Unable to fork.');
        } elseif ($exit_parent && $pid > 0) {
            // We are in the parent, so we terminate.
            exit(0);
        }

        return $pid;
    }
}
