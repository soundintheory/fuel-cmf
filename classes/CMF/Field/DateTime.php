<?php

namespace CMF\Field;

class DateTime extends Date {

    protected static $defaults = array(
        'default' => null,
        'default_offset' => null,
        'format' => 'd/m/Y H:i',
        'list_format' => 'M jS H:i'
    );

    public static function process($value, $settings, $model)
    {
        $settings = static::settings($settings);
        if (!($value instanceof \DateTime)) $value = \DateTime::createFromFormat(\Arr::get($settings, 'format', 'd/m/Y H:i'), $value);
        if ($value === false) $value = new \DateTime();
        return $value;
    }

    /** @inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return '<a href="'.$edit_link.'" class="item-link">'.$value->format(\Arr::get($settings, 'list_format', 'M jS H:i')).'</a>';
    }

    /** @inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $settings = static::settings($settings);
        if (!isset($value) || !$value) {
            $value = (!is_null($settings['default'])) ? \DateTime::createFromFormat($settings['format'], $settings['default']) : new \DateTime();
            if (!is_null($settings['default_offset'])) {
                $value->modify($settings['default_offset']);
            }
        }

        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = isset($settings['input_attributes']) ? $settings['input_attributes'] : array( 'class' => 'input-large' );
        if(!empty($settings['format']))
        {
            $dateFormat = explode(" ", $settings['format']);
            $input_attributes["data-options"] = "{ 'dateFormat': '" . self::dateformat_PHP_to_jQueryUI(@$dateFormat[0]) . "','timeFormat': '" . self::dateformat_PHP_to_jQueryUI(@$dateFormat[1]) . "' }";
        }
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName'], array( 'class' => 'item-label' ));
        $input = \Form::input($settings['mapping']['fieldName'], $value->format($settings['format']), $input_attributes);
        $input = $input = html_tag('div', array( 'class' => 'input-prepend' ), html_tag('span', array( 'class' => 'add-on' ), '<i class="fa fa-calendar"></i>').$input);

        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;

        return html_tag('div', array( 'class' => 'controls control-group field-type-datetime'.($has_errors ? ' error' : '')), $label.$input);
    }

    public static function getTranslatableAttributes()
    {
        return array_merge(parent::getTranslatableAttributes(), array(
            'list_format'
        ));
    }

    public static function dateformat_PHP_to_jQueryUI($php_format)
    {
        $SYMBOLS_MATCHING = array(
            // Day
            'd' => 'dd',
            'D' => 'D',
            'j' => 'd',
            'l' => 'DD',
            'N' => '',
            'S' => '',
            'w' => '',
            'z' => 'o',
            // Week
            'W' => '',
            // Month
            'F' => 'MM',
            'm' => 'mm',
            'M' => 'M',
            'n' => 'm',
            't' => '',
            // Year
            'L' => '',
            'o' => '',
            'Y' => 'yy',
            'y' => 'y',
            // Time
            'a' => 'tt',
            'A' => 'TT',
            'B' => '',
            'g' => 'h',
            'G' => 'H',
            'h' => 'hh',
            'H' => 'hh',
            'i' => 'mm',
            's' => 'ss',
            'u' => ''
        );
        $jqueryui_format = "";
        $escaping = false;
        for($i = 0; $i < strlen($php_format); $i++)
        {
            $char = $php_format[$i];
            if($char === '\\') // PHP date format escaping character
            {
                $i++;
                if($escaping) $jqueryui_format .= $php_format[$i];
                else $jqueryui_format .= '\'' . $php_format[$i];
                $escaping = true;
            }
            else
            {
                if($escaping) { $jqueryui_format .= "'"; $escaping = false; }
                if(isset($SYMBOLS_MATCHING[$char]))
                    $jqueryui_format .= $SYMBOLS_MATCHING[$char];
                else
                    $jqueryui_format .= $char;
            }
        }
        return $jqueryui_format;
    }

}