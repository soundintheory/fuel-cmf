<?php

namespace CMF\Field;

use Twig_Autoloader,
    Twig_Environment,
    Twig_Loader_String;

class Base {
    
    public static $always_process = false;
    
    protected static $defaults = array(
        'input_attributes' => array(
            'class' => 'input-xxlarge'
        )
    );
    
    /**
     * When a processed version of this field is requested, it will be run through this method first.
     * Useful for conversions between database type and 'front end' type.
     * @return mixed
     */
    public static function getValue($value, $settings, $model)
    {
        return $value;
    }
    
    /**
     * Prepares the field's value for displaying in an item's list view.
     * @see \Admin::getFieldSettings()
     * @param mixed $value
     * @param string $edit_link The address of the edit page for the item
     * @param string &$settings Field settings, created through \Admin::getFieldSettings()
     * @param object &$model
     * @return string The HTML to insert into the table cell
     */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        try {
            return '<a href="'.$edit_link.'" class="item-link">'.strval($value).'</a>';
        } catch (\Exception $e) {
            return "Error: unkown type";
        }
    }
    
    /**
     * Renders the field's form element for editing in the admin site
     * @see \Admin::getFieldSettings()
     * @param mixed $value The current value of the property, if there is one
     * @param array $settings Field settings, created through \Admin::getFieldSettings()
     * @param object $model The model, if it is being edited.
     * @return string The form control
     */
    public static function displayForm($value, &$settings, $model)
    {
        $class = get_called_class();
        $settings = static::settings($settings);
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge' );
        if (!isset($input_attributes['id'])) $input_attributes['id'] = 'form_'.$settings['mapping']['fieldName'];
        $attributes = array( 'class' => 'controls control-group'.($has_errors ? ' error' : '').' field-type-'.$class::type($settings) );
        $input = '<input type="text" name="'.$settings['mapping']['fieldName'].'" '.array_to_attr($input_attributes).' value="'.\Security::htmlentities(strval($value), ENT_QUOTES).'" />';
        //$input = \Form::input($settings['mapping']['fieldName'], strval($value), $input_attributes);
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        
        // Translation?
        if (\CMF::$lang_enabled) {
            
            // If there is no translation
            if (!$model->hasTranslation($settings['mapping']['fieldName'])) {
                $attributes['class'] .= ' no-translation';
            }
            
        }
        
        // Prepend or append things...
        if (isset($settings['prepend'])) {
            $input = html_tag('div', array( 'class' => 'input-prepend' ), html_tag('span', array( 'class' => 'add-on' ), $settings['prepend']).$input);
        }
        if (isset($settings['append'])) {
            $input = html_tag('div', array( 'class' => 'input-append' ), $input.html_tag('span', array( 'class' => 'add-on' ), $settings['append']));
        }
        
        // Don't wrap the input if wrap is set to false
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        // Add the 'keep updated' control if the field has a template
        if (isset($settings['template']) && !empty($settings['template'])) {
            
            $attributes['class'] .= ' field-with-controls field-with-template';
            $auto_update_setting = 'settings['.$settings['mapping']['fieldName'].'][auto_update]';
            $auto_update_content = \Form::hidden($auto_update_setting, '0', array()).html_tag('label', array( 'class' => 'checkbox auto-update-label' ), \Form::checkbox($auto_update_setting, '1', \Arr::get($settings, 'auto_update', true), array( 'class' => 'auto-update' )).' auto update');
            $auto_update = html_tag('div', array( 'class' => 'controls-top' ), $auto_update_content);
            $label .= $auto_update;
            
            return array(
                'content' => html_tag('div', $attributes, $label.$input).'<div class="clear"><!-- --></div>',
                'widget' => false,
                'assets' => array( 'js' => array('/admin/assets/js/twig.min.js', '/admin/assets/js/fields/template.js') ),
                'js_data' => $settings
            );
            
        }
        
        return html_tag('div', $attributes, $label.$input);
    }
    
    /**
     * Renders a form element to filter by at the top of list pages
     * @param  $value
     * @return mixed
     */
    public static function displayFilter($value)
    {
        return false;
    }
    
    /**
     * Processes the value of this field before getting set to the model in the populate() method
     * @see \CMF\Model\Base::populate()
     * @param mixed $value The value about to be set
     * @param array $settings The settings for this field
     * @param object $model The model
     * @return mixed The value ready for setting on the model
     */
    public static function process($value, $settings, $model)
    {
        if (!(isset($settings['auto_update']) && !$settings['auto_update']) && 
            (isset($settings['template']) && !empty($settings['template']))) {
            
            $post_data = \Input::post();
            $context = \Arr::merge($model->toArray(), $post_data);
            
            $twig = \View_Twig::parser();
            $loader = \View_Twig::loader();
            if (!isset($loader) || is_null($loader)) {
                $loader = new Twig_Loader_String();
                $twig->setLoader($loader);
                \View_Twig::setLoader($loader);
            }
            return $twig->render($settings['template'], $context);
            
        }
        
        return $value;
    }
    
    /**
     * Validates the this field in the model's validate() method
     * @see \CMF\Model\Base::validate()
     * @param mixed $value The value to be validated
     * @param array $settings The settings for this field
     * @param object $model The model
     * @return void
     */
    public static function validate($value, $settings, $model)
    {
        // Nothing
    }
    
    /**
     * Returns an array of static assets to append to the head 
     * @return array
     */
    public static function getAssets()
    {
        return null;
    }
    
    /**
     * Given the user defined settings, returns a copy merged with the field's defaults
     * @see \CMF\Field\Base::$defaults
     * @param array $user_settings The user defined settings for the field
     * @return array
     */
    public static function settings($user_settings)
    {
        $called_class = get_called_class();
        return \Arr::merge($called_class::$defaults, $user_settings);
    }
    
    /**
     * A string stating the field's type
     * @return string
     */
    public static function type($settings = array())
    {
        return \Inflector::friendly_title(str_replace('CMF\\Field', '', get_called_class()), '-', true);
    }
	
}