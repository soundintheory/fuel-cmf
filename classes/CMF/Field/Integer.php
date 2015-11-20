<?php

namespace CMF\Field;

class Integer extends Base {
    
    protected static $defaults = array(
        // None
    );
    
    /* inheritdoc */
    public static function process($value, $settings, $model)
    {
        $output = @intval($value);
        return ($output === false) ? $value : $output;
    }
    
    /* inheritdoc */
    public static function validate($value, $settings, $model)
    {
        if (!is_null($value) && !is_int($value)) {
            $model->addErrorForField($settings['mapping']['fieldName'], __('admin.errors.not_a_number'));
        }
    }
	
}