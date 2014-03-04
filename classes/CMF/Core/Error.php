<?php

namespace CMF\Core;

class Error extends \Fuel\Core\Error
{

	public static function show_production_error(\Exception $e)
	{
		// when we're on CLI, always show the php error
		if (\Fuel::$is_cli)
		{
			return static::show_php_error($e);
		}

		if ( ! headers_sent())
		{
			$protocol = \Input::server('SERVER_PROTOCOL') ? \Input::server('SERVER_PROTOCOL') : 'HTTP/1.1';
			header($protocol.' 500 Internal Server Error');
		}

		$response = '';
		try {
			$response = \View::forge('errors'.DS.'500.twig');
		} catch (\Exception $e) {
			$response = \View::forge('errors'.DS.'production');
		}

		exit($response);
	}

}


