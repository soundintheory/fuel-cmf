<?php


namespace CMF\Field\Object;

class ArraySelect extends \CMF\Field\Select {
    
    protected static $defaults = array(
        'options' => array(),
        'allow_empty' => true,
        'use_key' => false,
        'output' => 'value',
        'multiple' => true,
        'select2' => array(),
        'default' => null
    );
    
    public static function process($value, $settings, $model)
    {
        $settings = static::settings($settings);
        if (!is_array($value)) return array($value);
        return $value;
    }
	
}