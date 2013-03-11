<?php

namespace CMF\Doctrine;

use Doctrine\Common\EventArgs,
    Doctrine\Common\EventSubscriber;

class CacheListener implements EventSubscriber
{
    public $classes = array();
    
    /**
     * Specifies the list of events to listen to
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata'
        );
    }
    
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $this->classes[] = $eventArgs->getClassMetadata()->name;
    }
    
}
