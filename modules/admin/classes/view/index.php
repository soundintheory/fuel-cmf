<?php

namespace Admin;

class View_Index extends ViewModel
{
	/**
	 * Prepare the view data, keeping this in here helps clean up
	 * the controller.
	 * 
	 * @return void
	 */
	public function view()
	{
		$this->name = $this->request()->param('name', 'World');
		
	}
}