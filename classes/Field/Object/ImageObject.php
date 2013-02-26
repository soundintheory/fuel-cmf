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
            'src' => array( 'type' => 'image', 'title' => 'Image' ),
            'width' => array( 'visible' => false ),
            'height' => array( 'visible' => false ),
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
        
        // Need to tell the image field that it's part of this object,
        // and tell it about the fields defined in it
        $settings['fields']['src']['__object__'] = true;
        $settings['fields']['src']['fields'] = $settings['fields'];
        $settings['fields']['src']['path'] = $settings['path'];
        
        if ($settings['widget'] === true) {
            $settings['fields']['src']['title'] = $settings['title'];
        }
        
        $content = parent::displayForm($value, $settings, $model);
        $content['content'] .= '<input type="hidden" name="'.$settings['mapping']['fieldName'].'[width]" value="'.(isset($value['width']) ? $value['width'] : 0).'" />';
        $content['content'] .= '<input type="hidden" name="'.$settings['mapping']['fieldName'].'[height]" value="'.(isset($value['height']) ? $value['height'] : 0).'" />';
        return $content;
        
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