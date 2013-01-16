<?php

namespace CMF\Field\Relation;

use CMF\Admin\ModelForm;

class OneToOne extends \CMF\Field\Base {
    
    /** inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return (is_null($value)) ? '(empty)' : $value->display();
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {	
    	$target_class = $settings['mapping']['targetEntity'];
    	$target_metadata = $target_class::metadata();
    	if (is_null($value) || !$value instanceof $target_class) $value = new $target_class();
    	
        $form = new ModelForm($target_metadata, $value, $settings['mapping']['fieldName']);
        
        return array(
            'assets' => $form->assets,
            'content' => $form->getContentFlat(),
            'widget' => isset($settings['widget']) ? $settings['widget'] : true,
            'widget_icon' => $target_class::icon()
        );
        
    }
	
}