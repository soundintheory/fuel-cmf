<?php

namespace CMF\Field;

class Decimal extends Base {
    
    protected static $defaults = array(
        // None
    );
    
    /* inheritdoc */
    public static function process($value, $settings, $model)
    {
        //allow nullable decimals (blank string)
        if (trim($value) == '' && $settings['mapping']['nullable'])
        {
            return null;
        }

        if (is_numeric($value))
        {
            $output = @floatval($value);
            return ($output === false) ? $value : $output;
        }
        else
        {
            return $value;
        }
            
    }
    
    /* inheritdoc */
    public static function validate($value, $settings, $model)
    {
        //only allow blank if nullable
        if ($settings['mapping']['nullable'] && trim($value) == '')
        {
            //this is ok
        }
        else 
        {
            if (trim($value) != '' && is_numeric($value))
            {
                //this is fine 
            }
            else    
            {            
                $model->addErrorForField($settings['mapping']['fieldName'], 'This is not a valid number');
            }
        }
    }
	
}
