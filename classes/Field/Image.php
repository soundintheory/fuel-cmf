<?php

namespace CMF\Field;

class Image extends File {
    
    protected static $defaults = array(
        'thumb_size' => array( 'width' => 80, 'height' => 50 ),
        'path' => 'uploads/images/'
    );
	
	/** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                '/admin/assets/js/fields/image.js'
            )
        );
    }
    
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        if (isset($value)) {
            return \Html::anchor($edit_link, \Html::img('/image/2/50/50/'.$value, array()));
        }
        return '-';
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
        
        $attributes = array( 'class' => 'field-type-image controls control-group'.($settings['has_errors'] ? ' error' : '') );
        $content = strval(\View::forge('admin/fields/image.twig', array( 'settings' => $settings, 'value' => $value, 'preview_value' => $preview_value ), false));
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $content;
        
        return html_tag('div', $attributes, $content);
    }
	
}