<?php

namespace CMF\Field;

class Checkbox extends Base {
    
    /** inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return ($value === true || $value === 1) ? 'yes' : 'no';
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $include_label = isset($settings['label']) ? $settings['label'] : true;
    	$errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
    	$input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array();
    	$off_input = \Form::hidden($settings['mapping']['fieldName'], '0');
    	$input = \Form::checkbox($settings['mapping']['fieldName'], '1', $value, $input_attributes);
    	$title = $settings['title'].($has_errors ? ' - '.$errors[0] : '');
    	$label = (!$include_label) ? $input : html_tag('label', array( 'class' => 'checkbox' ), $input.'<span class="item-label"> '.$title.'</span>');

        $description = isset($settings['description']) ? "<span class=\"help-block\">".$settings['description']."</span>" : "";

        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label;
    	
        return html_tag('div', array( 'class' => 'field-type-checkbox controls control-group'.($has_errors ? ' error' : '') ), $off_input.$label.$description);
    }
	
}