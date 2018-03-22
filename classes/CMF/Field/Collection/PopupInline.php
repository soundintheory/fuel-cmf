<?php

namespace CMF\Field\Collection;

use CMF\Admin\ModelForm;

class PopupInline extends Multiselect {
    
    /** inheritdoc */
    public static function type($settings = array())
    {
        return 'popup-inline';
    }
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                '/assets/fancybox/jquery.fancybox.pack.js',
                '/assets/js/fields/collection/popup-inline.js'
            ),
            'css' => array(
                '/assets/fancybox/jquery.fancybox.css'
            )
        );
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);

    	$target_class = $settings['mapping']['targetEntity'];
    	$target_metadata = $target_class::metadata();
        $settings['cid'] = 'field_'.md5($settings['mapping']['fieldName'].static::type());
        $target_prop = ($settings['mapping']['isOwningSide'] === true) ? $settings['mapping']['inversedBy'] : $settings['mapping']['mappedBy'];
        if (empty($target_prop) || is_null($model->id)) $target_prop = false;

        
        // Get the array of possible types
        $types = array($target_class);
        $exclude_types = array();
        if (count($target_metadata->subClasses) > 0) {
            if ($target_class::superclass() === true) $types = array();
            $types = array_merge($types, $target_metadata->subClasses);
        }
        
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
        
        // The forms from which we'll render out each row, but also the blank forms for the 'new item' templates
        $form_templates = array();
        $js_data = array();
        $target_tables = array();
        $templates_content = array();
        $assets = array();
        $add_types = array();
        $edit_qs = '?_mode=inline&_cid='.$settings['cid'].($target_prop !== false ? '&'.$target_prop.'='.$model->id : '');
        
        foreach ($types as $type)
        {
            $metadata = $type::metadata();
            $prefix = '__TEMP__'.$settings['mapping']['fieldName'].'[__NUM__]';
            $target_tables[$type] = $metadata->table['name'];
            $templates_content[$type] = array(
                'icon' => $type::icon(),
                'singular' => $type::singular(),
                'prefix' => $prefix,
                'can_duplicate' => !$type::_static() && @$settings['create'],
                'edit_link' => \CMF::adminUrl('/'.$metadata->table['name'].'/__ID__/edit'.$edit_qs),
                'hidden_fields' => array(
                    'id' => \Form::hidden($prefix.'[id]', '', array( 'class' => 'item-id', 'data-field-name' => 'id' )),
                    'pos' => \Form::hidden($prefix.'[pos]', '', array( 'data-field-name' => 'pos' )),
                    '__type__' => \Form::hidden($prefix.'[__type__]', $type, array( 'class' => 'item-type' ))
                )
            );
            $add_types[] = array(
                'type' => $type,
                'singular' => $type::singular(),
                'plural' => $type::plural(),
                'icon' => $type::icon(),
                'add_link' => \CMF::adminUrl('/'.$metadata->table['name'].'/create'.$edit_qs)
            );
        }
        
        // Loop through and get each row from the form
        $rows = array();
    	foreach ($value as $num => $item) {
            
            // Get the class of this item, check if it's a proxy
            $type = get_class($item);
            if (strpos($type, 'Proxy') === 0) $type = get_parent_class($item);
            $metadata = $type::metadata();
            
            $prefix = $settings['mapping']['fieldName'].'['.$num.']';
            $row = array(
                '_icon_' => $type::icon(),
                '_title_' => $item->display(),
                'edit_link' => \CMF::adminUrl('/'.$metadata->table['name'].'/'.$item->id.'/edit'.$edit_qs),
                'can_duplicate' => !$type::_static() && @$settings['create'],
                'hidden_fields' => array(
                    'id' => \Form::hidden($prefix.'[id]', $item->id, array( 'class' => 'item-id', 'data-field-name' => 'id' )),
                    'pos' => \Form::hidden($prefix.'[pos]', $item->pos, array( 'data-field-name' => 'pos' )),
                    '__type__' => \Form::hidden($prefix.'[__type__]', $type, array( 'class' => 'item-type' ))
                )
            );
            
    		$rows[] = $row;
    	}
        
        $js_data[$settings['mapping']['fieldName']] = array(
            'target_tables' => $target_tables,
            'add_types' => $add_types,
            'save_all' => $save_all,
            'sortable' => $sortable,
            'cid' => $settings['cid'],
            'edit_qs' => $edit_qs
        );
        
        return array(
            'assets' => $assets,
            'content' => strval(\View::forge('admin/fields/collection/popup-inline.twig', array( 'settings' => $settings, 'add_types' => $add_types, 'singular' => $target_class::singular(), 'plural' => $target_class::plural(), 'rows' => $rows, 'templates' => $templates_content, 'sortable' => $sortable ), false)),
            'widget' => true,
            'widget_class' => '',
            'widget_icon' => $target_class::icon(),
            'js_data' => $js_data,
            'merge_data' => true
        );
        
    }
	
}

?>