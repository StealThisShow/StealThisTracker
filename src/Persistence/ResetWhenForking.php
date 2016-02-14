<?php

namespace StealThisShow\StealThisTracker\Persistence;

/**
 * When a persistence object is used in a forked process and implements this
 * interface, resetAfterForking will be called after the fork to, for example,
 * re-establish database connections.
 *
 * @package    StealThisTracker
 * @subpackage Persistence
 * @author     StealThisShow <info@stealthisshow.com>
 * @licence    https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */
interface ResetWhenForking
{
    /**
     * To be called after the child-process is forked.
     *
     * @return void
     */
    public function resetAfterForking();
}
