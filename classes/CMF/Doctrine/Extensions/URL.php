<?php

namespace CMF\Doctrine\Extensions;

/**
 * Extension that powers the CMF's special URL field
 */
class URL extends \CMF\Doctrine\Extensions\Extension
{
	
	/** @override */
	public static function init($em, $reader)
	{
		$listener = new URLListener();
		$em->getEventManager()->addEventSubscriber($listener);
	}
	
}