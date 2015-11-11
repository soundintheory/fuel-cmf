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
	
}