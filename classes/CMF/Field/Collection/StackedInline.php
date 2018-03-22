<?php

namespace CMF\Field\Collection;

use CMF\Admin\ModelForm;

class StackedInline extends Multiselect {
    
    /** inheritdoc */
    public static function type($settings = array())
    {
        return 'stacked-inline';
    }
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array('/assets/js/fields/collection/stacked-inline.js')
        );
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
    	$target_class = $settings['mapping']['targetEntity'];
    	$target_metadata = $target_class::metadata();
        
        // Get the array of possible types
        $types = array($target_class);
        $exclude_types = array();
        if (count($target_metadata->subClasses) > 0) {
            if ($target_class::superclass() === true) $types = array();
            $types = array_merge($types, $target_metadata->subClasses);
        }
        
    	if (isset($value) && $value instanceof \Doctrine\Common\Collections\Collection) {
    		$value = $value->toArray();
    	} else if (!is_array($value)) {
    		$value = array();
    	}
        
        $target_field = ($settings['mapping']['isOwningSide'] === true) ? $settings['mapping']['inversedBy'] : $settings['mapping']['mappedBy'];
        $sortable = $target_class::sortable() && isset($settings['mapping']['orderBy']) && isset($settings['mapping']['orderBy']['pos']) && $settings['mapping']['orderBy']['pos'] == 'ASC';
        $sort_group = $target_class::sortGroup();
        
        // If the target isn't grouped by this relationship, we need to save all the positions at once...
        $save_all = $sort_group != $target_field;
        
        $exclude = array($target_field);
        $hidden_fields = array();
        if ($sortable) $hidden_fields['pos'] = 0;
        
        // The forms from which we'll render out each row, but also the blank forms for the 'new item' templates
        $form_templates = array();
        $js_data = array();
        $target_tables = array();
        $templates_content = array();
        $assets = array();
        $add_types = array();
        
        foreach ($types as $type) {
            $metadata = $type::metadata();
            $prefix = '__TEMP__'.$settings['mapping']['fieldName'].'[__NUM__]';
            $form_templates[$type] = new ModelForm($metadata, new $type(), $prefix, $hidden_fields, $exclude);
            $target_tables[$type] = $metadata->table['name'];
            $templates_content[$type] = array( 'fields' => $form_templates[$type]->getFieldContent(), 'icon' => $type::icon(), 'singular' => $type::singular(), 'prefix' => $prefix );
            $add_types[] = array( 'type' => $type, 'singular' => $type::singular(), 'plural' => $type::plural(), 'icon' => $type::icon() );
            foreach ($form_templates[$type]->js_field_settings as $key => $js_settings) {
                if (!isset($js_data[$key])) $js_data[$key] = $js_settings;
            }
            $assets = \Arr::merge($assets, $form_templates[$type]->assets);
        }
        
        // Loop through and get each row from the form
        $rows = array();
    	foreach ($value as $num => $model) {
            
            // Get the class of this item, check if it's a proxy
            $type = get_class($model);
            if (strpos($type, 'Proxy') === 0) $type = get_parent_class($model);
            
            if (!isset($form_templates[$type])) continue;
            $form_template = $form_templates[$type];
            
            $prefix = $settings['mapping']['fieldName'].'['.$num.']';
            $row = $form_template->getFields($model, $prefix);
            $row['_icon_'] = $type::icon();
            $row['_title_'] = $model->display();
            
            $row['hidden_fields']['id'] = \Form::hidden($prefix.'[id]', $model->id, array( 'class' => 'item-id' ));
            $row['hidden_fields']['__type__'] = \Form::hidden($prefix.'[__type__]', $type);
    		$rows[] = $row;
            $js_data = array_merge($js_data, $row['js_field_settings']);
            
    	}
        
        $js_data[$settings['mapping']['fieldName']] = array(
            'target_tables' => $target_tables,
            'add_types' => $add_types,
            'save_all' => $save_all,
            'sortable' => $sortable
        );
        
        return array(
            'assets' => $assets,
            'content' => strval(\View::forge('admin/fields/collection/stacked-inline.twig', array( 'settings' => $settings, 'add_types' => $add_types, 'singular' => $target_class::singular(), 'plural' => $target_class::plural(), 'rows' => $rows, 'templates' => $templates_content, 'forms' => $form_templates, 'sortable' => $sortable ), false)),
            'widget' => true,
            'widget_class' => '',
            'widget_icon' => $target_class::icon(),
            'js_data' => $js_data,
            'merge_data' => true
        );
        
    }
	
}

?>