<?php

namespace CMF\Field;

class Date extends Base {
    
    public static function process($value, $settings, $model)
    {
        $date = \DateTime::createFromFormat('d/m/Y', $value);
        if ($date === false) $date = new \DateTime();
        return $date;
    }
    
    /** @inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return isset($value) ? $value->format('d M Y') : '(empty)';
    }
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
    	if (!isset($value) || !$value) $value = new \DateTime();
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-large' );
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        $input = \Form::input($settings['mapping']['fieldName'], $value->format('d/m/Y'), $input_attributes);
        $input = $input = html_tag('div', array( 'class' => 'input-prepend' ), html_tag('span', array( 'class' => 'add-on' ), '<i class="icon-calendar"></i>').$input);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', array( 'class' => 'controls control-group field-type-date'.($has_errors ? ' error' : '') ), $label.$input);
    }
	
}