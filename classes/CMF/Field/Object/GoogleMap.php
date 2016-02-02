<?php

namespace CMF\Field\Object;

class GoogleMap extends Object {
	
	protected static $options = null;
    
    protected static $defaults = array(
        'dynamic' => false,
        'array' => false,
        'tabular' => false,
        'widget' => false,
        'widget_icon' => 'globe',
        'sub_group' => true,
        'marker' => true,
        'initial' => array(
            'lat' => 54.44340598200656,
            'lng' => -3.063812255859375,
            'zoom' => 5
        ),
        'fields' => array(
            'lat' => array( 'type' => 'float' ),
            'lng' => array( 'type' => 'float' ),
            'zoom' => array( 'type' => 'float' ),
            'search' => array( 'type' => 'string' )
        )
    );

    /**
     * Get a map image from the Google Maps static image API
     */
    public static function getMapImage($options, $force = false)
    {
        if ($is_single = \Arr::is_assoc($options)) {
            $options = array($options);
        }

        // Set up the dir for storing
        $dir = DOCROOT.'uploads/maps';
        $dir_made = is_dir($dir) ? true : @mkdir($dir, 0775, true);
        if (!$dir_made) throw new \Exception("The map upload directory could not be found or created!");

        foreach ($options as $num => $settings) {

            // Allow offset to be applied
            $olat = \Arr::get($settings, 'lat', 54.443);
            $olng = \Arr::get($settings, 'lng', -3.063);

            $lat = $olat + \Arr::get($settings, 'latOffset', 0);
            $lng = $olng + \Arr::get($settings, 'lngOffset', 0);

            $width = \Arr::get($settings, 'width', 200);
            $height = \Arr::get($settings, 'height', 200);
            $scale = \Arr::get($settings, 'scale', 1);
            $marker = \Arr::get($settings, 'marker', false);
            $markerColor = \Arr::get($settings, 'markerColor', 'red');

            // Build the API url
            $url = 'http://maps.googleapis.com/maps/api/staticmap'
            .'?center='.$lat.','.$lng
            .($marker ? '&markers=color:'.$markerColor.'|'.$olat.','.$olng : '')
            .'&zoom='.\Arr::get($settings, 'zoom', 11)
            .'&size='.$width.'x'.$height
            .'&scale='.$scale
            .'&sensor=false';

            // Download the image
            $path = $dir.'/'.md5($url).'.png';
            if (!file_exists($path) || $force) {
                $img = file_get_contents($url);
                @file_put_contents($path, $img);
            }

            // Set info back to the options
            $options[$num]['src'] = str_replace(DOCROOT, '', $path);
            $options[$num]['width'] = $width * $scale;
            $options[$num]['height'] = $height * $scale;
        }

        return $is_single ? $options[0] : $options;
    }
    
    /** @inheritdoc */
    public static function process($value, $settings, $model)
    {
        return parent::process($value, $settings, $model);
    }

    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        if (!is_array($value)) $value = array();

        // Search input or
        $searchInput = \Form::input($settings['mapping']['fieldName'].'[search]', null, array( 'class' => 'input input-xxlarge search-input', 'placeholder' => \Lang::get('admin.common.map_search_placeholder') ));
        $searchButton = \Form::button('mapsearch', 'Search', array( 'class' => 'btn btn-primary' ));
        $searchInput = html_tag('div', array( 'class' => 'form form-inline search-form' ), $searchInput.$searchButton);

        // Hidden inputs
        $latInput = \Form::hidden($settings['mapping']['fieldName'].'[lat]', \Arr::get($value, 'lat'), array( 'class' => 'lat' ));
        $lngInput = \Form::hidden($settings['mapping']['fieldName'].'[lng]', \Arr::get($value, 'lng'), array( 'class' => 'lng' ));
        $zoomInput = \Form::hidden($settings['mapping']['fieldName'].'[zoom]', \Arr::get($value, 'zoom'), array( 'class' => 'zoom' ));

        // Other elements
        $required = isset($settings['required']) ? $settings['required'] : false;
        $label_text = $settings['title'].($required ? ' *' : '');
        $label = \Form::label($label_text);
        $mapDiv = html_tag('div', array( 'class' => 'map', 'id' => \Inflector::friendly_title($settings['mapping']['fieldName'], '-', true).'-google-map' ), ' ');

        $content = html_tag('div', array( 'class' => 'controls control-group field-type-google-map', 'data-field-name' => $settings['mapping']['fieldName'] ), $label.$searchInput.$latInput.$lngInput.$zoomInput.$mapDiv);

        return array(
            'content' => $content,
            'js_data' => $settings
        );
    }

    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false',
                '/admin/assets/js/fields/googlemap.js'
            )
        );
    }

    /** inheritdoc */
    public static function type($settings = array())
    {
        return 'google-map';
    }
	
}