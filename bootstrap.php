<?php

define('CMFPATH', PKGPATH.'cmf/');

// Set up twig to be loaded from here
\Config::load('parser', true);
\Config::load('cmf', true);

if (isset($_GET['debug']) && !\Fuel::$profiling) {
	\Fuel::$profiling = true;
	\Profiler::init();
	\Config::load('db', true);
	\Config::set('db.default.profiling', true);
}

// Listen for events at the beginning of the request for caching
\Event::register('controller_started', 'CMF\\Cache::start');

// Load up the required packages
Package::load(array('email', 'parser'));

// Override some external classes
Autoloader::add_core_namespace('CMF\\Core', true);
Autoloader::add_classes(array(
    'CMF\\Core\\Upload'  => __DIR__.'/classes/CMF/Core/Upload.php',
    'CMF\\Core\\View_Twig'  => __DIR__.'/classes/CMF/Core/View_Twig.php'
));

// Add CMF's modules directory so it's modules can be autoloaded
$module_paths = Config::get('module_paths');
$module_paths[] = CMFPATH.'modules/';
Config::set('module_paths', $module_paths);

// Alias some of the cmf classes to root namespace
Autoloader::alias_to_namespace('CMF\\CMF');
Autoloader::alias_to_namespace('CMF\\Admin');