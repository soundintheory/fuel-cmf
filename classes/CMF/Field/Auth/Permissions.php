<?php

namespace CMF\Field\Auth;

class Permissions extends \CMF\Field\Collection\Multiselect {
    
    public static $always_process = true;
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array('/admin/assets/js/fields/auth/permissions.js'),
            'css' => array()
        );
    }
    
    /** inheritdoc */
    public static function preProcess($value, $settings, $model)
    {
        $values = array();
        
        foreach ($value as $class => $actions) {
            
            if (array_key_exists('all', $actions) && intval($actions['all']) === 1) {
                $values[] = \CMF\Auth::get_permission('all', $class);
                continue;
            }
            
            foreach ($actions as $action => $enabled) {
                if (intval($enabled) === 1) {
                    $values[] = \CMF\Auth::get_permission($action, $class);
                }
            }
            
        }
        
        return $values;
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        if (!\CMF\Auth::can(array('view', 'edit'), 'CMF\\Model\\Permission')) return '';
        
        // Kick the permissions and get the active classes
        \CMF\Auth::create_permissions();
        $all_actions = \CMF\Auth::all_actions();
        $activeClasses = \CMF\Admin::activeClasses();
        
        // Set up the values
        $values = array();
        if (isset($value) && ($value instanceof \Doctrine\Common\Collections\Collection || is_array($value))) {
            
            foreach ($value as $val) {
                $resource = $val->resource;
                $action = $val->action;
                $actions = isset($values[$resource]) ? $values[$resource] : array();
                if (!in_array($action, $actions)) $actions[] = $action;
                $values[$resource] = $actions;
            }
            
        }
        
        // Get the resources defined in the config
        $extra_resources = \CMF\Auth::extra_resources();
        $resources = array();
        $resource_group = array( 'title' => 'Resources', 'classes' => array() );
        $classes_index = 0;
        
        // Set the values of the resources
        foreach ($extra_resources as $resource_id => $extra_resource) {
            
            $extra_resource['values'] = (isset($values[$resource_id]) ? $values[$resource_id] : array());
            $resource_group['classes'][$resource_id] = $extra_resource;
            
        }
        
        // If there are resources, add them at the top and update the classes index
        if (count($resource_group['classes']) > 0) {
            
            $resources[] = $resource_group;
            $classes_index = 1;
            
        }
        
    	$resources[] = array( 'title' => 'Content types', 'classes' => array() );
    	
    	// Build the resources list...
    	foreach ($activeClasses as $class_name => $classes) {
    		
    		if (count($classes) > 1) {
    			
    			$class_group = array();
    			foreach ($classes as $group_class) {
    				$resource_title = $group_class::_static() ? $group_class::singular() : $group_class::plural();
    				$resource_icon = $group_class::icon();
    				$class_group[$group_class] = array(
                        'title' => $resource_title,
                        'icon' => $resource_icon,
                        'actions' => $group_class::_static() ? array('view', 'edit') : $all_actions,
                        'values' => (isset($values[$group_class]) ? $values[$group_class] : array())
                    );
    			}
    			
    			uasort($class_group, function($a, $b) {
    				return strcmp(strtolower($a['title']), strtolower($b['title']));
    			});
    			
    			$resources[] = array('title' => $class_name::plural(), 'classes' => $class_group );
    			
    		} else {
    			
    			$resource_title = $class_name::_static() ? $class_name::singular() : $class_name::plural();
    			$resource_icon = $class_name::icon();
    			$resources[$classes_index]['classes'][$class_name] = array(
                    'title' => $resource_title,
                    'icon' => $resource_icon,
                    'actions' => $class_name::_static() ? array('view', 'edit') : $all_actions,
                    'values' => (isset($values[$class_name]) ? $values[$class_name] : array())
                );
    			
    		}
    	}
    	
    	uasort($resources[$classes_index]['classes'], function($a, $b) {
			return strcmp(strtolower($a['title']), strtolower($b['title']));
		});
        
    	$content = strval(\View::forge('admin/fields/auth/permissions.twig', array(
    		'settings' => $settings,
    		'resources' => $resources,
    		'actions' => $all_actions
    	), false));
    	
    	return array(
    		'content' => $content,
    		'widget' => true,
    		'widget_title' => $settings['title'],
    		'assets' => array()
    	);
    }
    
    public static function type($settings = array())
    {
        return 'auth-permissions';
    }
	
}