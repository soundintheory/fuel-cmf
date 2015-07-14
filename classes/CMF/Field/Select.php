<?php


namespace CMF\Field;

class Select extends Base {
    
    protected static $defaults = array(
        'options' => array(),
        'allow_empty' => true,
        'use_key' => false,
        'output' => 'value',
        'multiple' => false,
        'select2' => false
    );
    
    public static function process($value, $settings, $model)
    {
        $settings = static::settings($settings);
        if (@$settings['multiple'] && is_array($value)) return implode(',', $value);
        return parent::process($value, $settings, $model);
    }
    
    public static function getValue($value, $settings, $model)
    {
        if (!\Arr::is_assoc($settings['options']) || $settings['use_key'] === true) return $value;
        if (is_numeric($value)) $value = trim(strval($value), ' ').' ';
        $option = isset($settings['options'][$value]) ? $settings['options'][$value] : null;
        if (is_array($option)) {
            $output = isset($settings['output']) ? $settings['output'] : 'value';
            return isset($option[$output]) ? $option[$output] : $option;
        }
        return $option;
    }
    
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
        $options = \CMF::getOptions(get_class($model), $settings['mapping']['columnName'], isset($settings['options']) ? $settings['options'] : array());

        // Description?
        $description = isset($settings['description']) ? '<span class="help-block">'.$settings['description'].'</span>' : '';
        
        if (!empty($options) && !\Arr::is_assoc($options) && $settings['use_key'] !== true) {
            $options = array_combine($options, $options);
        } else if (!empty($options)) {
            reset($options);
            $first = current($options);
            if (is_array($first) && isset($first['value'])) {
                $options = array_map(function($option) {
                    return $option['value'];
                }, $options);
            }
        }
        
        if (@$settings['multiple']) {
            
            if (is_null($value) || empty($value)) {
                if (@$settings['default'] == 'all' && is_array($options)) {
                    $value = array_keys($options);
                }
            }
            
            if (!is_array($value)) $value = explode(',', $value);
            $input_attributes['multiple'] = 'multiple';
            
        } else if (isset($settings['mapping']['nullable']) && $settings['mapping']['nullable'] && 
            !(isset($settings['required']) && $settings['required']) &&
            $settings['allow_empty']) {
            $options = array( '' => '' ) + $options;
        }
        
        // Transform the options into the right format
        foreach ($options as $key => $option) {
            if (is_array($option))
                $options[$key] = \Arr::get($option, 'title', $key);
        }
        
        // Select2?
        if (is_array($settings['select2'])) {
            
            $settings['is_select2'] = true;
            $input_attributes['class'] .= ' input-xxlarge select2';
            $settings['select2']['placeholder'] = 'click to select an option';
            
            $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
            $input = \Form::select($settings['mapping']['fieldName'], $value, $options, $input_attributes);
            $content = $label.$description.$input;
            if (!(isset($settings['wrap']) && $settings['wrap'] === false)) $content = html_tag('div', array( 'class' => 'controls control-group'.($has_errors ? ' error' : '') ), $content);
            
            return array(
                'content' => $content.html_tag('div', array(), ''),
                'widget' => @$settings['widget'],
                'assets' => array(
                    'css' => array('/admin/assets/select2/select2.css'),
                    'js' => array('/admin/assets/select2/select2.min.js', '/admin/assets/js/fields/select2.js')
                ),
                'js_data' => $settings['select2']
            );
        }

        if (!is_string($value)) {
            $value = $value.' ';
        }
        
        $options = array_map('strip_tags', $options);
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        $input = \Form::select($settings['mapping']['fieldName'], strval($value), $options, $input_attributes);

        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', array( 'class' => 'controls control-group'.($has_errors ? ' error' : '') ), $label.$description.$input);
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