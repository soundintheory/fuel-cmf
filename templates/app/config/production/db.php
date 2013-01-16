<?php
/**
 * The production database settings.
 */

return array(
	'default' => array(
		'profiling' => false,
		'connection'  => array(
			'dsn'        => 'mysql:host=localhost;dbname=cmftemp',
			'username'   => 'root',
			'password'   => 'root',
			'driver'   => 'pdo_mysql',
		    'dbname'   => 'cmftemp'
		),
	),
);
