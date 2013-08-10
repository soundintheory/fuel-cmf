<?php

namespace CMF\Composer;

use Composer\Composer;
use Composer\Package\PackageInterface;

/**
 * Provides hooks for automatic install during composer install
 * 
 * @package  CMF\Composer
 */
class Hooks
{
	
	/**
	 * post-install hook for composer - will run the initial installer process
	 * 
	 * @param  PackageInterface $package
	 * @param  Composer         $composer
	 * @return void
	 */
    public static function postInstall(PackageInterface $package, Composer $composer)
    {
        static::bootstrap();
    }
    
    /**
     * post-update hook for composer - will trigger the installer to run again
     * and fill in any potential gaps
     * 
     * @param  PackageInterface $package
     * @param  Composer         $composer
     * @return void
     */
    public static function postUpdate(PackageInterface $package, Composer $composer)
    {
        static::bootstrap();
    }
    
    /**
     * Bootstrap the fuel environment and load the cmf package
     */
    protected static function bootstrap()
    {
    	print('DIR: '.__DIR__.DIRECTORY_SEPARATOR."\n");
    	return false;
    	
    	error_reporting(-1);
    	ini_set('display_errors', 1);
    	
    	define('DOCROOT', __DIR__.DIRECTORY_SEPARATOR);
    	define('APPPATH', realpath(__DIR__.'/../fuel/app/').DIRECTORY_SEPARATOR);
    	define('PKGPATH', realpath(__DIR__.'/../fuel/packages/').DIRECTORY_SEPARATOR);
    	define('COREPATH', realpath(__DIR__.'/../fuel/core/').DIRECTORY_SEPARATOR);
    	
    	// Get the start time and memory for use later
    	defined('FUEL_START_TIME') or define('FUEL_START_TIME', microtime(true));
    	defined('FUEL_START_MEM') or define('FUEL_START_MEM', memory_get_usage());
    	
    	// Boot the app and the CMF
    	require APPPATH.'bootstrap.php';
    	\Package::load('cmf');
    }
