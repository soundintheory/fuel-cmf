<?php

namespace CMF\Field\Auth;

class Password extends \CMF\Field\Base {
    
    /**
     * Renders the field's form element for editing in the admin site
     * @see \Admin::getFieldSettings()
     * @param mixed $value The current value of the property, if there is one
     * @param array $settings Field settings, created through \Admin::getFieldSettings()
     * @param array $errors Any error messages relating to this field
     * @param object $model The model, if it is being edited. Will be null if it's new
     * @return string The form control
     */
    public static function displayForm($value, &$settings, $model)
    {
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge' );
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName']);
        $input = \Form::password($settings['mapping']['fieldName'], strval($value), $input_attributes);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', array( 'class' => 'controls control-group'.($has_errors ? ' error' : '') ), $label.$input);
    }
	
}