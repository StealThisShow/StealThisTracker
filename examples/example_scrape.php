<?php
/**
 * Scrape example
 *
 * @package StealThisTracker
 * @author  StealThisShow <info@stealthisshow.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use \StealThisShow\StealThisTracker\Core;
use \StealThisShow\StealThisTracker\Persistence\Pdo;

// ---------------------------------------
// This is how to set up a scrape URL.
// ---------------------------------------

// Composer autoloader
require dirname(__FILE__).'/../vendor/autoload.php';

// Persistence object implementing PersistenceInterface.
// We use Pdo here.
$persistence = new Pdo('sqlite:sqlite_example.db');

// Core class managing the scrapes.
$core = new Core($persistence);

// We simply send back the results of the scrape method to the client.
echo (string) $core->scrape($_GET);