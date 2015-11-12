<?php

namespace CMF\Field\Object;

class BingMap extends Object {
	
	protected static $options = null;
    
    protected static $defaults = array(
        'dynamic' => false,
        'array' => false,
        'tabular' => false,
        'widget' => false,
        'widget_icon' => 'globe',
        'sub_group' => true,
        'marker' => true,
        'api_key' => null,
        'api_key_setting' => 'bing_maps_api_key',
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
        return null;
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
        $searchInput = \Form::input($settings['mapping']['fieldName'].'[search]', null, array( 'class' => 'input input-xxlarge search-input', 'placeholder' => 'Search by address, postcode or coordinates' ));
        $searchButton = \Form::button('mapsearch', __('admin.common.search'), array( 'class' => 'btn btn-primary' ));
        $searchInput = html_tag('div', array( 'class' => 'form form-inline search-form' ), $searchInput.$searchButton);

        // Hidden inputs
        $latInput = \Form::hidden($settings['mapping']['fieldName'].'[lat]', \Arr::get($value, 'lat'), array( 'class' => 'lat' ));
        $lngInput = \Form::hidden($settings['mapping']['fieldName'].'[lng]', \Arr::get($value, 'lng'), array( 'class' => 'lng' ));
        $zoomInput = \Form::hidden($settings['mapping']['fieldName'].'[zoom]', \Arr::get($value, 'zoom'), array( 'class' => 'zoom' ));

        // Other elements
        $required = isset($settings['required']) ? $settings['required'] : false;
        $label_text = $settings['title'].($required ? ' *' : '');
        $label = \Form::label($label_text);
        $mapDiv = html_tag('div', array( 'class' => 'map', 'id' => \Inflector::friendly_title($settings['mapping']['fieldName'], '-', true).'-bing-map' ), ' ');

        // Check that we have an API key
        if (empty($settings['api_key'])) {
            $content = $label.'<div class="well"><p>'.__('admin.bing.api_key_not_set').'</p></div>';
        } else {
            $content = $label.$searchInput.$latInput.$lngInput.$zoomInput.$mapDiv;
        }

        $content = html_tag('div', array( 'class' => 'controls control-group field-type-bing-map', 'data-field-name' => $settings['mapping']['fieldName'] ), $content);

        return array(
            'content' => $content,
            'js_data' => $settings
        );
    }

    /** inheritdoc */
    public static function settings($user_settings)
    {
        $settings = parent::settings($user_settings);

        // Populate the Bing API key if not already present
        $api_key = \Arr::get($settings, 'api_key', false);
        if (empty($api_key)) {
            $settings['api_key'] = \CMF::getSetting(\Arr::get($settings, 'api_key_setting', 'bing_maps_api_key'));
        }

        return $settings;
    }

    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                'https://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0&s=1&mkt=en-US',
                '/admin/assets/js/fields/bingmap.js'
            )
        );
    }

    /** inheritdoc */
    public static function type($settings = array())
    {
        return 'bing-map';
    }
	
}