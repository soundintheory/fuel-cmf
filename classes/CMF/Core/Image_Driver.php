<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.6
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace CMF\Core;

abstract class Image_Driver extends \Fuel\Core\Image_Driver
{
	/**
	 * Converts percentages, negatives, and other values to absolute integers.
	 *
	 * @param   string   $input
	 * @param   boolean  $x  Determines if the number relates to the x-axis or y-axis.
	 * @return  integer  The converted number, usable with the image being edited.
	 */
	protected function convert_number($input, $x = null)
	{
		// Sanitize double negatives
		$input = str_replace('--', '', $input);

		$orig = $input;
		$sizes = $this->sizes();
		$size = $x ? $sizes->width : $sizes->height;
		// Convert percentages to absolutes
		if (substr($input, -1) == '%')
		{
			$input = floor((substr($input, 0, -1) / 100) * $size);
		}
		
		return $input;
	}
}

