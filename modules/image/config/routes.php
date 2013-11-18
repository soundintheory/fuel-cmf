<?php

return array(
	'image'  => 'image/image',  // The default route
	'image/(tl|t||tr|l|c|r|bl|b|br)/(:num)/(:num)/(:any)' => 'image/grid_crop/$1/$2/$3/$4',
	'image/(:alpha)/(:any)' => 'image/preset/$1',
	//'image/cc/:cropx/:cropy/:cropw/:croph/:w/:h/(:any)' => 'image/coordinate_crop',
	'image/(:segment)/(:segment)/(:num)/(:num)/(:num)/(:num)/(:any)' => 'image/coordinate_crop/$1/$2/$3/$4/$5/$6/$7',
	//broken dont use'image/(:num)/:w/:h/(:any)' => array('image/w_h','mode' => '$1'),
	'image/(:num)/(:num)/(:num)/(:any)' => 'image/w_h/$1/$2/$3/$4',
	
	'image/:bgwidth/:bgheight/:layery/:layerx/:cropy/:cropx/:cropw/:croph/:w/:h/(:any)' => 'image/index',
);