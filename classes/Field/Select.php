<?php

namespace CMF\Field;

class Select extends Base {
    
    /** @inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        try {
            return '<a href="'.$edit_link.'" class="item-link">'.strval($value).'</a>';
        } catch (\Exception $e) {
            return "Error: unkown type";
        }
    }
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge' );
        $options;
        if (isset($settings['mapping']['nullable']) && $settings['mapping']['nullable']) {
            $options = isset($settings['options']) ? array_merge(array( null => '---' ), $settings['options']) : array( null => '---' );
        } else {
            $options = isset($settings['options']) ? $settings['options'] : array();
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