<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension to wrap Gedmo's Loggable behaviour
 */
class Loggable extends Extension
{
	protected static $listener = null;
	
	/** @override */
	public static function init($em, $reader)
	{
		static::$listener = new \Gedmo\Loggable\LoggableListener();
		static::$listener->setAnnotationReader($reader);
		$em->getEventManager()->addEventSubscriber(static::$listener);
	}

	public static function setUsername($name)
	{
		static::$listener->setUsername($name);
	}
	
}