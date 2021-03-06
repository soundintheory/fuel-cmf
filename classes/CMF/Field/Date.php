<?php

namespace CMF\Field;

class Date extends Base {
    
    protected static $defaults = array(
        'default' => null,
        'default_offset' => null,
        'format' => 'd/m/Y'
    );
    
    public static function process($value, $settings, $model)
    {
        $settings = static::settings($settings);
        if (!($value instanceof \DateTime)) $value = \DateTime::createFromFormat($settings['format'], $value);
        if ($value === false) $value = (!is_null($settings['default'])) ? \DateTime::createFromFormat($settings['format'], $settings['default']) : new \DateTime();
        if ($value === false) $value = new \DateTime();
        return $value;
    }
    
    /** @inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return isset($value) ? $value->format('d M Y') : '(empty)';
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
        $inputId = preg_replace('/[^\w-_]+/', '-', $settings['mapping']['fieldName']);
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-large', 'id' => $inputId );
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $inputId, array( 'class' => 'item-label' ));
        $input = \Form::input($settings['mapping']['fieldName'], $value->format($settings['format']), $input_attributes);
        $input = $input = html_tag('div', array( 'class' => 'input-prepend' ), html_tag('span', array( 'class' => 'add-on' ), '<i class="fa fa-calendar"></i>').$input);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', array( 'class' => 'controls control-group field-type-date'.($has_errors ? ' error' : '') ), $label.$input);
    }

    public static function getTranslatableAttributes()
    {
        return array_merge(parent::getTranslatableAttributes(), array(
            'format'
        ));
    }
	
}