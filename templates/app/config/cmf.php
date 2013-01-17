<?php
return array(
	'admin' => array(
		'title' => 'Website',
		'sidebar' => array(
			array( 'heading' => '' ),
			array( 'model' => 'Model_Page_Page' ),
			array( 'model' => 'Model_Settings' ),
			array( 'heading' => '' ),
			array( 'model' => 'Admin\\Model_User' ),
			array( 'model' => 'CMF\\Model\\Role' )
		),
		'install' => false
	),
);
