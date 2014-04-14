<?php

namespace CMF\Core;

class Lang extends \Fuel\Core\Lang
{
	protected static $to_save = 0;
	protected static $loaded = array();
	
	public static function get($line, array $params = array(), $default = null, $language = null)
	{
		if (!\CMF::$lang_enabled) return parent::get($line, $params, $default, $language);

		($language === null) and $language = static::get_lang();
		$pos = strpos($line, '.');
		$group = 'common';
		if ($pos === false) {
			if (empty($default)) $default = $line;
			$line = "$group.$line";
		} else {
			if (empty($default)) $default = substr($line, $pos+1);
			$group = substr($line, 0, $pos);
		}

		// Try and load from the DB...
		if (!in_array($group, static::$loaded)) {
			static::load("$group.db", $group, $language, false, true);
		}

		$output = \Arr::get(static::$lines, "$language.$line");
		if ($output == null) {
			static::$to_save++;
			static::set($line, $default);
			static::set($line, $default, null, static::$fallback[0]);
			$output = $default;
		}

		return ($output != null) ? \Str::tr(\Fuel::value($output), $params) : $default;
	}

	public static function shutdown()
	{
		if (static::$to_save !== 0) {

			foreach (static::$lines as $lang => $groups) {

				foreach ($groups as $group => $lines) {

					// Save the lines back to the DB
					static::save("$group.db", $group, $lang);

					// Also take this opportunity to save the lines to the fallback language
					static::save("$group.db", $group, static::$fallback[0]);
				}
				
			}

			//var_dump('WHHHAAAAT?');
		}
	}
	
}
