<?php

namespace CMF\Core;

class Lang extends \Fuel\Core\Lang
{
	
	public static function get($line, array $params = array(), $default = null, $language = null)
	{
		return 'bums';
	}
	
	/*
	public static function get($line, array $params = array(), $default = null, $language = null)
	{
		($language === null) and $language = static::get_lang();

		return isset(static::$lines[$language]) ? \Str::tr(\Fuel::value(\Arr::get(static::$lines[$language], $line, $default)), $params) : $default;
	}
	*/
	
}
