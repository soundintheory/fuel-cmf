<?php

define('PROJECTROOT', realpath(APPPATH.'../../').'/');
define('CMFPATH', PKGPATH.'cmf/');

// Alias some of the cmf classes to root namespace
Autoloader::alias_to_namespace('CMF\\CMF');
Autoloader::alias_to_namespace('CMF\\Admin');

// Add in the 404 route which routes through the CMF
\Router::add(array( '_404_' => 'base/catchall' ));

// Load cmf config
\Config::load('cmf', true);
\Lang::load('errors', true);
\Lang::load('admin', true);
\Lang::load('site', true);

// Check if custom module urls have been set
if (!\Fuel::$is_cli && strpos(ltrim($_SERVER['REQUEST_URI'], '/'), 'admin') === 0) {
	\Config::set('security.uri_filter', array_merge( array('\Admin::module_url_filter'), \Config::get('security.uri_filter') ));
} else if (\Config::get('cmf.module_urls', false) !== false) {
	\Config::set('security.uri_filter', array_merge( array('\CMF::module_url_filter'), \Config::get('security.uri_filter') ));
}

// Load up the required packages
Package::load(array('email', 'parser', 'sprockets'));

// Override some external classes
Autoloader::add_core_namespace('CMF\\Core', true);
Autoloader::add_classes(array(
    'CMF\\Core\\View_Twig'  => __DIR__.'/classes/CMF/Core/View_Twig.php',
    'CMF\\Core\\Image_Driver'  => __DIR__.'/classes/CMF/Core/Image_Driver.php',
    'CMF\\Core\\Lang'  => __DIR__.'/classes/CMF/Core/Lang.php',
    'CMF\\Core\\Format'  => __DIR__.'/classes/CMF/Core/Format.php',
    'CMF\\Core\\Error'  => __DIR__.'/classes/CMF/Core/Error.php',
    'CMF\\Core\\HttpException'  => __DIR__.'/classes/CMF/Core/HttpException.php'
));

// Sort out the language
$lang = \CMF::lang();

// Quick and easy profiling using 'debug' in the query string
if (isset($_GET['debug']) && !\Fuel::$profiling) {
	\Fuel::$profiling = true;
	\Profiler::init();
	\Config::load('db', true);
	\Config::set('db.default.profiling', true);
}

// Listen for events at the beginning of the request for caching
\Event::register('controller_started', 'CMF\\Cache::start');

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

// Add CMF's modules directory so it's modules can be autoloaded
$module_paths = Config::get('module_paths');
$module_paths[] = CMFPATH.'modules/';
Config::set('module_paths', $module_paths);