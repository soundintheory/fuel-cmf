<?php
namespace CMF\Doctrine\Extensions;

/**
* Extension to auto translate php
*/
class Translator extends Extension
{
    protected static $listener = null;

    /** @override */
    public static function init($em, $reader)
    {
        $listener = new \CMF\Doctrine\Extensions\TranslatorListener();

        $em->getEventManager()->addEventSubscriber($listener);
    }

}