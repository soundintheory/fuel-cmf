<?php

namespace CMF\Field;

class File extends Base {
    
    protected static $defaults = array(
        'path' => 'uploads/files/'
    );
    
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
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        $settings['label'] = isset($settings['label']) ? $settings['label'] : true;
        $settings['required'] = isset($settings['required']) ? $settings['required'] : false;
        $settings['errors'] = $model->getErrorsForField($settings['mapping']['fieldName']);
        $settings['has_errors'] = count($settings['errors']) > 0;
        $preview_value = (isset($value) && !empty($value)) ? str_replace($settings['path'], '', $value) : '';
        
        $attributes = array( 'class' => 'field-type-file controls control-group'.($settings['has_errors'] ? ' error' : '') );
        $content = strval(\View::forge('admin/fields/file.twig', array( 'settings' => $settings, 'value' => $value, 'preview_value' => $preview_value ), false));
        
        if (!(isset($settings['wrap']) && $settings['wrap'] === false)) $content = html_tag('div', $attributes, $content);
        
        return array(
            'content' => $content,
            'widget' => false,
            'assets' => array(),
            'js_data' => $settings
        );
        
    }
    
    /** @inheritdoc */
    public static function process($value, $settings, $model)
    {
        if (isset($value) && is_array($value)) {
            $settings = static::settings($settings);
            $value['model'] = $model;
            $value['field_settings'] = $settings;
            $value['saved_to'] = $settings['path'];
            \Upload::set_file($value['file_index'], $value);
            return $value['file'];
        }
        return $value;
    }
    
}