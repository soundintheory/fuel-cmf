<?php

namespace CMF\Core;


class View_Twig extends \Parser\View_Twig
{
	protected static $_loader_class = '\\CMF\\Twig\\FilesystemLoader';

	
	/**
	 * Allows access to the Twig loader, which is useful if you need to get a list of all loaded templates
	 * @return \CMF\Twig\FilesystemLoader
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
        static::$_parser_loader = new static::$_loader_class($views_paths);
	}
	
	/**
	 * Allows the setting of the loader to a different kind
	 * @param \Twig\Loader\LoaderInterface
	 */
	public static function setLoader($loader)
	{
		static::$_parser_loader = $loader;
	}
	
}