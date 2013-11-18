<?php

namespace CMF\Field\Object;

class Image extends File {
	
    protected static $defaults = array(
        'path' => 'uploads/images/',
        'dynamic' => false,
        'array' => false,
        'tabular' => false,
        'widget' => false,
        'sub_group' => true,
        'widget_icon' => 'picture',
        'crop' => true,
        'thumb_size' => array( 'width' => 120, 'height' => 80 ),
        'fields' => array(
            'src' => array( 'type' => 'string', 'title' => 'Image' ),
            'width' => array( 'visible' => false ),
            'height' => array( 'visible' => false ),
            'alt' => array( 'type' => 'string' )
        )
    );
    
    public static function type($settings = array())
    {
        return 'image';
    }
    
    /** @inheritdoc */
    public static function process($value, $settings, $model)
    {
        if (!is_array($value)) return parent::process($value, $settings, $model);
        
        $src = DOCROOT.\Arr::get($value, 'src', 'none');
        if (!file_exists($src)) return parent::process($value, $settings, $model);
        
        $info = @getimagesize($src);
        
        if ($info === false) return parent::process($value, $settings, $model);
        
        // Insert image sizes
        if (\Arr::get($value, 'width', 0) === 0) $value['width'] = $info[0];
        if (\Arr::get($value, 'height', 0) === 0) $value['height'] = $info[1];
        
        // Generate crops if there are any missing
        if (is_array($crop = \Arr::get($settings, 'crop'))) {
            
            $crop_values = \Arr::get($value, 'crop', array());
            foreach ($crop as $cropid => $crop_setting) {
                
                $crop_value = \Arr::get($crop_values, $cropid, array());
                if (!isset($crop_value['x']) ||
                    !isset($crop_value['y']) ||
                    !isset($crop_value['width']) ||
                    !isset($crop_value['height'])) {
                    
                    // Insert some sensible defaults for the crop here
                    if (\Arr::get($crop_setting, 'width', 0) > 0 && \Arr::get($crop_setting, 'height', 0) > 0) {
                        
                        // There is an aspect ratio
                        $aspect = $crop_setting['width'] / $crop_setting['height'];
                        
                        $cropw = $value['width'];
                        $croph = round($value['width'] / $aspect);
                        
                        if ($croph < $value['height']) {
                            $croph = $value['height'];
                            $cropw = round($croph * $aspect);
                        }
                        
                        $crop_value['width'] = $cropw;
                        $crop_value['height'] = $croph;
                        $crop_value['x'] = round(($value['width'] - $cropw) / 2);
                        $crop_value['y'] = round(($value['height'] - $croph) / 2);
                        
                    } else {
                        
                        // It's a free crop
                        $crop_value['width'] = $value['width'];
                        $crop_value['height'] = $value['height'];
                        $crop_value['x'] = 0;
                        $crop_value['y'] = 0;
                        
                    }
                    
                }
                
                $crop_values[$cropid] = $crop_value;
                
            }
            
            $value['crop'] = $crop_values;
            
        }
        
        return parent::process($value, $settings, $model);
    }
    
    public static function getCropUrl($image, $width, $height, $crop_id = 'main')
    {
        $crop = \Arr::get($image, "crop.$crop_id", false);
        $src = \Arr::get($image, 'src', false);
        if ($src === false) return null;
        
        if ($crop === false) {
            return "/image/2/$width/$height/".$src;
        }
        
        return "/image/".
        $crop['x']."/".
        $crop['y']."/".
        $crop['width']."/".
        $crop['height']."/".
        $width."/".
        $height."/".
        $src;
    }
    
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        $src = (isset($value) && !empty($value['src'])) ? $value['src'] : 'placeholder.png';
        return \Html::anchor($edit_link, \Html::img('image/2/50/50/'.$src, array()));
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
        
        // Prepare the crop settings
        if ($settings['crop'] === true) $settings['crop'] = array( 'main' => array( 'title' => 'Main Crop' ) );
        if (is_array($settings['crop'])) {
            
            $crop_options = array();
            foreach ($settings['crop'] as $crop_id => $crop_settings) {
                if (is_string($crop_settings)) {
                    $crop_settings = array( 'title' => $crop_settings );
                }
                $crop_settings['id'] = $crop_id;
                $crop_options[] = $crop_settings;
            }
            $settings['crop'] = $crop_options;
            
        }
        
        $content = strval(\View::forge('admin/fields/image.twig', array( 'settings' => $settings, 'value' => $value, 'preview_value' => $preview_value ), false));
        
        $attributes = array(
            'class' => 'field-type-file image controls control-group'.($settings['has_errors'] ? ' error' : ''),
            'data-field-name' => $settings['mapping']['fieldName'],
            'id' => 'field-'.\CMF::fieldId($settings['mapping']['fieldName'])
        );
        
        if (!(isset($settings['wrap']) && $settings['wrap'] === false)) $content = html_tag('div', $attributes, $content);
        
        $output = array(
            'content' => $content,
            'widget' => false
        );
        
        $output['js_data'] = $settings;
        return $output;
        
    }
    
   /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                '/admin/assets/fineuploader/jquery.fineuploader-3.2.js',
                '/admin/assets/jcrop/jquery.Jcrop.min.js',
                '/admin/assets/js/fields/image.js'
            ),
            'css' => array(
                '/admin/assets/jcrop/jquery.Jcrop.min.css'
            )
        );
    }
	
}