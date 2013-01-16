<?php

namespace CMF\Field\Auth;

class Permissions extends \CMF\Field\Collection\Multiselect {
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array('/admin/assets/js/fields/auth/permissions.js'),
            'css' => array()
        );
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
    	// Get all the permissions and the active classes...
    	$actions = \CMF\Auth::$default_actions;
    	$activeClasses = \CMF\Admin::activeClasses();
    	$resources = array(
    		array( 'title' => '', 'classes' => array() )
    	);
    	
    	// Build the resources list...
    	foreach ($activeClasses as $class_name => $classes) {
    		
    		if (count($classes) > 1) {
    			
    			$class_group = array();
    			foreach ($classes as $group_class) {
    				$resource_title = $group_class::_static() ? $group_class::singular() : $group_class::plural();
    				$resource_icon = $group_class::icon();
    				$class_group[$group_class] = array( 'title' => $resource_title, 'icon' => $resource_icon );
    			}
    			
    			uasort($class_group, function($a, $b) {
    				return strcmp(strtolower($a['title']), strtolower($b['title']));
    			});
    			
    			$resources[] = array( 'title' => $class_name::plural(), 'classes' => $class_group );
    			
    		} else {
    			
    			$resource_title = $class_name::_static() ? $class_name::singular() : $class_name::plural();
    			$resource_icon = $class_name::icon();
    			$resources[0]['classes'][$class_name] = array( 'title' => $resource_title, 'icon' => $resource_icon );
    			
    		}
    	}
    	
    	uasort($resources[0]['classes'], function($a, $b) {
			return strcmp(strtolower($a['title']), strtolower($b['title']));
		});
    	
    	$content = strval(\View::forge('admin/fields/auth/permissions.twig', array(
    		'settings' => $settings,
    		'resources' => $resources,
    		'actions' => $actions
    	), false));
    	
    	return array(
    		'content' => $content,
    		'widget' => true,
    		'widget_title' => $settings['title'],
    		'assets' => array()
    	);
    	
        // Set up the values for the select
        $values = array();
        if (isset($value) && $value instanceof \Doctrine\Common\Collections\Collection) {
            foreach ($value as $val) {
                $values[] = strval($val->get('id'));
            }
        }
        
        $target_prop = ($settings['mapping']['isOwningSide'] === true) ? $settings['mapping']['inversedBy'] : $settings['mapping']['mappedBy'];
        if (empty($target_prop) || is_null($model->id)) $target_prop = false;
        
        //print_r($settings['mapping']);
        
        // Set up the values for the template
        $settings = static::settings($settings);
        $target_class = $settings['mapping']['targetEntity'];
        $options = $target_class::options();
        $settings['required'] = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $settings['title'] = $settings['title'].($settings['required'] ? ' *' : '').($has_errors ? ' - '.$errors[0] : '');
        $settings['cid'] = md5($settings['mapping']['fieldName'].static::type());
        $settings['add_link'] = '/admin/'.\CMF\Admin::getTableForClass($target_class).'/create?_mode=inline&_cid='.$settings['cid'].($target_prop !== false ? '&'.$target_prop.'='.$model->id : '');
        $settings['singular'] = $target_class::singular();
        
        return strval(\View::forge('admin/fields/collection/multiselect.twig', array( 'settings' => $settings, 'options' => $options, 'values' => $values ), false));
    }
    
    public static function type($settings = array())
    {
        return 'auth-permissions';
    }
	
}