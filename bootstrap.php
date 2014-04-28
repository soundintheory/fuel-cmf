<?php

define('CMFPATH', PKGPATH.'cmf/');

// Set up twig to be loaded from here
\Config::load('parser', true);
\Config::load('cmf', true);

// Add the nice capitalised cmf namespace
Autoloader::add_namespace('CMF', CMFPATH.'classes/', true);
Autoloader::add_namespace('Symfony\\Component\\Process', __DIR__.'/vendor/Symfony/Component/Process/', true);
Autoloader::add_namespace('FFMpeg', __DIR__.'/vendor/FFMpeg/', true);

// Listen for events at the beginning of the request for caching
\Event::register('controller_started', 'CMF\\Cache::start');

// Load up the required packages
Package::load(array('email', 'parser', 'doctrine', 'oil'));

// Enable classes in 'CMF\Core' to automatically override classes in 'Fuel\Core'
Autoloader::add_core_namespace('CMF\\Core', true);

Autoloader::add_classes(array(
    // PHPass
    'PasswordHash' => __DIR__.'/vendor/phpass/PasswordHash.php',
    // Overriding core classes
    'CMF\\Core\\View_Twig'  => __DIR__.'/classes/Core/View_Twig.php',
    'Twig_Loader_Filesystem'  => __DIR__.'/classes/Twig/Twig_Loader_Filesystem.php',
    'Doctrine\\ORM\\Mapping\\Column'  => __DIR__.'/classes/Doctrine/Mapping/Column.php',
    // For parsing SQL in the automatic cache driver
    'PHPSQLParser' => __DIR__.'/vendor/PHPSQLParser/php-sql-parser.php'
));

// Add CMF's modules directory so it's modules can be autoloaded
$module_paths = Config::get('module_paths');
$module_paths[] = CMFPATH.'modules/';
Config::set('module_paths', $module_paths);

// Alias some of the cmf classes to root namespace
Autoloader::alias_to_namespace('CMF\\CMF');
Autoloader::alias_to_namespace('CMF\\Admin');