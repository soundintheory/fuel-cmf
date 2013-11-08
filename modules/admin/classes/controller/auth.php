<?php

namespace Admin;

class Controller_Auth extends \Controller {
	
	public function action_login()
	{
		return \View::forge('admin/auth/login.twig', array( 'next' => \Input::get('next') ));
	}
	
	public function action_perform_login()
	{
	    if (\CMF\Auth::authenticate(\Input::post('username'), \Input::post('password'))) {
            \Response::redirect(\Uri::base(false).\Input::post('next', 'admin/'.\Config::get('cmf.admin.default_section')), 'location');
        } else {
        	\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => "Your username or password was incorrect, try again!" ));
            return \View::forge('admin/auth/login.twig', array( 'next' => \Input::get('next') ));
        }
	}
	
	public function action_logout()
	{
	    if (\CMF\Auth::logout()) {
	    	\Session::delete('cmf.admin.language');
            \Response::redirect(\Uri::base(false).'admin/login', 'location');
        }
	}
	
}