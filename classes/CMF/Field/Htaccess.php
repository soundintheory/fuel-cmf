<?php

namespace CMF\Field;

use CMF\Admin\ModelForm;

class Htaccess extends Textarea {
    
    protected static $defaults = array(

    );
    
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
                    '/admin/assets/js/fields/htaccess.js'
                )
            )
        );
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $fields = array();
        $fields['from'] = array();
        $fields['to'] = array();
        $fields['action'] = array();
        $fields['from']['field_type'] = $fields['to']['field_type'] = "link";
        $fromSettings = $settings;
        $fromSettings['mapping']['fieldName'] = '__TEMP__'.$settings['mapping']['fieldName'].'[__NUM__][from]';
        $toSettings = $settings;
        $toSettings['mapping']['fieldName'] = '__TEMP__'.$settings['mapping']['fieldName'].'[__NUM__][to]';
        $fields['from']['display'] = \CMF\Field\Text::displayForm(null,$fromSettings, $model);
        $fields['to']['display'] = \CMF\Field\Text::displayForm(null,$toSettings, $model);
        $selectSettings = $settings;
        $selectSettings['title'] = 'Action';
        $selectSettings['options'] = array('301'=>'Permanent Redirect(301)','302'=>'Temporary Redirect(302)');
        $selectSettings['mapping']['fieldName'] = '__TEMP__'.$settings['mapping']['fieldName'].'[__NUM__][action]';
        $fields['action']['display']  = \CMF\Field\Select::displayForm(null,$selectSettings, $model);

        $rows = array();
        $values = explode("\r\n",$value);
        foreach ($values as $key=>$val){
            if(self::startsWith($val,"Redirect")) {
                $rows[$key] = array();
                $rows[$key]['fields'] = array();
                $rows[$key]['fields']['from'] = array();
                $rows[$key]['fields']['to'] = array();
                $rows[$key]['fields']['action'] = array();
                $fromSettings = $settings;
                $fromSettings['mapping']['fieldName'] = $settings['mapping']['fieldName'] . '[' . $key . '][from]';
                $toSettings = $settings;
                $toSettings['mapping']['fieldName'] = $settings['mapping']['fieldName'] . '[' . $key . '][to]';

                $items = explode(" ", $val);

                $rows[$key]['fields']['from']['display'] = \CMF\Field\Text::displayForm($items[2], $fromSettings, $model);
                $rows[$key]['fields']['to']['display'] = \CMF\Field\Text::displayForm($items[3], $toSettings, $model);

                $selectSettings = $settings;
                $selectSettings['title'] = 'Action';
                $selectSettings['options'] = array('301' => 'Permanent Redirect(301)', '302' => 'Temporary Redirect(302)');
                $selectSettings['mapping']['fieldName'] = $settings['mapping']['fieldName'] . '[' . $key . '][action]';
                $rows[$key]['fields']['action']['display'] = \CMF\Field\Select::displayForm($items[1], $selectSettings, $model);
            }
        }

        return array(
            'assets' => \CMF\Field\Htaccess::getAssets(),
            'content' => strval(\View::forge('admin/fields/htaccess.twig', array( 'settings' => $settings, 'singular' => 'rule', 'plural' => 'rules', 'rows' => $rows, 'cols' =>  array('from','to','action'),'fields'=> $fields), false)),
            'widget' => true,
            'widget_class' => ''
        );
    }

    public static function process($value, $settings, $model)
    {
        /*$extraRules = $value['extrarules'];
        unset($value['extrarules']);*/
        $rules = "";
        foreach ($value as $val)
        {
            $valid = true;
            $from = \CMF\Field\Text::process($val['from'], $settings, $model);
            if(self::startsWith($from,"/"))
                $valid = preg_match('|^/[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $from);
            else{
                if(!self::startsWith($from,"http://") && !self::startsWith($from,"https://"))
                    $from = "http://".$from;
                $valid = preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $from);
            }
            if($valid) {
                $to = \CMF\Field\Text::process($val['to'], $settings, $model);
                if(self::startsWith($to,"/"))
                    $valid = preg_match('|^/[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $to);
                else{
                    if(!self::startsWith($to,"http://") && !self::startsWith($to,"https://"))
                        $to = "http://".$to;
                    $valid = preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $to);
                }
                $action = ($val['action'] ? $val['action'] : 301);
                if($valid)
                    $rules .= "Redirect " . $action . " " . $from . " " . $to . "\r\n";
            }
        }
        self::insertToHtaccess($rules/*.$extraRules*/."\r\n");
        $value = $rules/*.$extraRules*/;
        return parent::process($value, $settings, $model);
    }

    private static function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    private static function insertToHtaccess($text){
        $filePath = $_SERVER['DOCUMENT_ROOT'] . DS .'.htaccess';

        if (!file_exists($filePath))
            \File::copy(CMFPATH.'templates/.htaccess', $_SERVER['DOCUMENT_ROOT']);

        $parts = self::getHtaccessParts($filePath);

        file_put_contents($filePath,implode("",$parts['before']).$text.implode("",$parts['after']));
    }

    private static function getHtaccessParts($filePath){
        $lines = file($filePath);
        $parts = array();
        $firstPartEnd = 0;
        for($x = 0;$x < count($lines);$x++)
        {
            if(strpos ($lines[$x],"# BEGIN CMF Rules") !== false) {
                $parts['before'] = array_slice($lines, 0, ($x + 1));
                $firstPartEnd = $x;
            }
            if(strpos ($lines[$x],"# END CMF Rules") !== false) {
                $parts['middle'] = array_slice($lines, ($firstPartEnd + 1),($x - ($firstPartEnd+1)));
                $parts['after'] = array_slice($lines, $x);
                break;
            }
        }
        if(!isset($parts['before']) && !isset($parts['after'])) {
            $parts['before'] = array();
            $parts['middle'] = array();
            $parts['after'] = $lines;
        }
        return $parts;
    }
    
}