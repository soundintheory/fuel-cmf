<?php

return array(
    
    'defaults' => array(
        
		'useragent'	=> 'FuelPHP',
		
		'driver'		=> 'sendmail',
		
		'from'		=> array(
			'email'		=> 'cmf@soundintheory.co.uk',
			'name'		=> 'CMF Emailer',
		),
		
		'wordwrap'	=> 76,
		
		// 'smtp'	=> array(
		// 	'host'		=> 'ssl://smtp.gmail.com',
		// 	'port'		=> 465,
		// 	'username'	=> '',
		// 	'password'	=> '',
		// 	'timeout'	=> 10
		// ),
		
		'newline' => "\r\n"
		
	)
	
);