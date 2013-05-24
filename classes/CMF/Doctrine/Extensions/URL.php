<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension that powers the CMF's special URL field
 */
class URL extends \Doctrine\Fuel\Extensions\Extension
{
	
	/** @override */
	public static function init(&$config, &$reader, &$event_manager)
	{
		$listener = new URLListener();
		$event_manager->addEventSubscriber($listener);
	}
	
}