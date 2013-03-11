<?php

/**
 * CMF's config for Doctrine - This means you probably won't need any config in the app, unless you need
 * to add extra metadata paths or add custom extensions and types
 */

return array(
	
    'cache_driver' => 'file',
	'auto_generate_proxy_classes' => true,
	'proxy_dir' => APPPATH . 'classes' . DS . 'model' . DS . 'proxy',
	'proxy_namespace' => 'Proxy',
	
	'metadata_path' => array(
	    APPPATH . 'classes/model',
		PKGPATH . 'cmf/classes/Model',
		PKGPATH . 'cmf/modules/admin/classes/model'
	),
	
	'entity_namespaces' => array(
		'Model',
		'CMF',
		'Admin'
	),
	
	'extensions' => array(
		'Doctrine\\Fuel\\Extensions\\Tree',
		'Doctrine\\Fuel\\Extensions\\Timestampable',
		'Doctrine\\Fuel\\Extensions\\Spatial',
		'CMF\\Doctrine\\Extensions\\URL',
		'CMF\\Doctrine\\Extensions\\Sortable'
		//'Doctrine\\Fuel\\Extensions\\Sortable',
		//'Doctrine\\Fuel\\Extensions\\Translatable',
		//'Doctrine\\Fuel\\Extensions\\Loggable',
		//'Doctrine\\Fuel\\Extensions\\SoftDeletable'
	),
	
	'types' => array(
		'point' => array( 'class' => 'Doctrine\\Fuel\\Types\\Point', 'dbtype' => 'point' ),
		'richtext' => array( 'class' => 'Doctrine\\DBAL\\Types\\TextType' ),
		'file' => array( 'class' => 'Doctrine\\DBAL\\Types\\TextType' ),
		'image' => array( 'class' => 'Doctrine\\DBAL\\Types\\TextType' ),
		'fileobject' => array( 'class' => 'Doctrine\\DBAL\\Types\\ObjectType' ),
		'imageobject' => array( 'class' => 'Doctrine\\DBAL\\Types\\ObjectType' ),
		'link' => array( 'class' => 'Doctrine\\DBAL\\Types\\ObjectType' )
	),
	
	'ignore_tables' => array(
		'migration',
	)
	
);