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
			$response = \CMF::getCustomErrorResponse(__("errors.http.500", array(), __("errors.http.default", array(), 'Please contact the website administrator')));
		} catch (\Exception $e) {
			$response = \View::forge('errors'.DS.'production');
		}

		exit($response);
	}

	public static function exception_handler(\Exception $e)
	{
		// Try and stop the cache
		try {
			\CMF\Cache::stop();
		} catch (\Exception $e) {}

		parent::exception_handler($e);
	}
}


