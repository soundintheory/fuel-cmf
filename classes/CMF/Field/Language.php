<?php

namespace CMF\Field;

class Language extends Base {
    
    protected static $defaults = array(
        'allow_empty' => true,
        'active_only' => false
    );
    
    /** @inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        if (empty($value) || is_null($value) || strlen($value) === 0) return '<a href="'.$edit_link.'" class="item-link">(none)</a>';
        return '<a href="'.$edit_link.'" class="item-link"><img src="/admin/assets/img/lang/'.$value.'.png" style="width:24px;height:24px;" />&nbsp; '.\Lang::get("languages.$value").'</a>';
    }
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        // No point in ever showing this field if lang isn't enabled
        if (!\CMF::$lang_enabled) return '';
        
        \Lang::load('languages', true, 'en', true, true);
        $settings = static::settings($settings);
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge' );
        
        if ($settings['active_only']) {
            
            $options = array_map(function($lang) {
                return \Arr::get(\Lang::$lines, 'en.languages.'.$lang['code'], __('admin.errors.language.name_not_found'));
            }, \CMF\Model\Language::select('item.code', 'item', 'item.code')->orderBy('item.pos', 'ASC')->where('item.visible = true')->getQuery()->getArrayResult());
            
        } else {
            $options = \Arr::get(\Lang::$lines, 'en.languages', array());
        }
        
        // Whether to allow an empty option
        if (isset($settings['mapping']['nullable']) && $settings['mapping']['nullable'] && !$required && $settings['allow_empty']) {
            $options = array( '' => '' ) + $options;
        }
        
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        $input = \Form::select($settings['mapping']['fieldName'], $value, $options, $input_attributes);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', array( 'class' => 'controls control-group'.($has_errors ? ' error' : '') ), $label.$input);
    }
	
}