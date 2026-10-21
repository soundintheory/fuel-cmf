<?php

namespace CMF\Twig;

use Twig\Extension\AbstractExtension;

/**
 * Provides Twig support for commonly used FuelPHP classes and methods.
 */
class Cache extends AbstractExtension
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
     * @return array An array of token parser instances
     */
    public function getTokenParsers()
    {
        return array(
            new NoCacheTokenParser(),
        );
    }
	
}
