<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension to wrap Gedmo's Loggable behaviour
 */
class Loggable extends Extension
{
	
	/** @override */
	public static function init($em, $reader)
	{
		$listener = new \Gedmo\Loggable\LoggableListener();
		$listener->setAnnotationReader($reader);
		$em->getEventManager()->addEventSubscriber($listener);
	}
	
}