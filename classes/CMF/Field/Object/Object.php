<?php

namespace CMF\Field\Object;

use CMF\Admin\ObjectForm,
    CMF\Field\Data\ObjectData;

class Object extends \CMF\Field\Base {
    
    protected static $defaults = array(
        'dynamic' => true,
        'array' => false,
        'tabular' => false,
        'widget' => true,
        'sub_group' => true,
        'fields' => array()
    );
    
    /** @inheritdoc */
    public static function preProcess($value, $settings, $model)
    {
        $settings = static::settings($settings);
        $current_value = $model->get($settings['mapping']['fieldName']);
        
        // Combine the keys and values if they're in a dynamic format
        if ($settings['dynamic'] === true && isset($value['keys']) && isset($value['values'])) {
            $value = array_combine($value['keys'], $value['values']);
        }
        
        // Don't overwrite explicitly defined fields if they're not set
        if (isset($current_value) && is_array($current_value)) {
            
            foreach ($settings['fields'] as $field_name => $field_value) {
                $delete_field = isset($field_value['delete']) && $field_value['delete'] === true;
                if (array_key_exists($field_name, $current_value) && !array_key_exists($field_name, $value) && !$delete_field) {
                    $value[$field_name] = $current_value[$field_name];
                }
                //if (isset($value[$field_name]) && $delete_field) unset($value[$field_name]);
                //if (isset($current_value[$field_name]) && $delete_field) unset($current_value[$field_name]);
            }
            
        }
        
        // Process the fields inside this object
        $form = new ObjectForm($settings, $value);
        $form->processFields($model);
        
        return $form->values;
    }
    
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        try {
            return '<a href="'.$edit_link.'" class="item-link">'.strval($value).'</a>';
        } catch (\Exception $e) {
            return "Error: unknown type";
        }
    }
    
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        if ($settings['array'] === true) {
            return ArrayField::displayForm($value, $settings, $model);
        }
        
        $form = new ObjectForm($settings, $value);
        $content = $form->getContent($model);
        
        return array(
            'content' => $content,
            'assets' => $form->assets,
            'merge_data' => true,
            'js_data' => $form->js_field_settings,
            'widget' => $settings['widget']
        );
    }
    
    public static function type($settings = array())
    {
        $dynamic = isset($settings['dynamic']) && $settings['dynamic'] === true;
        $array = isset($settings['array']) && $settings['array'] === true;
        return 'object'.($dynamic ? '-dynamic' : '').($array ? '-array' : '');
    }
	
}