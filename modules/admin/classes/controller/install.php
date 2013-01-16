<?php

namespace Admin;

class Controller_Install extends Controller_Base {
	

	public function before(){
		if(\Uri::segment(3) != 'sync' && !\Admin::installer()){
			\Response::redirect('/admin/login');
      }
	}
	/**
	 * Go through every entry in the system and make sure the URL is up to date
	 */
	public function action_index()
	{
		return \Response::forge(\View::forge('admin/install/index.twig', array()));	
	}
	
	public function action_setup($task = null)
	{
		$status = array('status' => false);

		if ($task == 'go') {
			
			if (\CMF\Install::copy_app()) {
				$status['status'] = true;
			}
			
			if (\Input::post()) {
				$project_name = \Input::post('project_name');
				$admin_title = \Input::post('admin_title');
				$config_settings = array( "site" => $project_name, "title" => $admin_title );
				\CMF\Install::setup_admin_config($config_settings);
			}
			
			\Response::redirect(\Uri::base(false).'admin/install/database');
			
		}
		
		return \Response::forge(\View::forge('admin/install/setup.twig', $status));
	}

	public function action_database(){
		$data = array();
		if(\Input::post()){
			$data['post'] = \Input::post();
			//now we have some database post data...ummyeaaahh
			//var_dump(\Input::post());exit;
			$database_host = \Input::post('database_host');
			$database_username = \Input::post('database_username');
			$database_password = \Input::post('database_password');
			$database_name = \Input::post('database_name');

			$status = \CMF\Install::db_setup($database_host, $database_username, $database_password, $database_name);
			if($status[0]){
				\Response::redirect('/admin/install/migration');
			}
			else{
				//we had an error connecting.
				var_dump($status);
				$data['status'] = $status;
			}
			//
		}
		return \Response::forge(\View::forge('admin/install/database.twig', $data));
	}

	public function action_migration($task = null){
		$status = array('status' => false);

		if($task == 'go'){
			// \Migrate::latest('default', 'app');
			$cmf_install = new \CMF\Install;
			if($cmf_install->install_migration()){
				$status = array('status' => true);
			}
		}
		else{

		}

		return \Response::forge(\View::forge('admin/install/migration.twig', $status));
	}

	public function action_createuser(){
		
		$error = null;

		if(\Input::post()){
			$username = \Input::post('username');
			$password = \Input::post('password');
			$email_address = \Input::post('email_address');
			if(\CMF\Install::createSuperUser($username, $password, $email_address)){
				\Response::redirect('/admin/install/finishing');
			}
			else{
				$error = "Could not create super user.";
			}
		}
		include(CMFPATH."vendor/passgen/pwgen.class.php");
		$pwgen = new \PWGen();
	   $password = $pwgen->generate();

	   $email_address = "system.admin@soundintheory.co.uk";

	   $username = "administrator";

	   $data = array(
	                 "username" => $username,
	                 "email_address" => $email_address,
	                 "password" => $password,
	                 "error" => $error);
		return \Response::forge(\View::forge('admin/install/createuser.twig', $data));
	}

	public function action_finishing($task = null){
		if($task == 'go'){
			//finish up: put 'install' as false in config, so it doesnt install again. Redirect user to login page.
			if(\CMF\Install::disable_install()){
				\Response::redirect('/admin/login');
			}

		}
		return \Response::forge(\View::forge('admin/install/finishing.twig', array()));
	}
	public function action_sync($task = null){
		if (!\CMF\Auth::check('admin')) {
            \Response::redirect(\Uri::base(false)."admin/login?next=".\Uri::string(), 'location');
        }
		$status = array('status' => null);

		if($task == 'go'){
			// \Migrate::latest('default', 'app');
			$cmf_install = new \CMF\Install;
			if($cmf_install->install_migration()){
				$status = array('status' => true);
			}
		}
		if($status['status']){
			\Response::redirect('/admin');
		}
		return \Response::forge(\View::forge('admin/install/sync.twig', $status ));
	}
	
}