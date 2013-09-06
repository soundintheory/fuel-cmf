<?php

namespace CMF\Composer;

use Composer\Composer;
use Composer\Script\Event;
use Composer\Package\PackageInterface;

/**
 * Provides hooks for automatic install during composer install
 * 
 * @package  CMF\Composer
 */
class Hooks
{
    protected static $task;
	
	/**
	 * post-install hook for composer - will run the initial installer process
	 * 
	 * @param  \Composer\Script\Event $event
	 * @return void
	 */
    public static function postInstall(Event $event)
    {
        static::bootstrap();
        static::$task->install();
    }
    
    /**
     * Bootstrap the fuel environment and load the cmf package
     */
    protected static function bootstrap()
    {
        $dir = rtrim(realpath(__DIR__.'/../../../../../../'), '/').'/';
    	
    	error_reporting(-1);
    	ini_set('display_errors', 1);
    	
    	define('DOCROOT', $dir.'public/');
    	define('APPPATH', $dir.'fuel/app/');
    	define('PKGPATH', $dir.'fuel/packages/');
    	define('COREPATH', $dir.'fuel/core/');
    	
    	// Get the start time and memory for use later
    	defined('FUEL_START_TIME') or define('FUEL_START_TIME', microtime(true));
    	defined('FUEL_START_MEM') or define('FUEL_START_MEM', memory_get_usage());
    	
    	// Boot the app and the CMF
    	require APPPATH.'bootstrap.php';
    	\Package::load(array('oil', 'cmf'));
        
        // Instantiate CMF's fuel task class
        include(CMFPATH.'tasks/cmf.php');
        static::$task = new \Fuel\Tasks\Cmf();
    }
}