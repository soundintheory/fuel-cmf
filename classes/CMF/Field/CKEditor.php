<?php

namespace CMF\Field;

class CKEditor extends Textarea {
    
    protected static $defaults = array(
        'input_attributes' => array(
            'class' => 'input-xxlarge'
        ),
        'minHeight' => 300,
        'stylesSet' => '/assets/js/editor.js',
        'contentsCss' => '/assets/css/screen.min.css',
        'templatesFiles' => false,
        'editor' => array(
            'disableNativeSpellChecker' => false
        )
    );
    
    public function get_type()
    {
        return 'ckeditor';
    }
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                '/admin/assets/ckeditor/ckeditor.js',
                '/admin/assets/js/fields/ckeditor.js'
            )
        );
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        
        // Add ckeditor to the class for the field
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge' );
        $input_attributes['class'] = $input_attributes['class'] . " ckeditor-cmf";
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        $input = \Form::textarea($settings['mapping']['fieldName'], strval($value), $input_attributes);
        
        // Set up required information for any links specified
        if (isset($settings['links']) && is_array($settings['links'])) {
            $links = array();
            foreach ($settings['links'] as $link_type => $link) {
                if (!class_exists($link_type)) continue;
                $link['table_name'] = \CMF\Admin::getTableForClass($link_type);
                $link['singular'] = $link_type::singular();
                $link['plural'] = $link_type::plural();
                $link['icon'] = $link_type::icon();
                $links[$link_type] = $link;
            }
            $settings['links'] = $links;
        }

        if (isset($settings['stylesSet'])) {
            if (file_exists(DOCROOT.ltrim($settings['stylesSet'], '/'))) {
                $settings['stylesSet'] = 'default:'.\Uri::base(false).ltrim($settings['stylesSet'], '/');
            } else {
                unset($settings['stylesSet']);
            }
        }

        if (isset($settings['contentsCss'])) {
            if (strpos($settings['contentsCss'], '.php') === false && !file_exists(DOCROOT.ltrim($settings['contentsCss'], '/'))) {
                unset($settings['contentsCss']);
            }
        }
        
        // Return only the field and label if no wrap is required
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        // Return the widget
        if (isset($settings['widget']) && $settings['widget'] === true) {
            return array(
                'assets' => array(),
                'content' => $input,
                'widget' => true,
                'widget_title' => $settings['title'],
                'widget_icon' => 'align-left',
                'js_data' => $settings
            );
        }
        
        // Return the normal field
        return array(
            'assets' => array(),
            'content' => html_tag('div', array( 'class' => 'control-group '.($has_errors ? ' error' : '') ), $label.$input),
            'widget' => false,
            'js_data' => $settings
        );
    }
    
}