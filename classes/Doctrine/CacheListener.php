<?php

namespace CMF\Doctrine;

use Doctrine\Common\EventArgs,
    Doctrine\ORM\Event\LifecycleEventArgs,
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
            'postLoad'
        );
    }
    
    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $class = get_class($eventArgs->getEntity());
        if (!in_array($class, $this->classes)) $this->classes[] = $class;
    }
    
}
