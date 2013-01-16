<?php

namespace CMF\Field\Object;

class ImageObject extends FileObject {
	
    protected static $defaults = array(
        'path' => 'uploads/files/',
        'dynamic' => false,
        'array' => false,
        'tabular' => false,
        'widget' => false,
        'sub_group' => true,
        'widget_icon' => 'picture',
        'fields' => array(
            'src' => array( 'type' => 'image' ),
            'alt' => array( 'type' => 'string' )
        )
    );
    
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        $src = (isset($value) && isset($value['src'])) ? $value['src'] : 'placeholder.png';
        return \Html::anchor($edit_link, \Html::img('image/2/50/50/'.$src, array()));
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        if ($settings['widget'] !== true) {
            $settings['fields']['src']['title'] = '';
        } else if (!isset($settings['fields']['src']['title'])) {
            $settings['fields']['src']['title'] = $settings['title'];
        }
        return parent::displayForm($value, $settings, $model);
    }
    
	/** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                '/admin/assets/js/fields/image.js'
            )
        );
    }
    
    public static function type($settings = array())
    {
        return 'image-'.parent::type($settings);
    }
	
}