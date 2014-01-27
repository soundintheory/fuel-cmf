<?php

return array(
	
	'doctrine2' => array(
		'cache_driver' => 'array',
		'auto_generate_proxy_classes' => true,
		'proxy_dir' => APPPATH.'classes/proxy',
		'proxy_namespace' => 'Proxy',
		'metadata_path' => array(
			APPPATH . 'classes/model',
			PKGPATH . 'cmf/classes/CMF/Model',
			PKGPATH . 'cmf/modules/admin/classes/model'
		),
		
		/**
		 * These are used to filter entities for mapping. Doctrine will use 
		 * strpos($entity_class, $namespace) === 0 to do this
		 */
		'entity_namespaces' => array('Model', 'CMF', 'Admin'),
		
		/**
		 * Extensions to enable. Use fully qualified class names that extend
		 * the 'Doctrine\Fuel\Extension' class
		 */
		'extensions' => array(
			'CMF\\Doctrine\\Extensions\\Tree',
			'CMF\\Doctrine\\Extensions\\Timestampable',
			'CMF\\Doctrine\\Extensions\\Spatial',
			'CMF\\Doctrine\\Extensions\\URL',
			'CMF\\Doctrine\\Extensions\\Sortable',
			//'CMF\\Doctrine\\Extensions\\Translatable',
			//'CMF\\Doctrine\\Extensions\\Loggable',
			//'CMF\\Doctrine\\Extensions\\SoftDeletable'
		),
		
		/**
		 * You can map additional types here. Use fully qualified class names
		 * that extend the 'Doctrine\DBAL\Types\Type' class
		 */
		'types' => array(
			'richtext' => array( 'class' => 'Doctrine\\DBAL\\Types\\TextType' ),
			'color' => array( 'class' => 'Doctrine\\DBAL\\Types\\StringType' ),
			'enum' => array( 'class' => 'Doctrine\\DBAL\\Types\\StringType' ),
			'enum_integer' => array( 'class' => 'Doctrine\\DBAL\\Types\\IntegerType' ),
			'enum_float' => array( 'class' => 'Doctrine\\DBAL\\Types\\FloatType' ),
			'enum_decimal' => array( 'class' => 'Doctrine\\DBAL\\Types\\DecimalType' ),
			'videoembed' => array( 'class' => 'Doctrine\\DBAL\\Types\\ObjectType' ),
			'video' => array( 'class' => 'Doctrine\\DBAL\\Types\\ObjectType' ),
			'file' => array( 'class' => 'Doctrine\\DBAL\\Types\\ObjectType' ),
			'image' => array( 'class' => 'Doctrine\\DBAL\\Types\\ObjectType' ),
			'link' => array( 'class' => 'Doctrine\\DBAL\\Types\\ObjectType' ),
			'measurement' => array( 'class' => 'Doctrine\\DBAL\\Types\\FloatType' ),
			'language' => array( 'class' => 'Doctrine\\DBAL\\Types\\StringType' ),
			'config' => array( 'class' => 'Doctrine\\DBAL\\Types\\StringType' ),
			'array_config' => array( 'class' => 'Doctrine\\DBAL\\Types\\ArrayType' ),
		),
		
		'ignore_tables' => array(
			'migration',
			'wp_*'
		)
	)
	
);