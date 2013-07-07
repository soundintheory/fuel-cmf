<?php

namespace CMF\Field;

class Color extends Base {
    
    protected static $defaults = array(
        'default' => '#000000'
    );
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                '/admin/assets/js/fields/color.js'
            )
            /*
            ,
            'css' => array(
                '/admin/assets/redactor/redactor.css'
            )
            */
        );
    }
    
}