<?php

namespace CMF\Field;

class Text extends Base {
    
    public static $always_process = true;
    
    protected static $defaults = array(
        'auto_update' => '1'
    );
	
}