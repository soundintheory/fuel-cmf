<?php

namespace Api;

/**
 * Model Controller.
 * 
 * @package  app
 * @extends  Controller
 */
class Controller_Test extends Controller_Resource
{
	public function action_index()
	{
		return array( 'action' => 'test index' );
	}

	public function action_something()
	{
		return array( 'action' => 'test something' );
	}

}