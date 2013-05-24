<?php

return array(
	
	// if true, the $_FILES array will be processed when the class is loaded
	'auto_process'		=> true,
	
	// default path the uploaded files will be saved to
	'path'				=> 'uploads/',
	
	// create the path if it doesn't exist
	'create_path'		=> true,
	
	// if true, add a number suffix to the file if the file already exists
	'auto_rename'		=> true,
	
	// if true, normalize the filename (convert to ASCII, replace spaces by underscores)
	'normalize'			=> true,
	'normalize_separator' => '-',

	// valid values are 'upper', 'lower', and false. case will be changed after all other transformations
	'change_case'		=> 'lower',
	
);