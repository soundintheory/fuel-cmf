<?php

namespace CMF\Field;

class ConfigSelect extends Select {
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $name = \Arr::get($settings, 'config', $settings['mapping']['fieldName']);
        $settings['options'] = \Config::get($name, array());
        if (!is_array($settings['options'])) $settings['options'] = array($settings['options']);
        
        return parent::displayForm($value, $settings, $model);
    }
	
}