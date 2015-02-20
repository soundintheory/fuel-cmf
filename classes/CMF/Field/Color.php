<?php

namespace CMF\Field;

class Color extends Base {
    
    protected static $defaults = array(
        'default' => '#000000'
    );

    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                '/admin/assets/js/bootstrap-colorpicker.min.js',
                '/admin/assets/js/fields/color.js'
            ),
            'css' => array(
                '/admin/assets/css/bootstrap-colorpicker.min.css'
            )
        );
    }

    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $class = get_called_class();
        $settings = static::settings($settings);
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge form-control' );
        if (!isset($input_attributes['id'])) $input_attributes['id'] = 'form_'.$settings['mapping']['fieldName'];
        $attributes = array( 'class' => 'controls control-group'.($has_errors ? ' error' : '').' field-type-'.$class::type($settings) );
        $label_text = $settings['title'].($required ? ' *' : '');
        
        // Build the input
        $input = '<input type="text" name="'.$settings['mapping']['fieldName'].'" '.array_to_attr($input_attributes).' value="'.\Security::htmlentities(strval($value), ENT_QUOTES).'" />';
        
        // Build the label
        $label = (!$include_label) ? '' : \Form::label($label_text.($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        
        // Wrap it in an input group
        $input = html_tag('div', array( 'class' => 'input-append' ), $input.html_tag('span', array( 'class' => 'add-on' ), ' '));
        
        // Don't wrap the input if wrap is set to false
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', $attributes, $label.$input);
    }
    
}