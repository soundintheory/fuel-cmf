<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension to wrap Gedmo's Timestampable behaviour
 */
class Timestampable extends Extension
{
	
	/** @override */
	public static function init($em, $reader)
	{
		$listener = new \Gedmo\Timestampable\TimestampableListener();
		$listener->setAnnotationReader($reader);
		$em->getEventManager()->addEventSubscriber($listener);
	}
	
}