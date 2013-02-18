<?php

namespace CMF\Field;

class Image extends File {
    
    protected static $defaults = array(
        'crop' => true,
        'thumb_size' => array( 'width' => 120, 'height' => 80 ),
        'path' => 'uploads/images/'
    );
	
	/** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                '/admin/assets/fineuploader/jquery.fineuploader-3.2.js',
                '/admin/assets/js/fields/image.js'
            ),
            'css' => array(
                //'/admin/assets/fineuploader/fineuploader-3.2.css'
            )
        );
    }
    
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        if (isset($value)) {
            return \Html::anchor($edit_link, \Html::img('/image/2/50/50/'.(!empty($value) ? $value : 'placeholder.png'), array()));
        }
        return \Html::anchor($edit_link, \Html::img('/image/2/50/50/placeholder.png'), array());
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
        $content = strval(\View::forge('admin/fields/image.twig', array( 'settings' => $settings, 'value' => $value, 'preview_value' => $preview_value ), false));
        
        if (!(isset($settings['wrap']) && $settings['wrap'] === false)) $content = html_tag('div', $attributes, $content);
        
        return array(
            'content' => $content,
            'widget' => false,
            'assets' => array(),
            'js_data' => $settings
        );
        
    }
	
}