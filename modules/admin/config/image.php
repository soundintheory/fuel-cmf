<?php

return array(
    
    'driver' => 'imagick',
    'quality' => 80,
    
	'presets' => array(
	    
		'adminthumbnail' => array(
			'quality' => 80,
			'bgcolor' => '#fff',
			'actions' => array(
				array('resize', 60, 80)
			)
		),
		
		'adminpreview' => array(
			'quality' => 80,
			'bgcolor' => '#fff',
			'actions' => array(
				array('resize', 100, 100)
			)
		)
		
	)
);


