<?php

namespace CMF\Core;

class Lang extends \Fuel\Core\Lang
{
	
	public static function get($line, array $params = array(), $default = null, $language = null)
	{
		($language === null) and $language = static::get_lang();
		
		$output = \Arr::get(static::$lines, $language.".".$line, null);
		return $output !== null ? \Str::tr(\Fuel::value($output), $params) : \Str::tr(\Fuel::value(\Arr::get(static::$lines, static::$fallback[0].".".$line, $default)), $params);
	}
	
}
