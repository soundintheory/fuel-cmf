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
			'CMF\\Twig\\TextExtension',
			'Twig\\Extension\\StringLoaderExtension',
			'CMF\\Twig\\Extension',
			'CMF\\Twig\\Cache',
		),
	),

);