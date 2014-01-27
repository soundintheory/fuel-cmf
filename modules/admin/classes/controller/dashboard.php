<?php

namespace Admin;

class Controller_Dashboard extends Controller_Base {
	
	public function action_index()
	{
		
	}
	
	public function after($response)
	{
		$response = parent::after($response);
		
		$href = \Arr::get($this->sidebar, '0.items.0.href', false);
		if ($href != false) {
			return \Response::redirect($href);
		}
		
		return $response;
	}
	
}