<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension to wrap Gedmo's Translatable behaviour
 */
class Translatable extends Extension
{
	protected static $listener = null;
	
	/** @override */
	public static function init($em, $reader)
	{
		// http://www.w3schools.com/tags/ref_language_codes.asp
		static::$listener = $listener = new \Gedmo\Translatable\TranslatableListener();
		
		// Current translation locale should be set from session or hook later into the listener
		// Most importantly, before the entity manager is flushed
		$lang = \Config::get('language', 'en');
		$default = \Config::get('language_fallback', $lang);
		if (is_array($default)) $default = $default[0];
		
		$listener->setTranslatableLocale($lang);
		$listener->setDefaultLocale($default);
		$listener->setAnnotationReader($reader);
		$listener->setTranslationFallback(true);
		
		$em->getEventManager()->addEventSubscriber($listener);
	}
	
	public static function setLang($code)
	{
		if (!is_null(static::$listener)) {
			static::$listener->setTranslatableLocale($code);
			static::$listener->setTranslationFallback(true);
		}
	}

	public static function getListener()
	{
		return static::$listener;
	}

	public static function enabled()
	{
		return !is_null(static::$listener);
	}
	
}