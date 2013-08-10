<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension to wrap Gedmo's Tree behaviour
 */
class Tree extends Extension
{
	
	/** @override */
	public static function init($em, $reader)
	{
		$listener = new \Gedmo\Tree\TreeListener();
		$listener->setAnnotationReader($reader);
		$em->getEventManager()->addEventSubscriber($listener);
	}
	
}