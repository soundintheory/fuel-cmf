<?php

namespace CMF\Field\Object;

use CMF\Admin\ObjectForm;

class ArrayField extends Object {
    
    protected static $defaults = array(
        'dynamic' => true,
        'array' => true,
        'tabular' => false,
        'widget' => true,
        'fields' => array()
    );
    
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        try {
            return '<a href="'.$edit_link.'" class="item-link">'.strval($value).'</a>';
        } catch (\Exception $e) {
            return "Error: unkown type";
        }
    }
    
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        if ($settings['array'] !== true) {
            $field_class = 'CMF\\Field\\Object';
            return $field_class::displayForm($value, $settings, $model);
        }
        
        if (is_null($value)) $value = array();
        
        if (\Arr::is_assoc($value)) $value = array($value);
        $forms = array();
        $content = '';
        
        foreach ($value as $i => $object) {
            $form = new ObjectForm($settings, $object);
            $content .= "\n".$form->getContent($model)."\n";
        }
        
        return array(
            'content' => $content,
            'assets' => array(),
            'widget' => $settings['widget']
        );
    }
    
}