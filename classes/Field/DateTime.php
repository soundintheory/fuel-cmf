<?php

namespace CMF\Field;

class DateTime extends Date {
    
    public static function process($value, $settings, $model)
    {
        if (!($value instanceof \DateTime)) $value = \DateTime::createFromFormat('d/m/Y H:i:s', $value);
        if ($value === false) $value = new \DateTime();
        return $value;
    }
    
    /** @inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return $value->format('d/m/Y H:i:s');
    }
	
}