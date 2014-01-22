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
    
    /** @inheritdoc */
    public static function preProcess($value, $settings, $model)
    {
        return $value;
    }
    
    /** inheritdoc */
    public static function type($settings = array())
    {
        return 'tabular-array';
    }
    
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
            $field_class = 'CMF\\Field\\Object\\Object';
            return $field_class::displayForm($value, $settings, $model);
        }
        
        if (is_null($value)) $value = array();
        
        if (\Arr::is_assoc($value)) $value = array($value);
        $form = new ObjectForm($settings, $value);
        $content = '';
        
        return array(
            'content' => $form->getContent($model),
            'assets' => $form->assets,
            'widget' => $settings['widget']
        );
    }
    
}