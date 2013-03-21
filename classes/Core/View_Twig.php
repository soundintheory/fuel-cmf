<?php

namespace CMF\Core;

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
	 * Allows the setting of the loader to a different kind
	 * @param Twig_LoaderInterface
	 */
	public static function setLoader($loader)
	{
		static::$_parser_loader = $loader;
	}
	
}