<?php

namespace CMF\Field;

class Readonly extends Base {
	
	/** inheritdoc */
	public static function displayForm($value, &$settings, $model)
    {
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $attributes = array( 'class' => 'controls control-group'.($has_errors ? ' error' : '') );
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName']);
        $input = ($value instanceof \CMF\Model\Base) ? strval($value->display()) : strval($value);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', $attributes, $label.$input);
    }
	
}