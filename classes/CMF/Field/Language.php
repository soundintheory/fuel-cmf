<?php

namespace CMF\Field;

class Language extends Base {
    
    protected static $defaults = array(
        'allow_empty' => true
    );
    
    /** @inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return '<img src="/admin/assets/img/lang/'.$value.'.png" style="width:24px;height:24px;" />&nbsp; '.__("languages.$value");
    }
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge' );
        
        $options = \Arr::get(\Lang::$lines, 'en.languages', array());
        
        // Whether to allow an empty option
        if (isset($settings['mapping']['nullable']) && $settings['mapping']['nullable'] && !$required && $settings['allow_empty']) {
            $options = array( '' => '' ) + $options;
        }
        
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        $input = \Form::select($settings['mapping']['fieldName'], $value, $options, $input_attributes);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', array( 'class' => 'controls control-group'.($has_errors ? ' error' : '') ), $label.$input);
    }
	
}