<?php

namespace CMF\Field\Relation;

class ManyToOne extends \CMF\Field\Base {
    
    protected static $defaults = array(
        'select2' => array(
            'allowClear' => false
        ),
        'input_attributes' => array(
            'class' => ''
        )
    );
    
    protected $options = array();
    protected $parent_entity;
    
    /** inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return (is_null($value)) ? '(empty)' : '<a href="'.$edit_link.'" class="item-link">'.$value->display().'</a>';
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
    	$id = isset($value) ? $value->id : '';
    	
        $settings = static::settings($settings);
        $settings['cid'] = 'field_'.md5($settings['mapping']['fieldName'].static::type());
        $required = isset($settings['required']) ? $settings['required'] : false;
        $include_label = isset($settings['label']) ? $settings['label'] : true;
    	$target_class = $settings['mapping']['targetEntity'];
    	$options = $target_class::options();
        $null_option = array( '' => '' );
        $options = $null_option + $options;
    	
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = $settings['input_attributes'];
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        
        if (is_array($settings['select2'])) {
            
            $input_attributes['class'] .= ' select2';
            $input = \Form::select($settings['mapping']['fieldName'], $id, $options, $input_attributes);
            $settings['select2']['placeholder'] = 'Select a '.$target_class::singular();
            
            if (!$required) {
                $settings['select2']['allowClear'] = true;
            }
            
            return array(
                'content' => html_tag('div', array( 'class' => 'controls control-group'.($has_errors ? ' error' : ''), 'id' => $settings['cid'] ), $label.$input),
                'widget' => false,
                'assets' => array(
                    'css' => array('/admin/assets/select2/select2.css'),
                    'js' => array('/admin/assets/select2/select2.min.js', '/admin/assets/js/fields/select2.js')
                ),
                'js_data' => $settings['select2']
            );
            
        }
        
        $input_attributes['class'] .= ' input-xxlarge';
        
        $input = \Form::select($settings['mapping']['fieldName'], $id, $options, $input_attributes);
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        return html_tag('div', array( 'class' => 'controls control-group'.($has_errors ? ' error' : ''), 'id' => $settings['cid'] ), $label.$input);
    }
    
    /** inheritdoc */
    public static function type($settings = array())
    {
        return 'select';
    }
	
}