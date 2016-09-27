<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension to wrap Gedmo's Timestampable behaviour
 */
class Timestampable extends Extension
{
	protected static $listener = null;
	
	/** @override */
	public static function init($em, $reader)
	{
		static::$listener = $listener = new \Gedmo\Timestampable\TimestampableListener();

		$listener->setAnnotationReader($reader);
		$em->getEventManager()->addEventSubscriber($listener);
	}

	public static function disableListener()
	{
		if (empty(static::$listener)) return;
		\D::manager()->getEventManager()->removeEventSubscriber(static::$listener);
	}

	public static function enableListener()
	{
		if (empty(static::$listener)) return;
		\D::manager()->getEventManager()->addEventSubscriber(static::$listener);
	}
	
}