<?php

namespace CMF\Field;

class DateTime extends Date {
    
    protected static $defaults = array(
        'default' => null,
        'default_offset' => null,
        'format' => 'd/m/Y H:i',
        'list_format' => 'M jS H:i'
    );
    
    public static function process($value, $settings, $model)
    {
        $settings = static::settings($settings);
        if (!($value instanceof \DateTime)) $value = \DateTime::createFromFormat(\Arr::get($settings, 'format', 'd/m/Y H:i'), $value);
        if ($value === false) $value = new \DateTime();
        return $value;
    }
    
    /** @inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return '<a href="'.$edit_link.'" class="item-link">'.$value->format(\Arr::get($settings, 'list_format', 'M jS H:i')).'</a>';
    }
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        if (!isset($value) || !$value) {
            $value = (!is_null($settings['default'])) ? \DateTime::createFromFormat($settings['format'], $settings['default']) : new \DateTime();
            if (!is_null($settings['default_offset'])) {
                $value->modify($settings['default_offset']);
            }
        }
        
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-large' );
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        $input = \Form::input($settings['mapping']['fieldName'], $value->format($settings['format']), $input_attributes);
        $input = $input = html_tag('div', array( 'class' => 'input-prepend' ), html_tag('span', array( 'class' => 'add-on' ), '<i class="fa fa-calendar"></i>').$input);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', array( 'class' => 'controls control-group field-type-datetime'.($has_errors ? ' error' : '') ), $label.$input);
    }
	
}