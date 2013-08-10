<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension to wrap Gedmo's SoftDeletable behaviour
 */
class SoftDeletable extends Extension
{
	
	/** @override */
	public static function init($em, $reader)
	{
		$listener = new \Gedmo\SoftDeleteable\SoftDeleteableListener();
		$listener->setAnnotationReader($reader);
		$em->getEventManager()->addEventSubscriber($listener);
		$em->getConfiguration()->addFilter('soft-deleteable', 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
	}
	
}