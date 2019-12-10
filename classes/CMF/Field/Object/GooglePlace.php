<?php

namespace CMF\Field\Object;

class GooglePlace extends \CMF\Field\Base
{
    public static function getAssets()
    {
        $keyQueryString = "";
        $api_key = \Config::get('google_maps_api_key');
        if(!empty($api_key))
            $keyQueryString = "&key=".$api_key;
        return array(
            'js' => array(
                'https://maps.googleapis.com/maps/api/js?libraries=places'.$keyQueryString,
                '/admin/assets/js/fields/googleplace.js',
            )
        );
    }

    public static function type($settings = array())
    {
        return 'google-place';
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
        $input_attributes['class'] .= ' form_'.$settings['mapping']['fieldName'];
        $attributes = array( 'class' => 'controls control-group'.($has_errors ? ' error' : '').' field-type-'.$class::type($settings) );
        $label_text = $settings['title'].($required ? ' *' : '');

        // Translation?
        if (\CMF::$lang_enabled && !\CMF::langIsDefault() && isset($settings['mapping']['columnName']) &&  $model->isTranslatable($settings['mapping']['columnName'])) {

            // If there is no translation
            if (!$model->hasTranslation($settings['mapping']['columnName'])) {
                $attributes['class'] .= ' no-translation';
                $input_attributes['class'] .= ' no-translation';
                $label_text = '<img class="lang-flag" src="/admin/assets/img/lang/'.\CMF::defaultLang().'.png" />&nbsp; '.$label_text;
            } else {
                $label_text = '<img class="lang-flag" src="/admin/assets/img/lang/'.\CMF::lang().'.png" />&nbsp; '.$label_text;
            }

        }

        // Description?
        $description = isset($settings['description']) ? '<span class="help-block">'.$settings['description'].'</span>' : '';

        // Build the input
        $input = '<input type="text" name="'.$settings['mapping']['fieldName'].'[place_name]" '.array_to_attr($input_attributes).' value="'.\Security::htmlentities(strval($value['place_name']), ENT_QUOTES).'" />';
        $input .= '<input type="hidden" data-ref="place-id" name="'.$settings['mapping']['fieldName'].'[place_id]" '.array_to_attr($input_attributes).' value="'.\Security::htmlentities(strval($value['place_id']), ENT_QUOTES).'" />';
        $input .= '<input type="hidden" data-ref="address_components" name="'.$settings['mapping']['fieldName'].'[address_components]" '.array_to_attr($input_attributes).' value="'.\Security::htmlentities(strval($value['address_components']), ENT_QUOTES).'" />';

        // Build the label
        $label = (!$include_label) ? '' : html_tag('label', array( 'class' => 'item-label', 'for' => $settings['mapping']['fieldName'] ), $label_text.($has_errors ? ' - '.$errors[0] : ''));

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
            $auto_update_content = \Form::hidden($auto_update_setting, '0', array()).html_tag('label', array( 'class' => 'checkbox auto-update-label' ), \Form::checkbox($auto_update_setting, '1', \Arr::get($settings, 'auto_update', true), array( 'class' => 'auto-update' )).strtolower(\Lang::get('admin.common.auto_update')));
            $auto_update = html_tag('div', array( 'class' => 'controls-top' ), $auto_update_content);
            $label .= $auto_update;

            return array(
                'content' => html_tag('div', $attributes, $label.$description.$input).'<div class="clear"><!-- --></div>',
                'widget' => false,
                'assets' => array( 'js' => array('/admin/assets/js/twig.min.js', '/admin/assets/js/fields/template.js') ),
                'js_data' => $settings
            );

        }

        return html_tag('div', $attributes, $label.$description.$input);
    }
}
