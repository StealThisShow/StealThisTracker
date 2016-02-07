<?php

/**
 * Test class for StealThisTracker_Autoloader.
 */
class StealThisTracker_AutoloaderTest extends PHPUnit_Framework_TestCase
{
    public function testAutoload()
    {
        $this->assertFalse(class_exists('FooBarFoo'), '->autoload() does not try to load classes that does not begin with StealThisTracker');

        $autoloader = new StealThisTracker_Autoloader();
        $this->assertNull($autoloader->autoload('Foo'), '->autoload() returns false if it is not able to load a class');
    }
}
