<?php

namespace CMF\Doctrine\Extensions;

use \Doctrine\DBAL\Types\Type;

/**
 * Extension to add the MySQL point type and some related functions
 */
class Spatial extends Extension
{
	
	/** @override */
	public static function init($em, $reader)
	{
		$em->getConfiguration()->addCustomNumericFunction('DISTANCE', 'Doctrine\\Fuel\\Spatial\\Distance');
		$em->getConfiguration()->addCustomNumericFunction('POINT_STR', 'Doctrine\\Fuel\\Spatial\\PointStr');
	}
	
}