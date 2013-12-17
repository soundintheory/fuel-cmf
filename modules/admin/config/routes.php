<?php

return array(
	
	'admin'                   => 'admin/dashboard/index',
	'admin/demo/(:any)'       => 'admin/demo/index/$1',
	'admin/actions/(:any)'    => 'admin/actions/$1',
	'admin/benchmarks/(:any)' => 'admin/benchmarks/$1',
	'admin/filemanager(:any)' => 'admin/filemanager',
	'admin/redactor/(:any)'   => 'admin/redactor/$1',
	'admin/assets/(:any)'     => 'admin/assets/index',
	'admin/upload'            => 'admin/upload/index',
	'admin/upload/video'      => 'admin/upload/video',
	'admin/phpinfo'           => 'admin/base/phpinfo',
	'admin/lang/set'          => 'admin/lang/set',
	'admin/lang/snippets'     => 'admin/lang/snippets',
	
	// Auth actions
	'admin/login'             => array(array('GET', new Route('admin/auth/login')), array('POST', new Route('admin/auth/perform_login'))),
	'admin/logout'            => 'admin/auth/logout',
	'admin/install/(:any)'    => 'admin/install/$1',
	'admin/install'           => 'admin/install',
	
	// Routes for actions with models... these are quite dynamic so make sure they stay at the bottom!
	'admin/(:segment)'                          => 'admin/list/index/$1',
	'admin/(:segment)/saveall'                  => 'admin/list/saveall/$1',
	'admin/(:segment)/permissions'              => 'admin/list/permissions/$1',
	'admin/(:segment)/options'                  => 'admin/list/options/$1',
	'admin/(:segment)/permissions/(:num)'       => 'admin/list/permissions/$1/$2',
	'admin/(:segment)/permissions/(:num)/save'  => 'admin/list/save_permissions/$1/$2',
	'admin/(:segment)/list/order'               => 'admin/list/order/$1',
	'admin/(:segment)/(:num)/populate'          => array(array('POST', new Route('admin/item/populate/$1/$2'))),
	'admin/(:segment)/(:num)/updatetree'        => array(array('POST', new Route('admin/list/updatetree/$1/$2'))),
	'admin/(:segment)/updatetree'               => array(array('POST', new Route('admin/list/updatetree/$1/$2'))),
	
	// Standard item actions
	'admin/(:segment)/create'            => array(array('GET', new Route('admin/item/create/$1')), array('POST', new Route('admin/item/save/$1'))),
	'admin/(:segment)/(:num)/edit'       => array(array('GET', new Route('admin/item/edit/$1/$2')), array('POST', new Route('admin/item/save/$1/$2'))),
	'admin/(:segment)/(:num)/duplicate'       => array(array('GET', new Route('admin/item/duplicate/$1/$2')), array('POST', new Route('admin/item/save/$1/$2'))),
	'admin/(:segment)/(:num)/save'       => array(array('POST', new Route('admin/item/save/$1/$2'))),
	'admin/(:segment)/(:num)/delete'     => 'admin/item/delete/$1/$2',
	'admin/(:segment)/populate'          => array(array('POST', new Route('admin/item/populate/$1'))),
	'admin/(:segment)/(:segment)'        => 'admin/list/index/$1/$2',
	
	// Custom actions
	'admin/(:segment)/(:num)/(:segment)' => 'admin/item/action/$1/$2/$3',
	'admin/(:segment)/(:num)/(:alpha)'   => array(array('GET', new Route('admin/item/$3/$1/$2')), array('POST', new Route('admin/item/save/$1/$2')))
	
);