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
        \Lang::$listener = static::$listener = $listener;
    }

    public static function getListener()
    {
        return static::$listener;
    }

    public static function disableListener()
    {
        if (empty(static::$listener)) return;
        \D::manager()->getEventManager()->removeEventSubscriber(static::$listener);
    }

    public static function enableListener()
    {
        if (empty(static::$listener)) return;
        \D::manager()->getEventManager()->addEventSubscriber(static::$listener);
    }
}