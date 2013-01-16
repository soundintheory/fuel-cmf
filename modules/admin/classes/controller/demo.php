<?php

namespace Admin;

/**
 * Provides a means for quickly adding a template to demo
 */
class Controller_Demo extends Controller_Base {
	
	public function action_index($name)
	{
		$this->template = 'admin/demo/'.$name.'.twig';
	}
	
}