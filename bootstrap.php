<?php

define('PROJECTROOT', realpath(APPPATH.'../../').'/');
define('CMFPATH', PKGPATH.'cmf/');

// Hyphens to underscores
\Config::set('security.uri_filter', array_merge(
	array('\CMF::hyphens_to_underscores'),
    \Config::get('security.uri_filter')
));

// Add in the 404 route which routes through the CMF
\Router::add(array( '_404_' => 'base/404' ));

// Load cmf config
\Config::load('cmf', true);

// Quick and easy profiling using 'debug' in the query string
if (isset($_GET['debug']) && !\Fuel::$profiling) {
	\Fuel::$profiling = true;
	\Profiler::init();
	\Config::load('db', true);
	\Config::set('db.default.profiling', true);
}

// Listen for events at the beginning of the request for caching
\Event::register('controller_started', 'CMF\\Cache::start');

// Load up the required packages
Package::load(array('email', 'parser', 'sprockets'));

\Config::load('sprockets', true);
\Config::load(CMFPATH.'config/sprockets.php', 'sprockets');
$assets_dir = \Config::get('sprockets.asset_compile_dir');

// Check the compiled assets dir is working
if (!is_dir($assets_dir)) {
	@mkdir($assets_dir);
	@mkdir($assets_dir.'js');
	@mkdir($assets_dir.'css');
	@mkdir($assets_dir.'img');
}

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