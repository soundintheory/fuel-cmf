<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension to wrap Gedmo's Translatable behaviour
 */
class Translatable extends Extension
{
	
	/** @override */
	public static function init($em, $reader)
	{
		// http://www.w3schools.com/tags/ref_language_codes.asp
		
		$listener = new \Gedmo\Translatable\TranslatableListener();
		
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
	
}