<?php

return array(
	
	/**
	 * Security settings
	 */
	'security' => array(
		
		/**
		 * With output encoding switched on all objects passed will be converted to strings or
		 * throw exceptions unless they are instances of the classes in this array.
		 */
		'whitelisted_classes' => array(
			'CMF\\Model\\Base',
			'CMF\\Field\\Base',
			'CMF\\Admin\\ObjectForm'
		)
	),
	
);
