<?php

namespace Admin;

use CMF\Utils\Project;

class Controller_Import extends Controller_Base {
	
	public function get_index($table_name)
	{
		// Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404(null, "type");

		$this->table_name = $table_name;
		$this->plural = $class_name::plural();
		$this->singular = $class_name::singular();
		$this->icon = $class_name::icon();
		$this->template = 'admin/import/form.twig';
		$this->methods = $methods = $class_name::importMethods();
		$this->type = $class_name::importType();
		$this->show_all = empty($methods);

		$this->importUrl = '';
		$base_url = rtrim(\CMF\Model\DevSettings::instance()->parent_site, '/');
		if (!empty($base_url)) {
			$this->importUrl = $base_url.'/api/'.\Inflector::pluralize($table_name).'.json';
		}
	}
	
	public function action_file($table_name)
	{
		// Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404(null, "type");

		// Don't continue if no files have been uploaded
		if (!count(\Upload::get_files())) {
			\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => \Lang::get('admin.errors.upload.no_files') ));
			\Response::redirect_back(\CMF::adminPath("/$table_name"));
		}

		// Ensure directory exists
		$path = DOCROOT.'uploads/imports';
		if (!is_dir($path)) @mkdir($path, 0775, true);
		if (!is_dir($path)) {
			\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => \Lang::get('admin.errors.upload.directory_not_created') ));
			return;
		}

		// Process the uploaded file
		\Upload::process(array(
			'path' => $path,
			'auto_rename' => false,
			'overwrite' => true,
			'normalize' => true,
			'ext_whitelist' => array('xlsx', 'xls', 'xml', 'csv', 'json'),
		));

		// Save it to the filesystem if valid
		if (\Upload::is_valid()) {
			\Upload::save();
		}

		// Import the file
		$file = \Upload::get_files(0);
		$file_path = \Arr::get($file, 'saved_to').\Arr::get($file, 'saved_as');
		$this->import_result = \CMF\Utils\Importer::importFile($file_path, $class_name);

		// If success, redirect back with message
		if (isset($this->import_result['success']) && $this->import_result['success']) {
			\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-success' ), 'msg' => (isset($this->import_result['message']) ? $this->import_result['message'] : \Lang::get('admin.messages.import_success')) ));
			\CMF::adminRedirect("/$table_name", 'location');
		}

		// No success, damn!
		\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => (isset($this->import_result['message']) ? $this->import_result['message'] : \Lang::get('admin.errors.actions.import')) ));
		\Response::redirect_back(\CMF::adminPath("/$table_name"));
	}

	public function action_url($table_name)
	{
		// Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404(null, "type");

		// Import the data
		$this->import_result = \CMF\Utils\Importer::importUrl($class_name, \Input::post('import_url'));

		// If success, redirect back with message
		if (isset($this->import_result['success']) && $this->import_result['success']) {
			\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-success' ), 'msg' => (isset($this->import_result['message']) ? $this->import_result['message'] : \Lang::get('admin.messages.import_success')) ));
			\CMF::adminRedirect("/$table_name", 'location');
		}

		// No success, damn!
		\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => (isset($this->import_result['message']) ? $this->import_result['message'] : \Lang::get('admin.errors.actions.import')) ));
		\Response::redirect_back(\CMF::adminPath("/$table_name"));
	}
}