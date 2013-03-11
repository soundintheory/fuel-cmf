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
	
}