<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension to wrap Gedmo's Tree behaviour
 */
class Tree extends Extension
{
	public static $listener;
	
	/** @override */
	public static function init($em, $reader)
	{
		static::$listener = new \Gedmo\Tree\TreeListener();
		static::$listener->setAnnotationReader($reader);
		$em->getEventManager()->addEventSubscriber(static::$listener);
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