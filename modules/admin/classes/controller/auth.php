<?php

namespace Admin;

class Controller_Auth extends \Controller {
	
	public function action_login()
	{
		return \View::forge('admin/auth/login.twig');
	}
	
	public function action_perform_login()
	{
	    if (\CMF\Auth::authenticate(\Input::post('username'), \Input::post('password'))) {
            \CMF::adminRedirect(\Input::post('next', \CMF::adminPath(\Config::get('cmf.admin.default_section'))), 'location');
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
	
}