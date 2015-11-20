<?php

return array(

	'common' => array(
		'dashboard'					=> 'Dashboard',
		'account'					=> 'Account',
		'accounts'					=> 'Accounts',
		'permission'				=> 'Permission',
		'permission'				=> 'Permission',
		'permissions'				=> 'Permissions',
		'page'						=> 'Page',
		'pages'						=> 'Pages',
		'phrase'					=> 'Phrase',
		'phrases'					=> 'Phrases',
		'map_search_placeholder'	=> 'Search by address, postcode or coordinates',
		'auto_update'				=> 'Auto update',
		'username'					=> 'Username',
		'password'					=> 'Password',
		'all' 						=> 'All',
		'done' 						=> 'Done',
		'content_type'				=> 'Content type',
		'content_types'				=> 'Content types',
		'resource'					=> 'Resource',
		'resources'					=> 'Resources',
		'select_all'				=> 'Select all',
		'deselect_all'				=> 'Deselect all',
		'show_hidden'				=> 'Show hidden',
		'create_resource'			=> 'Create :resource',
		'save_resource'				=> 'Save :resource',
		'creating_resource'			=> 'Creating :resource',
		'saving_resource'			=> 'Saving :resource',
		'save_and_close'			=> 'Save & Close',
		'create_add_another'		=> 'Create & Add Another...',
		'add_resource'				=> 'Add :resource',
		'add_new_resource'			=> 'Add new :resource',
		'edit_resource'				=> 'Edit :resource',
		'new_resource'				=> 'New :resource',
		'edit_item'					=> 'Edit item',
		'add_item'					=> 'Add item',
		'add_new_item'				=> 'Add new item',
		'delete_item'				=> 'Delete item',
		'move_item'					=> 'Move item',
		'add_child'					=> 'Add Child',
		'add_child_resource'		=> 'Add Child :resource',
		'choose_a_type'				=> 'Choose a type',
		'label'						=> 'Label',
		'value'						=> 'Value',
		'external'					=> 'External',
		'back_to_resource'			=> 'Back to :resource',
		'file'						=> 'File',
		'url'						=> 'URL',
		'last_updated_at'			=> 'Last updated at :date',
		'edit_resource_permissions' => 'Edit :resource permissions',
		'role_resource_permissions' => ':resource permissions for :role',
		'reset_filters'				=> 'Reset filters',
		'search_results'			=> 'Search Results',
		'search_results_for'		=> 'Search Results For ":query"',
		'recover_tree'				=> 'Recover Tree',
		'resource_tree'				=> ':resource Tree',
		'language'					=> 'Language',
		'languages'					=> 'Languages',
		'common_term'				=> 'Common term',
		'common_terms'				=> 'Common terms',
		'save_all'					=> 'Save all',
		'clear_all'					=> 'Clear all'
	),

	'verbs' => array(
		'search'					=> 'Search',
		'create'					=> 'Create',
		'update'					=> 'Update',
		'save'						=> 'Save',
		'delete'					=> 'Delete',
		'process'					=> 'Process',
		'view'						=> 'View',
		'manage'					=> 'Manage',
		'login'						=> 'Login',
		'logout'					=> 'Logout',
		'remove'					=> 'Remove',
		'add'						=> 'Add',
		'edit'						=> 'Edit',
		'browse'					=> 'Browse',
		'import'					=> 'Import',
		'move'						=> 'Move',
		'upload'					=> 'Upload',
		'cancel'					=> 'Cancel',
		'retry'						=> 'Retry'
	),

	'messages' => array(
		'item_save_success'			=> ':resource saved successfully',
		'item_delete_success'		=> ':resource deleted successfully',
		'item_create_success'		=> ':resource created successfully',
		'item_updated'				=> ':resource updated successfully',
		'num_items_updated'			=> ':num items were updated',
		'save_all_success'			=> 'All items were saved',
		'reset_images_success'		=> 'All the images have been reset',
		'import_success'			=> 'Data Successfully imported',
		'translations_save_success'	=> 'The translations were successfully saved',
		'tree_recovery_success'		=> 'The tree was recovered',
		'import_url_success'		=> 'Contents of remote URL imported successfully',
		'no_items_added'			=> 'There are no :resource added yet',
		'no_items_found'			=> 'No :resource were found',
		'tree_corrupted'			=> 'This tree appears to be corrupted. Click the button below to attempt a recovery',
		'item_delete_confirm'		=> 'Do you really want to remove this item? You can\'t undo!',
		'items_delete_confirm'		=> 'Do you really want to remove these items? You can\'t undo!',
		'click_select_link'			=> 'Click to select a link...',
		'get_started_click'			=> 'Click \':button\' to get started',
		'unsaved_changes'			=> 'There are potentially unsaved changes to this item. If you continue they may be lost.'

	),

	'import' => array(
		'import_resource'			=> 'Import :resource',
		'select_a_file'				=> 'Select a file to import',
		'import_from_url'			=> 'Import from URL',
		'import_from_file'			=> 'Import from file',
		'imported'					=> 'Imported',
		'import_from_location'		=> 'Imported from :location'
	),

	'bing' => array(
		'api_key_not_set'			=> 'Your Bing Maps API key is not set. Please check your settings.'
	),

	'validation' => array(
		'unique'					=> ':field already exists',
		'required'					=> ':field is required',
		'invalid'					=> ':field is invalid'
	),

	'upload' => array(
		'fail_upload'				=> 'Upload failed',
		'drag_zone'					=> 'Drop files here to upload',
		'drop_processing'			=> 'Processing dropped files...',
		'format_progress'			=> '{percent}% of {total_size}',
		'waiting_for_response'		=> 'Processing...',
		'type_error'				=> '{file} has an invalid extension. Valid extension(s): {extensions}.',
		'size_error'				=> '{file} is too large, maximum file size is {sizeLimit}.',
		'min_size_error'			=> '{file} is too small, minimum file size is {minSizeLimit}.',
		'empty_error'				=> '{file} is empty, please select files again without it.',
		'no_files_error'			=> 'No files to upload.',
		'on_leave'					=> 'The files are being uploaded, if you leave now the upload will be cancelled.',
		'too_many_files_error'		=> 'You may only drop one file',
		'no_file_selected'			=> 'No file selected',
		'upload_in_progress'		=> 'Uploading'
	),

	'image' => array(
		'reset_crop'				=> 'Reset crop',
		'scale'						=> 'Scale',
		'edit_crop_info'			=> 'Select an option above to edit the different crops for this image'
	),

	'errors' => array(

		'default'					=> 'There was an error performing the requested action',
		'not_a_number'				=> 'This is not a valid number',

		'actions' => array(
			'save'					=> 'There were errors when saving the :resource',
			'delete'				=> 'Could not delete item: :message',
			'duplicate'				=> 'Could not duplicate item: :message',
			'import'				=> 'The data could not be imported - please check it and try again'
		),

		'upload' => array(
			'no_files'				=> 'No files were uploaded',
			'directory_not_created'	=> 'The upload directory could not be found or created'
		),

		'http' => array(
		    'default'				=> 'Please contact the website administrator',
		    '404'					=> 'That :resource could not be found'
		),

		'unauthorized' => array(
			'default'				=> 'You are not authorized',
			'action_plural'			=> 'You\'re not allowed to :action :resource',
			'action_singular'		=> 'You\'re not allowed to :action this :resource',
			'super_only'			=> 'Error: Only super users can do this'
		),

		'account' => array(
			'unconfirmed'			=> 'You have to confirm your account before continuing',
			'already_confirmed'		=> ':email was already confirmed, please try signing in',
			'locked'				=> 'Your account is locked',
			'invalid'				=> 'Your username or password was incorrect',
			'expired_token'			=> ':name token has expired, please request a new one',
			'password_mismatch'		=> 'Your passwords do not match'
		),

		'tree' => array(
			'not_enough_data'		=> 'Not enough data to update the tree',
			'not_in_tree'			=> 'That item is not part of a tree',
			'recovery_unsuccessful' => 'Recovery was unsuccessful - you may need to manually check it or empty the database table and start again',
			'recovery_error'		=> 'There was an error recovering the tree: :message'
		),

		'video' => array(
			'video_url_unknown'		=> 'That video URL is not recognised'
		),

		'language' => array(
			'name_not_found' 		=> 'Language name not found'
		),

		'import' => array(
			'url_auth_required'		=> 'URL requires authentication. Check your API key settings',
			'url_inaccessible'		=> 'There was a problem accessing that URL. Please check and try again',
			'file_format_unknown'	=> 'The file format was not recognised',
			'file_parse_error'		=> 'There was an unknown problem parsing the file for import. Please check the formatting'
		)

	)

);