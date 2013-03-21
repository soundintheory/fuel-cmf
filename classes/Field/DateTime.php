<?php

namespace CMF\Field;

class DateTime extends Date {
    
    public static function process($value, $post_data, $entity)
    {
        if (!($date instanceof \DateTime)) $date = \DateTime::createFromFormat('d/m/Y H:i:s', $value);
        if ($date === false) $date = new \DateTime();
        return $date;
    }
    
    /** @inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return $value->format('d/m/Y H:i:s');
    }
	
}