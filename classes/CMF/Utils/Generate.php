<?php

namespace CMF\Utils;

/**
 * A static class through which all installation functionality can be accessed
 *
 * @package CMF
 */
class Generate
{
	/**
	 * A wrapper for PWGen
	 */
	public static function generatePassword($random = true)
	{
	    $pwgen = new \PWGen();
	    return $pwgen->generate();
	}
}