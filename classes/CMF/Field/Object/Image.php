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
                    
                    // Find the canvas scale
                    $cscale = 390 / $value['height'];
                    if (round($value['width'] * $cscale) > 565) $cscale = 565 / $value['width'];
                    if ($cscale > 1) $cscale = 1;
                    
                    // Scale of the image within the crop can be overridden
                    $image_scale = 100 / intval(\Input::param('imagecropscale', 100));
                    $bias_x = intval(\Input::param('imagecropbiasx', 0));
                    $bias_y = intval(\Input::param('imagecropbiasy', 0));
                    if ($bias_x !== 0) $bias_x = 100 / $bias_x;
                    if ($bias_y !== 0) $bias_y = 100 / $bias_y;
                    
                    print('Bias X: '.$bias_x."\n");
                    print('Bias Y: '.$bias_y."\n\n");
                    
                    // Insert some sensible defaults for the crop here
                    if (\Arr::get($crop_setting, 'width', 0) > 0 && \Arr::get($crop_setting, 'height', 0) > 0) {
                        
                        // There is an aspect ratio
                        $aspect = $crop_setting['width'] / $crop_setting['height'];
                        
                        $cropw = round($value['width'] * $image_scale);
                        $croph = round($cropw / $aspect);
                        
                        if ($croph < round($value['height'] * $image_scale)) {
                            $croph = round($value['height'] * $image_scale);
                            $cropw = round($croph * $aspect);
                        }
                        
                        $actualw = round($cropw * $cscale);
                        $actualh = round($croph * $cscale);
                        $cropscale = 1;
                        
                        // Adjust the crop scale if it's not gonna fit the canvas
                        if ($actualh > 390) $cropscale = 390 / $actualh;
                        if (round($actualw * $cropscale) > 565) $cropscale = 565 / $actualw;
                        
                        // This is the 'real' size of the crop in the editor
                        $actualw = round($actualw * $cropscale);
                        $actualh = round($actualh * $cropscale);
                        
                        $crop_value['width'] = $cropw;
                        $crop_value['height'] = $croph;
                        $crop_value['x'] = round(($value['width'] - $cropw) / 2);
                        $crop_value['y'] = round(($value['height'] - $croph) / 2);
                        
                         // Crop offset using the bias param
                        $crop_offset_x = round($crop_value['x'] * $bias_x);
                        $crop_offset_y = round($crop_value['y'] * $bias_y);
                        $crop_value['x'] = $crop_value['x'] + $crop_offset_x;
                        $crop_value['y'] = $crop_value['y'] + $crop_offset_y;
                        
                        // Figure out the actual sizes
                        $actual_crop_offset_x = round(round($crop_value['x'] * $cscale) * $cropscale);
                        $actual_crop_offset_y = round(round($crop_value['y'] * $cscale) * $cropscale);
                        $actual_imagew = round(round($value['width'] * $cscale) * $cropscale);
                        $actual_imageh = round(round($value['height'] * $cscale) * $cropscale);
                        $origin_x = round((565 - $actual_imagew) / 2);
                        $origin_y = round((390 - $actual_imageh) / 2);
                        
                        // Check if we need to scale for horizontal offset
                        if ($origin_x + $actual_crop_offset_x < 0) {
                            $diff = abs($origin_x + $actual_crop_offset_x);
                            $cropscale *= (565 / (565 + ($diff * 2)));
                        } else if (($origin_x + $actual_crop_offset_x + $actualw) > 565) {
                            $diff = ($origin_x + $actual_crop_offset_x + $actualw) - 565;
                            $cropscale *= (565 / (565 + ($diff * 2)));
                        }
                        
                        // Now recalculate all the actual sizes...
                        $actualw = round(round($cropw * $cscale) * $cropscale);
                        $actualh = round(round($croph * $cscale) * $cropscale);
                        $actual_crop_offset_x = round(round($crop_value['x'] * $cscale) * $cropscale);
                        $actual_crop_offset_y = round(round($crop_value['y'] * $cscale) * $cropscale);
                        $actual_imagew = round(round($value['width'] * $cscale) * $cropscale);
                        $actual_imageh = round(round($value['height'] * $cscale) * $cropscale);
                        $origin_x = round((565 - $actual_imagew) / 2);
                        $origin_y = round((390 - $actual_imageh) / 2);
                        
                        // Check if we need to scale for vertical offset
                        if ($origin_y + $actual_crop_offset_y < 0) {
                            $diff = abs($origin_y + $actual_crop_offset_y);
                            $cropscale *= (390 / (390 + ($diff * 2)));
                        } else if ($origin_y + $actual_crop_offset_y + $actualh > 390) {
                            $diff = ($origin_y + $actual_crop_offset_y + $actualh) - 390;
                            $cropscale *= (390 / (390 + ($diff * 2)));
                        }
                        
                        $crop_value['scale'] = round($cropscale * 100);
                         
                    } else {
                        
                        // It's a free crop
                        $crop_value['width'] = round($value['width'] * $image_scale);
                        $crop_value['height'] = round($value['height'] * $image_scale);
                        $crop_value['x'] = round(($value['width'] - $crop_value['width']) / 2);
                        $crop_value['y'] = round(($value['height'] - $crop_value['height']) / 2);
                        
                        // Work out crop scale...
                        $actualw = round($crop_value['width'] * $cscale);
                        $actualh = round($crop_value['height'] * $cscale);
                        $cropscale = 1;
                        
                        // Adjust the crop scale if it's not gonna fit the canvas
                        if ($actualh > 390) $cropscale = 390 / $actualh;
                        if (round($actualw * $cropscale) > 565) $cropscale = 565 / $actualw;
                        
                        // This is the 'real' size of the crop in the editor
                        $actualw = round($actualw * $cropscale);
                        $actualh = round($actualh * $cropscale);
                        
                        // Crop offset using the bias param
                        $crop_offset_x = round($crop_value['x'] * $bias_x);
                        $crop_offset_y = round($crop_value['y'] * $bias_y);
                        $crop_value['x'] = $crop_value['x'] + $crop_offset_x;
                        $crop_value['y'] = $crop_value['y'] + $crop_offset_y;
                        
                        // Figure out the actual sizes
                        $actual_crop_offset_x = round(round($crop_value['x'] * $cscale) * $cropscale);
                        $actual_crop_offset_y = round(round($crop_value['y'] * $cscale) * $cropscale);
                        $actual_imagew = round(round($value['width'] * $cscale) * $cropscale);
                        $actual_imageh = round(round($value['height'] * $cscale) * $cropscale);
                        $origin_x = round((565 - $actual_imagew) / 2);
                        $origin_y = round((390 - $actual_imageh) / 2);
                        
                        // Check if we need to scale for horizontal offset
                        if ($origin_x + $actual_crop_offset_x < 0) {
                            $diff = abs($origin_x + $actual_crop_offset_x);
                            $cropscale *= (565 / (565 + ($diff * 2)));
                        } else if (($origin_x + $actual_crop_offset_x + $actualw) > 565) {
                            $diff = ($origin_x + $actual_crop_offset_x + $actualw) - 565;
                            $cropscale *= (565 / (565 + ($diff * 2)));
                        }
                        
                        // Now recalculate all the actual sizes...
                        $actualw = round(round($cropw * $cscale) * $cropscale);
                        $actualh = round(round($croph * $cscale) * $cropscale);
                        $actual_crop_offset_x = round(round($crop_value['x'] * $cscale) * $cropscale);
                        $actual_crop_offset_y = round(round($crop_value['y'] * $cscale) * $cropscale);
                        $actual_imagew = round(round($value['width'] * $cscale) * $cropscale);
                        $actual_imageh = round(round($value['height'] * $cscale) * $cropscale);
                        $origin_x = round((565 - $actual_imagew) / 2);
                        $origin_y = round((390 - $actual_imageh) / 2);
                        
                        // Check if we need to scale for vertical offset
                        if ($origin_y + $actual_crop_offset_y < 0) {
                            $diff = abs($origin_y + $actual_crop_offset_y);
                            $cropscale *= (390 / (390 + ($diff * 2)));
                        } else if ($origin_y + $actual_crop_offset_y + $actualh > 390) {
                            $diff = ($origin_y + $actual_crop_offset_y + $actualh) - 390;
                            $cropscale *= (390 / (390 + ($diff * 2)));
                        }
                        
                        $crop_value['scale'] = round($cropscale * 100);
                        
                    }
                    
                }
                
                $crop_values[$cropid] = $crop_value;
                
            }
            
            $value['crop'] = $crop_values;
            print_r($value);
            
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