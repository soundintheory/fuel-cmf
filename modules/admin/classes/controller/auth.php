<?php

namespace Admin;

use CMF\Model\Settings,
	CMF\Model\User;

class Controller_Auth extends \Controller {
	
	public function action_login()
	{
		return \View::forge('admin/auth/login.twig');
	}
	
	public function action_perform_login()
	{
		// $settings = new Settings();

		// print_r($settings);
		// $twofaEnabled = $settings->get("twofa_enabled");
		// $twofaMethod = $settings->get("twofa_method");

		// echo "Two factor auth: ".$twofaEnabled;
		// echo "<br>";
		// echo "Two factor method: ".$twofaMethod;
		// echo "<br>";

		$twofaEnabled = true;

		// if(\CMF\Auth::testauth(\Input::post('username'))){
			
		// }
		// if(\CMF\Auth::authenticate_no_login(\Input::post('username'), \Input::post('password'))){ //check user creds are correct
		// 	echo "user correct";
			
		// 	// return false;
		// }
	    if (\CMF\Auth::authenticate(\Input::post('username'), \Input::post('password'))) {
			if(\CMF\TwoFactorAuth::isGlobalTwoFactorEnabled()){ //two factor auth is enabled globally
				\Session::set('twoFactorAuth','0');
				\CMF\TwoFactorAuth::defaultRoutingForUser();
			}else{
				\CMF::adminRedirect(\Input::post('next', \CMF::adminPath(\Config::get('cmf.admin.default_section'))), 'location');
			}
        } else {
        	\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => \Lang::get('admin.errors.account.invalid') ));
            return \View::forge('admin/auth/login.twig');
        }
	}
	
	public function action_logout()
	{
	    if (\CMF\Auth::logout()) {
	    	\Session::delete('cmf.admin.language');
            \CMF::adminRedirect('/login', 'location');
        }
	}

	public function action_twofactor(){
		return \CMF\TwoFactorAuth::twoFactorSetup();
	}

	public function action_perform_twofactor(){
		\CMF\TwoFactorAuth::enabledUserTwoFactorAuth();
	}

	public function action_twofactor_codeinput(){
		return \CMF\TwoFactorAuth::twoFactorCodeInput();
	}

	public function action_perform_twofactor_codeinput(){
		\CMF\TwoFactorAuth::checkCode();
		return false;
	}
	
}