<?php

namespace CMF\Core;

use Twig_Loader_Filesystem;

class View_Twig extends \Parser\View_Twig
{
	
	/**
	 * Allows access to the Twig loader, which is useful if you need to get a list of all loaded templates
	 * @return Twig_Loader_Filesystem
	 */
	public static function loader()
	{
		return static::$_parser_loader;
	}
	
	/**
	 * Initialises the filesystem loader with the paths specified in config
	 */
	public static function initLoader()
	{
		$views_paths = \Config::get('parser.View_Twig.views_paths', array(APPPATH . 'views'));
        static::$_parser_loader = new Twig_Loader_Filesystem($views_paths);
	}
	
	/**
	 * Allows the setting of the loader to a different kind
	 * @param Twig_LoaderInterface
	 */
	public static function setLoader($loader)
	{
		static::$_parser_loader = $loader;
	}
	
}