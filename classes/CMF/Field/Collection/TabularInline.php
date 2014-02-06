<?php

namespace CMF\Field\Collection;

use CMF\Admin\ModelForm;

class TabularInline extends Multiselect {
    
    /** inheritdoc */
    public static function type($settings = array())
    {
        return 'tabular-inline';
    }
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array('/admin/assets/js/fields/collection/tabular-inline.js')
        );
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {	
    	$target_class = $settings['mapping']['targetEntity'];
    	$target_metadata = $target_class::metadata();
    	if (isset($value) && $value instanceof \Doctrine\Common\Collections\Collection) {
    		$value = $value->toArray();
    	} else if (!is_array($value)) {
    		$value = array();
    	}
        
        $target_field = ($settings['mapping']['isOwningSide'] === true) ? $settings['mapping']['inversedBy'] : $settings['mapping']['mappedBy'];
        $sortable = $target_class::sortable() && isset($settings['mapping']['orderBy']) && isset($settings['mapping']['orderBy']['pos']) && $settings['mapping']['orderBy']['pos'] == 'ASC';
        $sort_group = $target_class::sortGroup();
        
        // If the target isn't grouped by this relationship, we need to save all the positions at once...
        $save_all = $sort_group != $target_field;
        
        $exclude = array($target_field);
        $hidden_fields = array();
        if ($sortable) $hidden_fields['pos'] = 0;
        
        // The form from which we'll render out each row, but also the blank form for the 'new item' template
        $field_settings = \Arr::get($settings, 'fields');

        $form_template = new ModelForm($target_metadata, new $target_class(), '__TEMP__'.$settings['mapping']['fieldName'].'[__NUM__]', $hidden_fields, $exclude, false, false, $field_settings);
        $cols = $form_template->field_keys;
        $rows = array();
        $js_data = $form_template->js_field_settings;
        
        // Loop through and get each row from the form
    	foreach ($value as $num => $model) {
            $prefix = $settings['mapping']['fieldName'].'['.$num.']';
            $row = $form_template->getFields($model, $prefix);
            $row['hidden_fields']['id'] = \Form::hidden($prefix.'[id]', $model->id, array( 'class' => 'item-id' ));
    		$rows[] = $row;
            $js_data = array_merge($js_data, $row['js_field_settings']);
    	}
        
        $js_data[$settings['mapping']['fieldName']] = array(
            'target_table' => $target_metadata->table['name'],
            'save_all' => $save_all,
            'sortable' => $sortable
        );
        
        return array(
            'assets' => $form_template->assets,
            'content' => strval(\View::forge('admin/fields/collection/tabular-inline.twig', array( 'settings' => $settings, 'singular' => $target_class::singular(), 'plural' => $target_class::plural(), 'rows' => $rows, 'cols' => $cols, 'template' => $form_template->getFieldContent(), 'form' => $form_template, 'sortable' => $sortable ), false)),
            'widget' => true,
            'widget_class' => '',
            'widget_icon' => $target_class::icon(),
            'js_data' => $js_data,
            'merge_data' => true
        );
        
    }
	
}

?>