<?php

namespace CMF\Field;

use Twig_Autoloader,
    Twig_Environment,
    Twig_Loader_String;

class Textarea extends Base
{
    protected static $defaults = array(
        'input_attributes' => array(
            'class' => 'input-xxlarge',
            'rows' => 4
        )
    );
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge' );
        $attributes = array( 'class' => 'controls control-group'.($has_errors ? ' error' : '') );
        $input = \Form::textarea($settings['mapping']['fieldName'], strval($value), $input_attributes);
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));

        if (isset($settings['prepend'])) {
            $input = html_tag('div', array( 'class' => 'input-prepend' ), html_tag('span', array( 'class' => 'add-on' ), $settings['prepend']).$input);
        }
        if (isset($settings['append'])) {
            $input = html_tag('div', array( 'class' => 'input-append' ), $input.html_tag('span', array( 'class' => 'add-on' ), $settings['append']));
        }
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        // Add the 'keep updated' control if the field has a template
        if (isset($settings['template']) && !empty($settings['template'])) {
            
            $attributes['class'] .= ' field-with-controls field-with-template';
            $auto_update_setting = 'settings['.$settings['mapping']['fieldName'].'][auto_update]';
            $auto_update_content = \Form::hidden($auto_update_setting, '0', array()).html_tag('label', array( 'class' => 'checkbox auto-update-label' ), \Form::checkbox($auto_update_setting, '1', \Arr::get($settings, 'auto_update', true), array( 'class' => 'auto-update' )).' auto update');
            $auto_update = html_tag('div', array( 'class' => 'controls-top' ), $auto_update_content);
            $label .= $auto_update;
            
            return array(
                'content' => html_tag('div', $attributes, $label.$input).'<div class="clear"><!-- --></div>',
                'widget' => false,
                'assets' => array( 'js' => array('/admin/assets/js/twig.min.js', '/admin/assets/js/fields/template.js') ),
                'js_data' => $settings
            );
            
        }

        return html_tag('div', $attributes, $label.$input);
    }
    
}