<?php

return array(
    
    'driver' => 'gd',
    
    /**
	 * These presets allow you to call controlled manipulations.
	 */
	'presets' => array(

		/**
		 * This shows an example of how to add preset manipulations
		 * to an image.
		 *
		 * Note that config values here override the current configuration.
		 *
		 * Driver cannot be changed in here.
		 */
		'example' => array(
			'quality' => 100,
			'bgcolor' => null,
			'actions' => array(
				array('crop_resize', 200, 200),
				array('border', 20, "#f00"),
				array('rounded', 10),
				array('output', 'png')
			)
		)
	)
    
);