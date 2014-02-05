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
        'fields' => array(
            'lat' => array( 'type' => 'float' ),
            'lng' => array( 'type' => 'float' ),
            'zoom' => array( 'type' => 'float' ),
            'search' => array( 'type' => 'string' )
        )
    );
    
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
        $searchButton = \Form::button(null, 'Search', array( 'class' => 'btn btn-primary' ));
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