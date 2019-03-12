<?php


namespace CMF\Field\Object;

class ArrayConfig extends ArraySelect {
    
    public static function process($value, $settings, $model)
    {
        $settings = static::settings($settings);
        if (!is_array($value)) return array($value);

        if (($key = array_search("", $value)) !== false) {
            unset($value[$key]);
        }

        return $value;
    }
	
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings['options'] = \Config::get($settings['mapping']['fieldName'], array());
        if (!is_array($settings['options'])) $settings['options'] = array($settings['options']);
        
        return parent::displayForm($value, $settings, $model);
    }
    
}