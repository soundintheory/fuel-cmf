<?php

/**
 * Fuel CMF
 *
 * Adds CMF's Twig extensions to the parser
 */

return array(
	
	'View_Twig' => array(
		'views_paths' => array(
			PKGPATH.'cmf'.DS.'modules'.DS.'admin'.DS.'views',
		),
		'extensions' => array(
			'Twig_Extensions_Extension_Text',
			'Twig_Extension_StringLoader',
			'CMF\\Twig\\Extension',
			'CMF\\Twig\\Cache',
		),
	),

);