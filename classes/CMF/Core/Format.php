<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace CMF\Core;

/**
 * Format class
 *
 * Help convert between various formats such as XML, JSON, CSV, etc.
 *
 * @package    Fuel
 * @category   Core
 * @author     Fuel Development Team
 * @copyright  2010 - 2012 Fuel Development Team
 * @link       http://docs.fuelphp.com/classes/format.html
 */
class Format extends \Fuel\Core\Format
{
	/**
	 * Import an excel spreadsheet, where string is actually the filename
	 *
	 * @param   string  $string
	 * @return  array
	 */
	protected function _from_xls($path)
	{
		$phpExcel = \PHPExcel_IOFactory::load($path);
		return $phpExcel->getActiveSheet()->toArray(null,true,true,true);
	}
}
