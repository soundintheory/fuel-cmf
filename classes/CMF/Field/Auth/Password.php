<?php

namespace CMF\Field\Auth;

class Password extends \CMF\Field\Base {
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-xxlarge' );
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors && !empty($errors[0]) ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName']);
        $input = \Form::password($settings['mapping']['fieldName'], strval($value), $input_attributes);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        
        return html_tag('div', array( 'class' => 'controls control-group'.($has_errors ? ' error' : '') ), $label.$input);
    }

	/** inheritdoc */
    public static function validate($value, $settings, $model)
    {
        if(strpos($settings['mapping']['fieldName'], 'confirm_') === 0 || empty($value)) return;

        $confirm_password = 'confirm_'.$settings['mapping']['fieldName'];
        $cpv = $model->get($confirm_password);

        if($value != $cpv){
            $model->addErrorForField($settings['mapping']['fieldName'], '');
            $model->addErrorForField($confirm_password, \Lang::get('admin.errors.account.password_mismatch'));
        }
    }
    
}