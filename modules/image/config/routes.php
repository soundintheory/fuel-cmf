<?php

return array(
	'image/(tl|t|tr|l|c|r|bl|b|br)/(:num)/(:num)/(:any)' => 'image/grid_crop/$1/$2/$3/$4',
	'image/(:segment)/(:segment)/(:num)/(:num)/(:num)/(:num)/(:any)' => 'image/crop/$1/$2/$3/$4/$5/$6/$7',
	'image/(:num)/(:num)/(:num)/(:any)' => 'image/resize/$1/$2/$3/$4'
);