<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension that powers the CMF's sortable functionality
 */
class Sortable extends \Doctrine\Fuel\Extensions\Extension
{
	
	/** @override */
	public static function init(&$config, &$reader, &$event_manager)
	{
		$listener = new SortableListener();
		$event_manager->addEventSubscriber($listener);
	}
	
}