<?php

namespace CMF\Field;

class Redactor extends Textarea {
    
    public function get_type()
    {
        return 'redactor';
    }
    
	/** inheritdoc */
    public static function getAssets()
    {
		return array(
			'js' => array(
				'/admin/assets/redactor/redactor.min.js',
				'/admin/assets/js/fields/redactor.js'
			),
			'css' => array(
				'/admin/assets/redactor/redactor.css'
			)
		);
    }
    
    /** inheritdoc */
	public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;

        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge' );
        //add redactor to the class for the field
        $input_attributes['class'] = $input_attributes['class'] . " redactor";
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        $input = \Form::textarea($settings['mapping']['fieldName'], strval($value), $input_attributes);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        if (isset($settings['widget']) && $settings['widget'] === true) {
            
            return array(
                'assets' => array(),
                'content' => $input,
                'widget' => true,
                'widget_title' => $settings['title'],
                'widget_icon' => 'align-left',
                'js_data' => $settings
            );
            
        }
        
        return array(
            'content' => html_tag('div', array( 'class' => 'controls control-group '.($has_errors ? ' error' : '') ), $label.$input),
            'widget' => false,
            'js_data' => $settings
        );
    }
	
}