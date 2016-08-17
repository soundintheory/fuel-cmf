<?php

/**
 *
 * /purchases?created_on
 * 
 */

return array(

	// A welcome message, possibly in future an API playground
	'_root_' => 'resource/index',
	'_lang-canonicals_' => 'resource/languageCanonicals',

	// Resources
	'(:segment)' => 'resource/render/$1',
	'(:segment)/(:num)' => 'resource/render/$1/$2',
	'(:segment)/(:num)/(:segment)' => 'resource/render/$1/$2/$3',
	'(:segment)/(:segment)' => 'resource/render/$1//$2',
	'(:segment)/(:segment)/(:segment)' => 'resource/render/$1/$2/$3',
);