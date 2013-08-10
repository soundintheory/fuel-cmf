<?php

namespace CMF;

use DoctrineFuel,
    Doctrine\DBAL\LockMode,
    Gedmo\Mapping\ExtensionMetadataFactory,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Annotations\CachedReader,
    Doctrine\Common\Cache\ArrayCache,
    Doctrine\Common\Annotations\Reader,
    Gedmo\Mapping\MappedEventSubscriber,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    CMF\Forms\ItemForm;

/**
 * Some helper methods to be used for the admin site
 */
class Admin
{
	
	public static $current_class = '';
	
	/**
	 * Maps table names to class names - populated once on first use for efficiency
	 * @see Admin::getClassForTable()
	 * @var array
	 */
    protected static $tables_to_classes = null;
    
    /**
	 * Maps class names to table names - populated once on first use for efficiency
	 * @see Admin::getTableForClass()
	 * @var array
	 */
    protected static $classes_to_tables = null;
    
    /**
	 * Maps active class names to table names - populated once on first use for efficiency
	 * @see Admin::getTableForClass()
	 * @var array
	 */
    protected static $active_classes = null;
    
    /**
     * Stores field settings against class names - a form of cache for efficiency
     * @see Admin::getFieldSettings()
     * @var array
     */
    protected static $field_settings = array();
    
    /**
     * Array mapping association types to a human readable format.
     * @var array
     */
    protected static $association_types = array(
    	ClassMetadataInfo::ONE_TO_ONE => 'onetoone',
    	ClassMetadataInfo::MANY_TO_ONE => 'manytoone',
    	ClassMetadataInfo::ONE_TO_MANY => 'onetomany',
    	ClassMetadataInfo::MANY_TO_MANY => 'manytomany'
    );
	
	/**
	 * Gets the model's fully qualified class name from the table name
	 * @param string $table_name
	 * @return string The model class name
	 */
	public static function getClassForTable($table_name)
	{
		if (static::$tables_to_classes !== null) return isset(static::$tables_to_classes[$table_name]) ? static::$tables_to_classes[$table_name] : false;
		
	    static::initClassTableMap();
	    
	    return isset(static::$tables_to_classes[$table_name]) ? static::$tables_to_classes[$table_name] : false;
	}
	
	/**
	 * Gets the the corresponding table name from a model's fully qualified class name
	 * @param string $table_name
	 * @return string The table name
	 */
	public static function getTableForClass($class_name)
	{
		if (static::$classes_to_tables !== null) return isset(static::$classes_to_tables[$class_name]) ? static::$classes_to_tables[$class_name] : false;
		
		static::initClassTableMap();
		
		return isset(static::$classes_to_tables[$class_name]) ? static::$classes_to_tables[$class_name] : false;
	}
	
	/**
	 * Populates the 'tables > classes' and 'classes > tables' maps.
	 * @return void
	 */
	protected static function initClassTableMap()
	{
		$em = \D::manager();
		$driver = $em->getConfiguration()->getMetadataDriverImpl();
		static::$tables_to_classes = array();
		static::$classes_to_tables = array();
		static::$active_classes = array();
		
		// Loop through all Doctrine's class names, get metadata for each and populate the maps
		foreach ($driver->getAllClassNames() as $class_name)
		{
		    $metadata = $em->getClassMetadata($class_name);
		    
		    if ($metadata->inheritanceType === ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE) {
		    	static::$tables_to_classes[$metadata->table['name']] = $metadata->rootEntityName;
		    } else {
		    	static::$tables_to_classes[$metadata->table['name']] = $class_name;
		    }
		    
		    static::$classes_to_tables[$class_name] = $metadata->table['name'];
		    
		    if (!$metadata->isMappedSuperclass && is_subclass_of($class_name, 'CMF\\Model\\Base') && $class_name::hasPermissions()) {
		    	if (count($metadata->parentClasses) > 0) {
		    		$parent_class = $metadata->parentClasses[0];
		    		if (!isset(static::$active_classes[$parent_class])) {
		    			static::$active_classes[$parent_class] = array($parent_class, $class_name);
		    		} else {
		    			static::$active_classes[$parent_class][] = $class_name;
		    		}
		    		
		    	} else if (!isset(static::$active_classes[$class_name])) {
		    		static::$active_classes[$class_name] = array($class_name);
		    	}
		    }
		    
		}
	}
	
	/**
	 * Gets the classes to tables array map
	 * @param  boolean $active Whether or not to filter to only active classes (leaves out mapped superclasses)
	 * @return array
	 */
	public static function activeClasses($active = false)
	{
		if (static::$active_classes !== null) return static::$active_classes;
	    static::initClassTableMap();
	    return static::$active_classes;
	}
	
	/**
	 * Gets the field settings for a model. Adds in default settings if there aren't any specified.
	 * @param string $class_name
	 * @return array Associative array of field settings
	 */
	public static function getFieldSettings($class_name)
	{
		if (isset(static::$field_settings[$class_name])) return static::$field_settings[$class_name];
		
		$fields = $class_name::fields();
		$fields_types = \Config::get('cmf.fields_types');
		$metadata = $class_name::metadata();
		
		$field_mappings = $metadata->fieldMappings;
		$association_mappings = $metadata->associationMappings;
		$field_list = array_keys(\Arr::merge($fields, $metadata->reflFields));
		
		foreach ($field_list as $key) {
			
			$field = isset($fields[$key]) ? $fields[$key] : array();
			
			if ($field === true) {
				$field = array();
			} else if ($field === false) {
				$field = array( 'visible' => false );
			}
			
			if (isset($field_mappings[$key])) {
				
				// It's a normal field
				$mapping = $field_mappings[$key];
				
			} else if (isset($association_mappings[$key])) {
				
				// It's an association.
				$mapping = $association_mappings[$key];
				$association_type = static::$association_types[$mapping['type']];
				
				// If there's a custom type defined based on the table name, use it
				$special_mapping_type = $association_type.'_'.static::getTableForClass($mapping['targetEntity']);
				
				// If that custom type doesn't exist, try and use the '_inline' version for associations with orphanRemoval = true
				// Also an inline version can be invoked by having an 'inline' setting on the field. If this value is a string then it
				// will also get appended to the inline mapping type. Eg. 'onetomany_inline_stacked' if 'inline' => 'stacked'
				if (!isset($fields_types[$special_mapping_type]) && ($mapping['orphanRemoval'] === true || isset($field['inline']))) {
					$special_mapping_type = $association_type.'_inline'.((isset($field['inline']) && is_string($field['inline'])) ? '_'.$field['inline'] : '');
				}
				
				$mapping['type'] = isset($fields_types[$special_mapping_type]) ? $special_mapping_type : $association_type;
				
			} else if (property_exists($class_name, $key)) {
				
				// It's just a property of the class, no mapping info
				if (!isset($field['field'])) $field['field'] = 'CMF\\Field\\Base';
				if (!isset($field['title'])) $field['title'] = \Inflector::humanize($key);
				$field['mapping'] = array( 'fieldName' => $key );
				$fields[$key] = $field;
				continue;
				
			} else {
				
				// This field can't be used. Remove it from the output
				if (isset($fields[$key])) unset($fields[$key]);
				continue;
				
			}
			
			$field['mapping'] = $mapping;
			
			// Add in the field class from the field types map if it's not there
			if (!isset($field['field'])) $field['field'] = isset($fields_types[$mapping['type']]) ? $fields_types[$mapping['type']] : 'CMF\\Field\\None';
			
			// Set up the title of the field it if it's not there
			if (!isset($field['title'])) $field['title'] = \Inflector::humanize($mapping['fieldName']);
			
			$fields[$key] = $field;
			
		}
		
		return static::$field_settings[$class_name] = $fields;
	}
	
	/**
	 * Processes the config and generates data for the template to render the sidebar
	 * @return array The sidebar config
	 */
	public static function getSidebarConfig()
	{
		$sidebar_config = \Config::get('cmf.admin.sidebar');
		$current_group = 0;
		$output = array( array( 'heading' => false, 'items' => array() ) );
		
		// Check if the first item is a heading
		if (isset($sidebar_config[0]['heading'])) {
			$item = array_shift($sidebar_config);
			$output[0]['heading'] = $item['heading'];
		}
		
		foreach ($sidebar_config as $item) {
			
			if (isset($item['heading'])) {
				
				$current_group++;
				$output[$current_group] = array( 'heading' => $item['heading'], 'items' => array() );
				
			} else if (isset($item['model'])) {
				
				$class_name = $item['model'];
				if (!\CMF\Auth::can('view', $class_name)) continue;
				
				$metadata = $class_name::metadata();
				$output[$current_group]['items'][] = array(
					'icon' => $class_name::icon(),
					'title' => $class_name::plural(),
					'href' => '/admin/'.$metadata->table['name'],
					'class' => $class_name,
					'active' => $class_name === static::$current_class
				);
				
			} else if (isset($item['link'])) {
				
				if (!isset($item['title'])) {
					$parts = explode('/', $item['link']);
					$item['title'] = \Inflector::humanize(str_replace('-', ' ', array_pop($parts)));
				}
				
				$uri = \Input::uri();
				
				$output[$current_group]['items'][] = array(
					'icon' => isset($item['icon']) ? $item['icon'] : 'dashboard',
					'title' => $item['title'],
					'href' => $item['link'],
					'active' => strpos($uri, $item['link']) === 0
				);
				
			}
			
		}
		
		return $output;
		
	}
	
	/**
	 * Creates any instances of static models for the class hierarchy defined in the metadata
	 * @param object $metadata
	 * @return void
	 */
	public static function createStaticInstances($metadata)
	{
		$classes = $metadata->subClasses;
		array_unshift($classes, $metadata->name);
		foreach ($classes as $class) {
			
			if (is_subclass_of($class, 'CMF\\Model\\Base')) {
				if ($class::_static() === true) {
					$class::instance();
				}
			}
			
		}
		
	}
}