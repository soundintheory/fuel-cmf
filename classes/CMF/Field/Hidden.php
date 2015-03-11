<?php

namespace CMF\Field;

class Hidden extends Base {
	
	public static function displayForm($value, &$settings, $model)
	{
		$class = get_called_class();
	    $settings = static::settings($settings);

		return '<input type="hidden" name="'.$settings['mapping']['fieldName'].'" value="'.\Security::htmlentities(strval($value), ENT_QUOTES).'" />';
	}
	
}