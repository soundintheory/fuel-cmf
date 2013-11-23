<?php

namespace CMF\Field\Object;

class File extends Object {
    
    protected static $defaults = array(
        'path' => 'uploads/files/',
        'dynamic' => false,
        'array' => false,
        'tabular' => false,
        'widget' => false,
        'widget_icon' => 'file',
        'sub_group' => true,
        'fields' => array(
            'src' => array( 'title' => 'File', 'type' => 'string' ),
            'last_modified' => array( 'type' => 'string' )
        )
    );
    
    public static function type($settings = array())
    {
        return 'image';
    }
    
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        if (isset($value) && isset($value['src'])) {
            return \Html::anchor($edit_link, '/'.$value['src']);
        }
        return '-';
    }
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        if (!is_array($value)) $value = array( 'src' => $value );
        
        $settings = static::settings($settings);
        $settings['label'] = isset($settings['label']) ? $settings['label'] : true;
        $settings['required'] = isset($settings['required']) ? $settings['required'] : false;
        $settings['errors'] = $model->getErrorsForField($settings['mapping']['fieldName']);
        $settings['has_errors'] = count($settings['errors']) > 0;
        $preview_value = (isset($value) && isset($value['src'])) ? str_replace($settings['path'], '', $value['src']) : '';
        $content = strval(\View::forge('admin/fields/file.twig', array( 'settings' => $settings, 'value' => $value, 'preview_value' => $preview_value ), false));
        
        $attributes = array(
            'class' => 'field-type-file file controls control-group'.($settings['has_errors'] ? ' error' : ''),
            'data-field-name' => $settings['mapping']['fieldName'],
            'id' => 'field_'.\CMF::slug($settings['mapping']['fieldName'])
        );
        
        if (!(isset($settings['wrap']) && $settings['wrap'] === false)) $content = html_tag('div', $attributes, $content);
        
        return array(
            'content' => $content,
            'widget' => false,
            'assets' => array(),
            'js_data' => $settings
        );
        
    }
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                '/admin/assets/fineuploader/jquery.fineuploader-3.2.js',
                '/admin/assets/js/fields/file.js'
            ),
            'css' => array(
                //'/admin/assets/fineuploader/fineuploader-3.2.css'
            )
        );
    }
	
}