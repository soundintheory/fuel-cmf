<?php

namespace CMF\Field;

class Redactor extends Textarea {
    
    protected static $defaults = array(
        'input_attributes' => array(
            'class' => 'input-xxlarge'
        ),
        'minHeight' => 300
    );
    
    public function get_type()
    {
        return 'redactor';
    }
    
	/** inheritdoc */
    public static function getAssets()
    {
		return array(
			'js' => array(
				'/admin/assets/redactor/redactor.js',
				'/admin/assets/js/fields/redactor.js'
			),
			'css' => array(
				'/admin/assets/redactor/redactor.css'
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
        
        $attributes = array( 'class' => 'controls control-group '.($has_errors ? ' error' : '') );
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge' );
        //add redactor to the class for the field
        $input_attributes['class'] = $input_attributes['class'] . " redactor";
        $label_text = $settings['title'].($required ? ' *' : '');
        $input = \Form::textarea($settings['mapping']['fieldName'], strval($value), $input_attributes);
        
        // Translation?
        if (\CMF::$lang_enabled && !\CMF::langIsDefault() && $model->isTranslatable($settings['mapping']['columnName'])) {
            
            // If there is no translation
            if (!$model->hasTranslation($settings['mapping']['columnName'])) {
                $attributes['class'] .= ' no-translation';
                $label_text = '<span class="no-translation"><img class="lang-flag" src="/admin/assets/img/lang/'.\CMF::defaultLang().'.png" />&nbsp; '.$label_text.'</span>';
            } else {
                $label_text = '<img class="lang-flag" src="/admin/assets/img/lang/'.\CMF::lang().'.png" />&nbsp; '.$label_text;
            }
            
        }
        
        // Build the label
        $label = (!$include_label) ? '' : \Form::label($label_text.($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        
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
        
        // Return only the field and label if no wrap is required
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        // Return the widget
        if (isset($settings['widget']) && $settings['widget'] === true) {
            return array(
                'assets' => array(),
                'content' => $input,
                'widget' => true,
                'widget_title' => $label_text,
                'widget_icon' => 'align-left',
                'js_data' => $settings
            );
        }
        
        // Return the normal field
        return array(
            'assets' => array(),
            'content' => html_tag('div', $attributes, $label.$input),
            'widget' => false,
            'js_data' => $settings
        );
    }
	
}