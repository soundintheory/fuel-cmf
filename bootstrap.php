<?php

define('CMFPATH', PKGPATH.'cmf/');

// Set up twig to be loaded from here
\Config::load('parser', true);
\Config::load('cmf', true);

// Enable classes in 'CMF\Core' to automatically override classes in 'Fuel\Core'
Autoloader::add_core_namespace('CMF\\Core', true);

Autoloader::add_classes(array(
    // PHPass
    'PasswordHash' => __DIR__.'/vendor/phpass/PasswordHash.php',
    // Overriding core classes
    'CMF\\Core\\Upload'  => __DIR__.'/classes/Core/Upload.php',
    'Fuel\\Core\\Config_Php'  => __DIR__.'/classes/Core/Config_Php.php'
));

// Load up the required packages
Package::load(array('email', 'parser', 'doctrine','oil'));

// Add CMF's modules directory so it's modules can be autoloaded
$module_paths = Config::get('module_paths');
$module_paths[] = CMFPATH.'modules/';
Config::set('module_paths', $module_paths);

// Add the nice capitalised cmf namespace
Autoloader::add_namespace('CMF', CMFPATH.'classes/', true);
Autoloader::add_namespace('Phly', __DIR__.'/vendor/Phly/', true);

// Alias some of the cmf classes to root namespace
Autoloader::alias_to_namespace('CMF\\CMF');
Autoloader::alias_to_namespace('CMF\\Admin');