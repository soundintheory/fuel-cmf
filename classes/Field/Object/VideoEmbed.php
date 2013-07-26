<?php

namespace CMF\Field\Object;

use CMF\Admin\ObjectForm,
    CMF\Field\Data\ObjectData;

class VideoEmbed extends Object {
    
    protected static $defaults = array(
        'dynamic' => false,
        'array' => false,
        'tabular' => false,
        'widget' => false,
        'widget_icon' => 'film',
        'sub_group' => true,
        'fields' => array(
            'url' => array( 'title' => 'Video URL', 'type' => 'string' ),
            'poster_image' => array( 'title' => 'Poster Image', 'type' => 'image' ),
            'video_id' => array( 'visible' => false, 'type' => 'string' ),
            'provider' => array( 'visible' => false, 'type' => 'string' )
        ),
        'providers' => array(
            '/(?:youtu\.be\/|youtube.com\/(?:watch\?.*\bv=|embed\/|v\/)|ytimg\.com\/vi\/)(.+?)(?:[^_-a-zA-Z0-9]|$)/i' => 'youtube',
            '/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(.+?)(?:[^_-a-zA-Z0-9]|$)/i' => 'vimeo'
        )
    );
    
    public static function getEmbedCode($value, $width = 640, $height = 480)
    {
        if (!is_array($value)) return false;
        
        $provider = \Arr::get($value, 'provider', false);
        $video_id = \Arr::get($value, 'video_id', false);
        
        if ($provider === false || $video_id === false) return false;
        
        switch ($provider) {
            case 'youtube':
                
                return '<iframe width="'.$width.'" height="'.$height.'" src="//www.youtube.com/embed/'.$video_id.'?wmode=transparent" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen></iframe>';
                
            break;
            case 'vimeo':
                
                return '<iframe src="http://player.vimeo.com/video/'.$video_id.'" width="'.$width.'" height="'.$height.'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
                
            break;
        }
        
        return false;
    }
    
    // YOUTUBE:
    // http://www.youtube.com/watch?v=OSn6Go-sQ_4
    // http://youtu.be/OSn6Go-sQ_4
    // <iframe width="560" height="315" src="//www.youtube.com/embed/OSn6Go-sQ_4" frameborder="0" allowfullscreen></iframe>
    
    // VIMEO:
    // http://vimeo.com/70642037
    // <iframe src="http://player.vimeo.com/video/70642037" width="500" height="281" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
    
    /** @inheritdoc */
    public static function process($value, $settings, $model)
    {
        if (isset($value) && is_array($value)) {
            
            if (array_key_exists('url', $value)) {
                
                $settings = static::settings($settings);
                
                // Try and get info
                $url = \Arr::get($value, 'url', null);
                if (!isset($url) || empty($url)) return parent::process($value, $settings, $model);
                $info = static::getBasicVideoInfo($settings, $url);
                if (is_null($info)) return parent::process($value, $settings, $model);
                
                // If stuff has changed...
                $current_value = $model->get($settings['mapping']['fieldName']);
                if (!is_array($current_value)) $current_value = array();
                $current_provider = \Arr::get($current_value, 'provider', false);
                $current_video_id = \Arr::get($current_value, 'video_id', false);
                
                if ($info['provider'] != $current_provider || $info['video_id'] != $current_video_id) {
                    
                    // It has changed!
                    $value = \Arr::merge($value, $info);
                    
                    $video_id = \Arr::get($value, 'video_id', false);
                    $poster = \Arr::get($value, 'poster_image.src', false);
                    //print_r($value); exit();
                    
                    if ($video_id !== false && ($poster === false || empty($poster))) {
                        
                        $img_url = null;
                        
                        // Try and get hold of a poster image from the provider...
                        switch ($info['provider']) {
                            case 'youtube':
                                $img_url = 'http://img.youtube.com/vi/'.$info['video_id'].'/maxresdefault.jpg';
                            break;
                            case 'vimeo':
                                $video_info = json_decode(file_get_contents('http://vimeo.com/api/v2/video/'.$info['video_id'].'.json'));
                                if (is_array($video_info)) $video_info = $video_info[0];
                                $img_url = $video_info->thumbnail_large;
                            break;
                        }
                        
                        if (!is_null($img_url) && !empty($img_url)) {
                            
                            // Get hold of the image we've found
                            $img_content = file_get_contents($img_url);
                            $img_dir = DOCROOT.'uploads/video-thumbs';
                            $made_dir = @mkdir($img_dir, 0775, true);
                            
                            if (is_dir($img_dir)) {
                                
                                $img_path = $img_dir.'/'.$video_id.'-'.$info['provider'].'.jpg';
                                file_put_contents($img_path, $img_content);
                                $info = @getimagesize($img_path);
                                
                                if ($info !== false) {
                                    
                                    // If we can get the info, then we definitely have an image that's worth using.
                                    \Arr::set($value, 'poster_image.src', str_replace(DOCROOT, '', $img_path));
                                    \Arr::set($value, 'poster_image.width', $info[0]);
                                    \Arr::set($value, 'poster_image.height', $info[1]);
                                    
                                    // Now go through and update the crops
                                    $crop = \Arr::get($settings, 'fields.poster_image.crop', false);
                                    if ($crop !== false) {
                                        
                                        foreach ($crop as $crop_id => $crop_settings) {
                                            
                                            $cw = \Arr::get($crop_settings, 'width', 0);
                                            $ch = \Arr::get($crop_settings, 'height', 0);
                                            $crop_data = null;
                                            $ratio = $cw / $ch;
                                            
                                            if ($cw === 0 && $ch === 0) {
                                                $crop_data = array( 'x' => 0, 'y' => 0, 'width' => $info[0], 'height' => $info[1] );
                                            }
                                            
                                            if ($cw > 0 && $ch > 0) {
                                                $ccw = $info[0];
                                                $cch = round($ccw / $ratio);
                                                if ($cch > $info[1]) {
                                                    $cch = $info[1];
                                                    $ccw = round($cch * $ratio);
                                                }
                                                $crop_data = array( 'x' => round(($info[0]-$ccw)/2), 'y' => round(($info[1]-$cch)/2), 'width' => $ccw, 'height' => $cch );
                                            }
                                            
                                            if ($crop_data !== null) {
                                                \Arr::set($value, "poster_image.crop.$crop_id", $crop_data);
                                            }
                                            
                                        }
                                        
                                    }
                                    
                                }
                                
                            }
                            
                        }
                        
                    }
                                
                }
                
            }
            
        }
        
        return parent::process($value, $settings, $model);
    }
    
    /* inheritdoc */
    public static function validate($value, $settings, $model)
    {
        if (isset($value) && is_array($value)) {
            $settings = static::settings($settings);
            
            $url = \Arr::get($value, 'url', null);
            if (!isset($url) || empty($url)) return;
            $info = static::getBasicVideoInfo($settings, $url);
            
            if (is_null($info)) {
                $model->addErrorForField($settings['mapping']['fieldName'], 'That video URL is not recognised');
            }
            
        }
        
        //if (!is_null($value) && !is_int($value)) {
        //    $model->addErrorForField($settings['mapping']['fieldName'], 'This is not a valid number');
        //}
    }
    
    private static function getBasicVideoInfo($settings, $url)
    {
        $providers = \Arr::get($settings, 'providers');
        foreach ($providers as $regex => $provider) {
            preg_match($regex, $url, $matches);
            if (count($matches) > 1) {
                return array( 'provider' => $provider, 'video_id' => $matches[1] );
            }
        }
        return null;
    }
    
    public static function type($settings = array())
    {
        return 'videoembed';
    }
    
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        if (isset($value) && isset($value['url'])) {
            return \Html::anchor($edit_link, $value['url']);
        }
        return '-';
    }
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        
        $form = new ObjectForm($settings, $value);
        $form->getContent($model);
        
        return array(
            'content' => \View::forge('admin/fields/videoembed.twig', array( 'form' => $form, 'value' => $value ), false),
            'assets' => $form->assets,
            'merge_data' => true,
            'js_data' => $form->js_field_settings,
            'widget' => $settings['widget']
        );
    }
    
    /** inheritdoc */
    /*public static function getAssets()
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
    }*/
    
}