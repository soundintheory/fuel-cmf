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
		$listener = new \Gedmo\Translatable\TranslatableListener();
		
		// Current translation locale should be set from session or hook later into the listener
		// Most importantly, before the entity manager is flushed
		$listener->setTranslatableLocale('en');
		$listener->setDefaultLocale('en');
		
		$listener->setAnnotationReader($reader);
		$em->getEventManager()->addEventSubscriber($listener);
	}
	
}