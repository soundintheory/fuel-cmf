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

if (\Config::get('cmf.languages.enabled')) {
	\Config::load('db', true);
	$extensions = \Config::get('db.doctrine2.extensions');
	if (!is_array($extensions)) $extensions = array();
	if (!in_array('CMF\\Doctrine\\Extensions\\Translatable', $extensions))
		$extensions[] = 'CMF\\Doctrine\\Extensions\\Translatable';
	\Config::set('db.doctrine2.extensions', $extensions);
}

// Check if custom module urls have been set
$originalUri = !\Fuel::$is_cli ? \CMF::original_uri() : '';
$isAdmin = (!\Fuel::$is_cli && strpos(ltrim($_SERVER['REQUEST_URI'], '/'), trim(\CMF::adminPath(), '/')) === 0);
if ($isAdmin) {
	\Config::set('security.uri_filter', array_merge( array('\Admin::base_url_filter', '\Admin::module_url_filter'), \Config::get('security.uri_filter') ));
} else if (\Config::get('cmf.module_urls', false) !== false) {
	\Config::set('security.uri_filter', array_merge( array('\CMF::module_url_filter'), \Config::get('security.uri_filter') ));
}

// Load up the required packages
Package::load(array('email', 'parser'));

// Override some external classes
Autoloader::add_core_namespace('CMF\\Core', true);
Autoloader::add_classes(array(
    'CMF\\Core\\View_Twig'  => __DIR__.'/classes/CMF/Core/View_Twig.php',
    'CMF\\Core\\Image_Driver'  => __DIR__.'/classes/CMF/Core/Image_Driver.php',
    'CMF\\Core\\Lang'  => __DIR__.'/classes/CMF/Core/Lang.php',
    'CMF\\Core\\Format'  => __DIR__.'/classes/CMF/Core/Format.php',
    'CMF\\Core\\FuelError'  => __DIR__.'/classes/CMF/Core/FuelError.php',
    'CMF\\Core\\HttpException'  => __DIR__.'/classes/CMF/Core/HttpException.php'
));

// Sort out the language
\Lang::load('errors', true);
\Lang::load('admin', true);
\Lang::load('site', true);
$lang = \CMF::lang();

// Quick and easy profiling using 'debug' in the query string
if (isset($_GET['debug']) && !\Fuel::$profiling) {
	\Fuel::$profiling = true;
	\Profiler::init();
	\Config::load('db', true);
	\Config::set('db.default.profiling', true);
}

// Clean annoying path out of GET vars
if (!\Fuel::$is_cli && !empty($_GET)) {
    if (isset($_GET[$originalUri]) && empty($_GET[$originalUri])) {
        unset($_GET[$originalUri]);
    }
}

// Listen for events at the beginning of the request for caching
\Event::register('controller_started', 'CMF\\Cache::start');

// Add CMF's modules directory so it's modules can be autoloaded
$module_paths = Config::get('module_paths');
$module_paths[] = CMFPATH.'modules/';
Config::set('module_paths', $module_paths);

if ($isAdmin) {
	\Admin::initialize();
} else if (!\Fuel::$is_cli && strpos($originalUri, '/admin') === 0) {
    throw new \Fuel\Core\HttpNotFoundException();
}