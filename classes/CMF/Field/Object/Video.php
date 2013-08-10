<?php

namespace CMF\Field\Object;

class Video extends File {
    
    protected static $defaults = array(
        'path' => 'uploads/videos/',
        'dynamic' => false,
        'array' => false,
        'tabular' => false,
        'widget' => false,
        'widget_icon' => 'video',
        'fields' => array( 'src' => array( 'type' => 'string' ), 'converted' => array( 'type' => 'array' ), 'poster' => array( 'type' => 'string' ) )
    );
    
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        if (isset($value)) {
            
            if (isset($value['poster']) && !empty($value['poster'])) {
                return \Html::anchor($edit_link, \Html::img('image/2/110/80/'.$value['poster'], array()));
            } else {
                return \Html::anchor($edit_link, '/'.$value['src']);
            }
            
        }
        return '-';
    }
    
    public static function getValue($value, $settings, $model)
    {
        // Check if the src is set and the converted values are not - this means we need to check for
        // the progress file and possibly update the database
        
        if (is_array($value) && isset($value['src']) && strlen($value['src']) > 0 && (!isset($value['converted']) || empty($value['converted']))) {
            
            // See if the progress file exists
            $path = DOCROOT.$value['src'];
            if (file_exists($path.'.progress')) {
                $value['progress'] = json_decode(file_get_contents($path.'.progress'));
            } else {
                // It doesn't exist - populate the field
                if (isset($value['progress'])) unset($value['progress']);
                $path_info = pathinfo($value['src']);
                $value['poster'] = $path_info['dirname'].'/'.$path_info['basename'].'.jpg';
                $value['converted'] = array(
                    'mp4' => $path_info['dirname'].'/converted/'.$path_info['filename'].'.mp4',
                    'webm' => $path_info['dirname'].'/converted/'.$path_info['filename'].'.webm'
                );
                
                //$class = \CMF::getClass($model);
                $field_name = $settings['mapping']['fieldName'];
                $model->set($field_name, $value);
                
                \D::manager()->persist($model);
                \D::manager()->flush();
                
            }
            
        }
        return $value;
    }
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        $settings['label'] = isset($settings['label']) ? $settings['label'] : true;
        $settings['required'] = isset($settings['required']) ? $settings['required'] : false;
        $settings['errors'] = $model->getErrorsForField($settings['mapping']['fieldName']);
        $settings['has_errors'] = count($settings['errors']) > 0;
        
        $attributes = array(
            'class' => 'field-type-file video controls control-group'.($settings['has_errors'] ? ' error' : ''),
            'data-field-name' => $settings['mapping']['fieldName'],
            'id' => 'field_'.\CMF::slug($settings['mapping']['fieldName'])
        );
        
        $value = static::getValue($value, $settings, $model);
        $content = strval(\View::forge('admin/fields/video.twig', array( 'settings' => $settings, 'value' => $value ), false));
        $settings['value'] = $value;
        
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
                '/admin/assets/js/fields/video.js'
            ),
            'css' => array(
                //'/admin/assets/fineuploader/fineuploader-3.2.css'
            )
        );
    }
    
    public static function type($settings = array())
    {
        return 'video';
    }
    
}