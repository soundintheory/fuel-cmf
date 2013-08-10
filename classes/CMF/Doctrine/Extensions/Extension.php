<?php

namespace CMF\Doctrine\Extensions;

/**
 * A base extension class for the Doctrine Fuel package. This class should
 * be extended for any extensions being added.
 */
class Extension
{
	
	/**
	 * Called when the extension in question is included in the 'doctrine.extensions' config setting
	 * Use this to initialize your extension
	 * 
	 * @param  \Doctrine\ORM\Configuration $config
	 * @param  \Doctrine\Common\Annotations\Reader 		$reader
	 * @param  \Doctrine\Common\EventManager 			$event_manager [description]
	 * @return void
	 */
	public static function init($em, $reader)
	{
		
	}
	
}