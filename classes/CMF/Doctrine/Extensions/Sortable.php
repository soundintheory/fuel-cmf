<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension that powers the CMF's sortable functionality
 */
class Sortable extends \CMF\Doctrine\Extensions\Extension
{
	
	/** @override */
	public static function init($em, $reader)
	{
		$listener = new SortableListener();
		$em->getEventManager()->addEventSubscriber($listener);
	}
	
}