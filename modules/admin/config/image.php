<?php

return array(
    
    'driver' => 'imagick',
    
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


