<?php

namespace Admin;

use CMF\Utils\Project;

class Controller_Import extends Controller_Base {
	
	public function get_index($table_name)
	{
		// Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404("Can't find that type!");

		$this->table_name = $table_name;
		$this->template = 'admin/import/form.twig';
		$this->type = $class_name::importType();
		$this->importModel = $class_name::importModel();
	}
	
	public function post_index($table_name)
	{
		try {
		    set_time_limit(0);
		    ini_set('memory_limit', '256M');
		} catch (\Exception $e) {
		    // Nothing!
		}
		// Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404("Can't find that type!");


		$path = DOCROOT.'uploads/imports';
		if (!is_dir($path)) @mkdir($path, 0775, true);


		// Try to back up the DB
		try {
			$result = Project::backupDatabase(null, true);
		} catch (\Exception $e) { }

		if(in_array('file',$class_name::importType())){
			if (count(\Upload::get_files()) > 0) {

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

				$file = \Upload::get_files(0);
				$file_path = \Arr::get($file, 'saved_to').\Arr::get($file, 'saved_as');
				$file_path_relative = str_replace(DOCROOT, '', $file_path);

				// Set the result of the import back to the view
				$this->import_data = \Admin::parseImportFile($file_path);
				$this->import_result = method_exists($class_name, 'action_import') ? $class_name::action_import($this->import_data) : array(
					'success' => false,
					'message' => 'Import is not supported in this part of the system'
				);
			}
		}
		else if($class_name::importModel()){

			$import_model = $class_name::importModel();
			$context = stream_context_create(array(
				'http' => array(
					'method' => 'GET',
					'header' => "Host: mura.me\r\n" .
						"Authorization: bearer 36b30e8125e56182\r\n"
				)
			));
			try{
				$this->import_data = json_decode(file_get_contents('http://mura.me/api/' . $import_model . 's',false,$context));
				foreach($this->import_data as $data){
					var_dump( $this->import_data);
				}
				exit();
				$this->import_result = method_exists($class_name, 'action_import') ? $class_name::action_import($this->import_data) : array(
					'success' => false,
					'message' => 'Import is not supported in this part of the system'
				);
			}
			catch(\Exception $e){

			}
		}
		if (isset($this->import_result['success']) && $this->import_result['success']) {
			\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-success' ), 'msg' => (isset($this->import_result['message']) ? $this->import_result['message'] : 'Data Successfully imported!') ));
			\Response::redirect("/admin/$table_name", 'location');
		} else {
			\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => "There was an error: ".$this->import_result['message'] ));
		}

		$this->table_name = $table_name;
		$this->template = 'admin/import/form.twig';
	}
}