<?php

namespace CMF\Twig;

use Twig_Extension,
	Twig_Function_Function,
	Twig_Function_Method,
	Twig_Filter_Method;

/**
 * Provides Twig support for commonly used FuelPHP classes and methods.
 */
class Cache extends Twig_Extension
{
	/**
	 * Gets the name of the extension.
	 *
	 * @return  string
	 */
	public function getName()
	{
		return 'cache';
	}
	
	/**
     * Returns the token parser instance to add to the existing list.
     *
     * @return array An array of Twig_TokenParser instances
     */
    public function getTokenParsers()
    {
        return array(
            new NoCacheTokenParser(),
        );
    }
	
}
