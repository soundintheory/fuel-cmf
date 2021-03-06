<?php

namespace CMF\Core;

class Lang extends \Fuel\Core\Lang
{
	public static $autosave = true;
    public static $auto_translated = array();
	protected static $to_save = array();
	protected static $loaded = array();

	public static $listener = null;

	public static function save($file, $lang, $language = null)
	{
	    if (in_array($file.($language ?: ''), static::$auto_translated)) {
            return true;
        }

		$output = parent::save($file, $lang, $language);

		// Fire save event
		if (static::$listener !== null) {
			static::$listener->onSaveTerms($file, $lang, $language);
		}

		return $output;
	}

	public static function _get($line, array $params = array(), $default = null, $language = null)
	{
		return parent::get($line, $params, $default, $language);
	}
	
	public static function get($line, array $params = array(), $default = null, $language = null)
	{
		$output = parent::get($line, $params, '__NOT__FOUND__', $language);
		if (!empty($output) && $output != '__NOT__FOUND__') return $output;

		if ($output == '__NOT__FOUND__') $output = $default;
		if (!static::$autosave || !\CMF::$lang_enabled) return $output;

		($language === null) and $language = static::get_lang();

		$pos = (strpos($line, ' ') === false) ? strpos($line, '.') : false;
        if ($pos == strlen($line)-1) {
            $pos = false;
        }

		$group = 'common';
		$basename = $line;
		if ($pos === false) {
			if (empty($default)) $default = $line;
			$line = "$group.".\Fuel\Core\Inflector::friendly_title($line);
		} else {
			$basename = substr($line, $pos+1);
			if (empty($default)) $default = $basename;
			$group = substr($line, 0, $pos);
		}

		// Try and load from the DB...
		if (!in_array($group.'_'.$language, static::$loaded)) {
			static::load("$group.db", $group, $language, true, true);
			static::$loaded[] = $group.'_'.$language;
		}

		// Don't continue if it's not the 'common' group
		if ($group != 'common') return ($output != null) ? \Str::tr(\Fuel::value($output), $params) : $default;

		$output = \Arr::get(static::$lines, "$language.$line");
		if ($output == null)
		{
			// First try and get from the fallback...
			$output = \Arr::get(static::$lines, static::$fallback[0].".$line");

			if (!in_array($group, static::$to_save)) static::$to_save[] = $group;

			//if (!empty($default) && $default != $line)
			static::set($line, $default);
			static::set($line, $default, null, static::$fallback[0]);

			if ($output == null) {
				$output = $default;
			}
		}

		return ($output != null) ? \Str::tr(\Fuel::value($output), $params) : $default;
	}

	public static function shutdown()
	{
		if (count(static::$to_save) > 0) {

			$groups = static::$to_save;
			$output = array();

			foreach ($groups as $group) {

				$lft_db = static::load("$group.db", $group, \CMF::lang(), true, true);
				$rgt_db = static::load("$group.db", $group, static::$fallback[0], true, true);
				$lft = \Arr::get(static::$lines, \CMF::lang().'.'.$group, array());
				$rgt = \Arr::get(static::$lines, static::$fallback[0].'.'.$group, array());
				$lft = \Arr::merge($lft_db, $lft);
				$rgt = \Arr::merge($rgt_db, $rgt);

				foreach ($rgt as $key => $phrase) {
					if (!isset($lft[$key])) {
						$lft[$key] = $phrase;
					}
				}

				foreach ($lft as $key => $phrase) {
					if (!isset($rgt[$key])) {
						$rgt[$key] = $phrase;
					}
				}

				static::save("$group.db", $lft, \CMF::lang());
				static::save("$group.db", $rgt, static::$fallback[0]);
			}

			exit();

		}
	}
	
}
