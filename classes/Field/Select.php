<?php

namespace CMF\Field;

class Select extends Base {
    
    protected static $defaults = array(
        'allow_empty' => true
    );
    
    /** @inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        try {
            $value = strval($value);
            if (is_array($settings['options']) && \Arr::is_assoc($settings['options'])) {
                $value = $settings['options'][$value];
            }
            return '<a href="'.$edit_link.'" class="item-link">'.$value.'</a>';
        } catch (\Exception $e) {
            return '(empty)';
        }
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
        $options = isset($settings['options']) ? $settings['options'] : array();
        
        if (!empty($options) && !\Arr::is_assoc($options)) {
            $options = array_combine($options, $options);
        }
        
        if (isset($settings['mapping']['nullable']) && $settings['mapping']['nullable'] && 
            !(isset($settings['required']) && $settings['required']) &&
            $settings['allow_empty']) {
            $options = array_merge(array( null => '---' ), $options);
        }
        
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        $input = \Form::select($settings['mapping']['fieldName'], $value, $options, $input_attributes);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', array( 'class' => 'controls control-group'.($has_errors ? ' error' : '') ), $label.$input);
    }
    
    /** @inheritdoc */
    public static function getAssets()
    {
        return array(
            //'js' => array('/admin/assets/js/fields/base.js'),
            //'css' => array('/admin/assets/css/fields/base.css'),
        );
    }
	
}