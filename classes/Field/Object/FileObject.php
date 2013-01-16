<?php

namespace CMF\Field\Object;

class FileObject extends Object {
    
    protected static $defaults = array(
        'path' => 'uploads/files/',
        'dynamic' => false,
        'array' => false,
        'tabular' => false,
        'widget' => false,
        'widget_icon' => 'file',
        'sub_group' => true,
        'fields' => array(
            'src' => array( 'title' => '', 'type' => 'file' )
        )
    );
    
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        if (isset($value) && isset($value['src'])) {
            return \Html::anchor($edit_link, '/'.$value);
        }
        return '-';
    }
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                '/admin/assets/js/fields/file.js'
            )
        );
    }
	
}