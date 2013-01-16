<?php

namespace CMF\Field\Collection;

class Checkbox extends Multiselect {
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
    	$value = ($value instanceof \Doctrine\Common\Collections\Collection && count($value) > 0) ? $value->toArray() : array();
    	$value_ids = array_map(function($val) {
    		return $val->id;
    	}, $value);
    	
    	$target_class = $settings['mapping']['targetEntity'];
    	$targets = $target_class::findAll();
    	$checkboxes = array();
    	
    	$checkboxes[] = \Form::hidden($settings['mapping']['fieldName'], null);
    	
    	foreach ($targets as $target) {
    		$checkbox = \Form::checkbox($settings['mapping']['fieldName'].'[]', $target->id, in_array($target->id, $value_ids));
    		$checkboxes[] = html_tag('label', array( 'class' => 'checkbox' ), $checkbox.' '.$target->display());
    	}
    	
    	$group_label = html_tag('label', array(), $settings['title']);
    	$group = html_tag('div', array( 'class' => 'controls control-group checkbox-group' ), implode("\n", $checkboxes));
    	
        return html_tag('div', array( 'class' => 'controls control-group' ), $group_label.$group);
    }
	
}

?>