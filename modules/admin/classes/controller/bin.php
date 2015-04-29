<?php

namespace Admin;

/**
 * Provides a means for quickly adding a template to demo
 */
class Controller_Bin extends Controller_Base {
		
	public function action_recoverymode(){

		if(\Input::post('recovery_mode', false)){
            \Session::set('recovery_mode',true);
        }elseif(\Input::post('stop_recovery_mode', false)){
            \Session::delete('recovery_mode');
        }
  		
  		\Response::redirect(\Input::referrer());
	}
}