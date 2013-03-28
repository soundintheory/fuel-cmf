<?php

namespace CMF\Field\Collection;

use CMF\Admin\ModelForm;

class GalleryInline extends Multiselect {
    
    protected static $defaults = array(
        'filefield' => null
    );
    
    /** inheritdoc */
    public static function type($settings = array())
    {
        return 'gallery-inline';
    }
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array('/admin/assets/js/fields/collection/gallery-inline.js')
        );
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
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
        $filefield = $settings['filefield'];
        
        // Detect a filefield if one hasn't been specified
        if ($filefield === null) {
            foreach ($target_metadata->fieldMappings as $field_name => $field_mapping) {
                if (in_array($field_mapping['type'], array('image', 'imageobject', 'file', 'video'))) {
                    $filefield = $field_name;
                    break;
                }
            }
        }
        
        $settings['filefield'] = $filefield;
        
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
            $prefix = '%TEMP%'.$settings['mapping']['fieldName'].'[%num%]';
            $form_templates[$type] = new ModelForm($metadata, new $type(), $prefix, $hidden_fields, $exclude, true);
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
            
            // Get the class of this item
            $type = \CMF::getClass($model);
            
            if (!isset($form_templates[$type])) continue;
            $form_template = $form_templates[$type];
            
            $prefix = $settings['mapping']['fieldName'].'['.$num.']';
            $row = $form_template->getFields($model, $prefix);
            $row['_icon_'] = $type::icon();
            $row['_title_'] = $model->display();
            $row['_model_'] = $model;
            
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
            'content' => strval(\View::forge('admin/fields/collection/gallery-inline.twig', array( 'settings' => $settings, 'add_types' => $add_types, 'icon' => $target_class::icon(), 'singular' => $target_class::singular(), 'plural' => $target_class::plural(), 'rows' => $rows, 'templates' => $templates_content, 'forms' => $form_templates, 'sortable' => $sortable ), false)),
            'widget' => false,
            'widget_class' => '',
            'widget_icon' => $target_class::icon(),
            'js_data' => $js_data,
            'merge_data' => true
        );
    }
	
}

?>