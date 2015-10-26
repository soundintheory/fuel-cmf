<?php

/**
 *
 * /purchases?created_on
 * 
 */

return array(

	// A welcome message, possibly in future an API playground
	'_root_' => 'resource/index',

	// Resources
	'(:segment)' => 'resource/render/$1',
	'(:segment)/(:num)' => 'resource/render/$1/$2',
	'(:segment)/(:segment)' => 'resource/render/$1//$2',
	'(:segment)/(:segment)/(:segment)' => 'resource/render/$1/$2/$3',
);