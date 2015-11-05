<?php

namespace CMF\Field;

class RandomKey extends Base {
    
    public static $always_process = false;
    
    protected static $defaults = array(
        'auto_update' => '1'
    );

    /**
     * Renders the field's form element for editing in the admin site
     */
    public static function displayForm($value, &$settings, $model)
    {
        $class = get_called_class();
        $settings = static::settings($settings);
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge' );
        if (!isset($input_attributes['id'])) $input_attributes['id'] = 'form_'.$settings['mapping']['fieldName'];
        $attributes = array( 'class' => 'controls control-group'.($has_errors ? ' error' : '').' field-type-'.$class::type($settings) );
        $label_text = $settings['title'].($required ? ' *' : '');
        
        if (empty($value)) {
            $value = substr(\Security::generate_token(), 0, 16);
        }

        // Description?
        $description = isset($settings['description']) ? '<span class="help-block">'.$settings['description'].'</span>' : '';
        
        // Build the input
        $input = '<input type="text" name="'.$settings['mapping']['fieldName'].'" '.array_to_attr($input_attributes).' value="'.\Security::htmlentities(strval($value), ENT_QUOTES).'" />';
        
        // Build the label
        $label = (!$include_label) ? '' : html_tag('label', array( 'class' => 'item-label', 'for' => $settings['mapping']['fieldName'] ), $label_text.($has_errors ? ' - '.$errors[0] : ''));
        
        // Don't wrap the input if wrap is set to false
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', $attributes, $label.$description.$input);
    }
	
}