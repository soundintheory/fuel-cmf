<?php

namespace CMF\Doctrine\Extensions;

use Doctrine\Common\EventArgs;

/**
 * Gedmo's TranslatableListener, additionally recording which fields of CMF
 * models were actually translated on load (in CMF\Model\Base::$_translated)
 */
class TranslatableListener extends \Gedmo\Translatable\TranslatableListener
{
    /**
     * Mirrors the parent's private $defaultTranslationValue
     */
    protected $cmfDefaultTranslationValue = null;

    public function setDefaultTranslationValue(?string $defaultTranslationValue): void
    {
        parent::setDefaultTranslationValue($defaultTranslationValue);
        $this->cmfDefaultTranslationValue = $defaultTranslationValue;
    }

    public function postLoad(EventArgs $args)
    {
        parent::postLoad($args);

        $ea = $this->getEventAdapter($args);
        $object = $ea->getObject();
        if (!$object instanceof \CMF\Model\Base || $this->isSkipOnLoad()) return;

        $om = $ea->getObjectManager();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->getName());
        $locale = isset($config['fields']) ? $this->getTranslatableLocale($object, $meta, $om) : $this->getDefaultLocale();

        // In the default locale, everything counts as translated
        if (!isset($config['fields']) || ($locale === $this->getDefaultLocale() && !$this->getPersistDefaultLocaleTranslation())) {
            $object->_translated = \Admin::getTranslatable($meta->name);
            return;
        }

        // Same rule the parent uses to decide whether to apply a field's translation
        $result = $ea->loadTranslations($object, $this->getTranslationClass($ea, $config['useObjectClass']), $locale, $config['useObjectClass']);
        foreach ($config['fields'] as $field) {
            $translated = $this->cmfDefaultTranslationValue;
            foreach ($result as $entry) {
                if ($entry['field'] == $field) {
                    $translated = $entry['content'] ?? null;
                    break;
                }
            }

            if ($this->cmfDefaultTranslationValue !== $translated
                || (!$this->getTranslationFallback() && (!isset($config['fallback'][$field]) || !$config['fallback'][$field]))
                || ($this->getTranslationFallback() && isset($config['fallback'][$field]) && !$config['fallback'][$field])
            ) {
                $object->_translated[] = $field;
            }
        }
    }
}
