<?php

namespace CMF\Field;

class Measurement extends Select {
    
    protected static $defaults = array(
        'options' => array(),
        'default_unit' => 'cm',
        'output_units' => null,
        'display_units' => array(),
        'from' => 0,
        'to' => 30,
        'step' => 1,
        'extra_values' => array(),
        'allow_empty' => true,
        'rounding' => 0.5,
        'select2' => false
    );
    
    public static $conversions = array(
        
        // Millimetres
        'mm_inches' => 0.0393700787,
        'mm_m' => 0.001,
        'mm_feet' => 0.0032808399,
        'mm_cm' => 0.1,
        
        // Centimetres
        'cm_inches' => 0.393700787,
        'cm_mm' => 10,
        'cm_feet' => 0.032808399,
        'cm_m' => 0.01,
        
        // Metres
        'm_inches' => 39.3700787,
        'm_mm' => 1000,
        'm_feet' => 3.2808399,
        'm_cm' => 100,
        
        // Inches
        'inches_cm' => 2.54,
        'inches_mm' => 25.4,
        'inches_feet' => 0.08333333,
        'inches_m' => 0.0254,
        
        // Feet
        'feet_cm' => 30.48,
        'feet_mm' => 304.8,
        'feet_inches' => 12,
        'feet_m' => 0.3048
        
    );
    
    public static function convertUnit($value, $from, $to)
    {
        $conv_id = strtolower($from.'_'.$to);
        $factor = static::$conversions[$conv_id];
        return $value * $factor;
    }
    
    public static function formatUnit($unit, $value, $rounding = 0)
    {
        switch($unit) {
            case 'feet':
                $inches = $value * 12.0;
                if ($rounding > 0) $inches = round($inches * $rounding) / $rounding;
                $feet = floor($inches / 12);
                return strval($feet)."'".fmod($inches,12.0).'"';
            break;
            case 'inches':
                if ($rounding > 0) $value = round($value * $rounding) / $rounding;
                return strval($value).'"';
            break;
            default:
                if ($rounding > 0) $value = round($value * $rounding) / $rounding;
                return strval($value).$unit;
            break;
        }
    }
    
    public static function getValue($value, $settings, $model)
    {
        $default_unit = $settings['default_unit'];
        $rounding = 1 / $settings['rounding'];
        $output_units = ($settings['output_units'] !== null) ? $settings['output_units'] : array($default_unit);
        if (!is_array($output_units)) $output_units = array($output_units);
        
        $val = '';
        foreach ($output_units as $num => $unit) {
            if ($unit == $default_unit) {
                $val .= ($num > 0 ? ' / ' : '').static::formatUnit($unit, $value, $rounding);
            } else {
                $val .= ($num > 0 ? ' / ' : '').static::formatUnit($unit, static::convertUnit($value, $default_unit, $unit), $rounding);
            }
        }
        
        return $val;
    }
    
    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        
        $settings['options'] = array();
        $default_unit = $settings['default_unit'];
        $rounding = 1 / $settings['rounding'];
        $display_units = $settings['display_units'];
        if (empty($display_units)) $display_units = array($default_unit);
        
        for ($i=$settings['from']; $i <= $settings['to']; $i+=$settings['step']) { 
            $key = strval($i); // Force this to be treated as a string
            $val = '';
            
            foreach ($display_units as $num => $unit) {
                if ($unit == $default_unit) {
                    $val .= ($num > 0 ? ' / ' : '').static::formatUnit($unit, $i, $rounding);
                } else {
                    $val .= ($num > 0 ? ' / ' : '').static::formatUnit($unit, static::convertUnit($i, $default_unit, $unit), $rounding);
                }
            }
            
            $settings['options'][$key] = $val;
        }
        
        return parent::displayForm($value, $settings, $model);
    }

    public static function getTranslatableAttributes()
    {
        return array_merge(parent::getTranslatableAttributes(), array(
            'default_unit'
        ));
    }
	
}