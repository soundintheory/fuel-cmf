<?php

return array(
	'image'  => 'image/image',  // The default route
	'image/(:alpha)/(:any)' => 'image/preset/$1',
	'image/:cropx/:cropy/:cropw/:croph/:w/:h/(:any)' => 'image/coordinate_crop',
	'image/:mode/:w/:h/(:any)' => 'image/w_h',
	'image/:mode/:w/:h/(:any)' => 'image/w_h',
	
	'image/:bgwidth/:bgheight/:layery/:layerx/:cropy/:cropx/:cropw/:croph/:w/:h/(:any)' => 'image/index',
);