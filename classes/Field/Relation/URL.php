<?php

namespace CMF\Field\Relation;

class URL extends OneToOne {
	
	/** inheritdoc */
    public static function getAssets()
    {
		return array(
			'js' => array(
				'/admin/assets/js/fields/url.js'
			)
		);
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $include_label = isset($settings['label']) ? $settings['label'] : true;
    	$target_class = $settings['mapping']['targetEntity'];
    	if (is_null($value) || !$value instanceof $target_class) $value = new $target_class();
    	
    	$model_class = get_class($model);
    	$errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $attributes = array( 'class' => 'field-type-url controls control-group'.($has_errors ? ' error' : '') );
        $slug_name = $settings['mapping']['fieldName'].'[slug]';
        
        $keep_updated_setting = 'settings['.$settings['mapping']['fieldName'].'][keep_updated]';
        $keep_updated = \Form::hidden($keep_updated_setting, '0', array()).html_tag('label', array( 'class' => 'checkbox keep-updated' ), \Form::checkbox($keep_updated_setting, '1', \Arr::get($settings, 'keep_updated', true), array()).' auto update');
        $input = \Form::input($slug_name, $value->slug, array( 'class' => 'input-xlarge', 'data-copy-from' => implode(',', $model_class::slugFields()) ));
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($has_errors ? ' - '.$errors[0] : ''), $slug_name, array( 'class' => 'item-label' )).$keep_updated.html_tag('div', array( 'class' => 'clear' ), '&nbsp;');
        $prefix = $value->prefix;
        $prepend = html_tag('span', array( 'class' => 'add-on' ), empty($prefix) ? '/' : $prefix );
        $input = html_tag('div', array( 'class' => 'input-prepend' ), $prepend.$input);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
    	
    	return html_tag('div', $attributes, $label.$input);
    }
	
}