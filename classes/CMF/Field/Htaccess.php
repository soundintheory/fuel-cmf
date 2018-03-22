<?php

namespace CMF\Field;

use CMF\Admin\ModelForm;

class Htaccess extends Textarea {

    const BEGIN_TOKEN = "### BEGIN CMF RULES";
    const END_TOKEN = "### END CMF RULES";
    const MSG = "(do not edit!!)";
    const ARG_SEPARATOR = "\t";
    const NEWLINE = "\r\n";
    
    protected static $defaults = array(
        'disallowed_prefixes' => array(
            '',
            '/',
            '',
            '/image',
        )
    );
    protected static $errors = array();
    
    public function get_type()
    {
        return 'htaccess';
    }
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array_merge(
            \CMF\Field\Object\Link::getAssets(),
            array(
                'js' => array(
                    '/assets/js/fields/htaccess.js'
                )
            )
        );
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $name = $settings['mapping']['fieldName'];
        $fields = array(
            'from' => array( 'field_type' => 'text' ),
            'to' => array( 'field_type' => 'text' ),
            'action' => array( 'field_type' => 'select' )
        );
        $fromAttributes = array(
            'class' => 'input-xxlarge',
            'data-name' => "__TEMP__{$name}[__NUM__][from]"
        );
        $toAttributes = array(
            'class' => 'input-xxlarge',
            'data-name' => "__TEMP__{$name}[__NUM__][to]"
        );

        // From / to text inputs
        $fields['from']['display'] = \Form::input("__TEMP__{$name}[__NUM__][from]", null, $fromAttributes);
        $fields['to']['display'] = \Form::input("__TEMP__{$name}[__NUM__][to]", null, $toAttributes);

        // Action dropdown
        $actionOptions = array( '301' => '301 (permanent)', '302' => '302 (temporary)' );
        $actionAttributes = array( 'class' => 'input-xxlarge', 'data-name' => "__TEMP__{$name}[__NUM__][action]" );
        $fields['action']['display'] = \Form::select("__TEMP__{$name}[__NUM__][action]", null, $actionOptions, $actionAttributes);

        // Parsing the rows
        $rows = array();

        foreach (static::parseHtaccessRedirects($value) as $key => $value)
        {
            $rows[$key] = array(
                'fields' => array(
                    'from' => array(),
                    'to' => array(),
                    'action' => array()
                )
            );

            $rows[$key]['fields']['from']['display'] = \Form::input("{$name}[{$key}][from]", $value['from'], $fromAttributes);
            $rows[$key]['fields']['to']['display'] = \Form::input("{$name}[{$key}][to]", $value['to'], $toAttributes);
            $rows[$key]['fields']['action']['display'] = \Form::select("{$name}[{$key}][action]", $value['action'], $actionOptions, $actionAttributes);
        }

        return array(
            'assets' => static::getAssets(),
            'content' => strval(\View::forge('admin/fields/htaccess.twig', array(
                'settings' => $settings,
                'singular' => 'rule',
                'plural' => 'rules',
                'rows' => $rows,
                'cols' =>  array('from', 'to', 'action'),
                'fields' => $fields,
                'errors' => !empty($model) ? $model->getErrorsForField($name) : array()
            ), false)),
            'widget' => true,
            'widget_class' => ''
        );
    }

    public static function process($value, $settings, $model)
    {
        $settings = static::settings($settings);
        $disallowed = \Arr::get($settings, 'disallowed_prefixes');
        static::$errors = array();
        $rules = "";
        /*$extraRules = $value['extrarules'];
        unset($value['extrarules']);*/

        if (empty($value)) {
            return '';
        }

        foreach ($value as $val)
        {
            if (!is_array($val)) continue;

            $action = @$val['action'] ?: 301;
            $rules .= implode(static::ARG_SEPARATOR, array('RewriteRule', $val['from'], $val['to'], "[R=$action,L]\r\n"));

            if (!($fromValid = static::isValidRewriteArgument($val['from']))) {
                static::$errors[] = "An error was found in the syntax of '{$val['from']}'";
            }
            if (!($toValid = static::isValidRewriteArgument($val['to']))) {
                static::$errors[] = "An error was found in the syntax of '{$val['to']}'";
            }
            if ($fromValid && is_array($disallowed)) {
                foreach ($disallowed as $disallow) {
                    if (static::matchesPrefix($disallow, $val['from'])) {
                        static::$errors[] = "Cannot add a rule that matches '$disallow'";
                        break;
                    }
                }
            }
        }

        // Only insert if all the syntax is valid
        if (!count(static::$errors)) {
            self::insertToHtaccess($rules);
        }

        return $rules;
    }

    /**
     * Logs any errors added during processing
     */
    public static function validate($value, $settings, $model)
    {
        foreach (static::$errors as $msg) {
            $model->addErrorForField($settings['mapping']['fieldName'], $msg);
        }
    }

    private static function parseHtaccessRedirects($input)
    {
        $lines = explode("\r\n", $input ?: '');
        $output = array();

        foreach ($lines as $key => $value)
        {
            $parts = array_map('trim', explode(static::ARG_SEPARATOR, $value));
            if ($parts[0] != 'RewriteRule') continue;

            preg_match('/\[R=(\d+)/', $value, $action);
            $output[] = array(
                'from' => $parts[1],
                'to' => $parts[2],
                'action' => @$action[1] ?: '301'
            );
        }

        return $output;
    }

    private static function matchesPrefix($prefix, $pattern)
    {
        $prefix = rtrim($prefix, '/');
        $regex = '~'.str_replace("~", "\~", $pattern).'~';
        $segments = 10;

        if (!empty($prefix) && preg_match('~'.str_replace("~", "\~", $prefix).'/.*~', $pattern)) {
            return true;
        }

        if (!empty($prefix) && preg_match('~'.str_replace("~", "\~", trim($prefix, '/')).'/.*~', $pattern)) {
            return true;
        }

        for ($i=0; $i < $segments; $i++) { 
            if (preg_match($regex, $prefix.str_repeat('/segment', $i))) {
                return true;
            }
        }

        return false;
    }

    private static function isValidRewriteArgument($value)
    {
        if (empty(trim($value))) {
            return false;
        }
        if (preg_match('/\s/', $value)) {
            return false;
        }
        if (@preg_match('~'.str_replace("~", "\~", $value).'~', null) === false) {
            return false;
        }
        return true;
    }

    private static function insertToHtaccess($text)
    {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . DS .'.htaccess';

        if (!file_exists($filePath))
            \File::copy(CMFPATH.'templates/.htaccess', $_SERVER['DOCUMENT_ROOT']);

        $parts = self::getHtaccessParts($filePath);

        file_put_contents($filePath,implode("",$parts['before']).$text.implode("",$parts['after']));
    }

    private static function getHtaccessParts($filePath)
    {
        $lines = file($filePath);
        $parts = array();
        $firstPartEnd = 0;

        for ($x = 0; $x < count($lines); $x++)
        {
            if (strpos($lines[$x], static::BEGIN_TOKEN) !== false) {
                $parts['before'] = array_slice($lines, 0, $x);
                $firstPartEnd = $x;
            }
            if (strpos($lines[$x], static::END_TOKEN) !== false) {
                $parts['after'] = count($lines) > ($x + 2) ? array_slice($lines, $x + 2) : (count($lines) > ($x + 1) ? array_slice($lines, $x + 1) : array());
                break;
            }
        }

        if (!isset($parts['before']) && !isset($parts['after'])) {
            $parts['before'] = array();
            $parts['after'] = $lines;
        }

        array_push($parts['before'], static::BEGIN_TOKEN.' '.static::MSG.static::NEWLINE.'RewriteEngine on'.static::NEWLINE);
        array_unshift($parts['after'], static::END_TOKEN.' '.static::MSG.static::NEWLINE.static::NEWLINE);

        return $parts;
    }
    
}