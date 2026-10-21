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
     * Same as the parent implementation, with the CMF additions marked
     */
    public function postLoad(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->getName());
        $locale = $this->defaultLocale;
        $oid = null;
        if (isset($config['fields'])) {
            $locale = $this->getTranslatableLocale($object, $meta, $om);
            $oid = spl_object_id($object);
            $this->translatedInLocale[$oid] = $locale;
        }

        if ($this->skipOnLoad) {
            return;
        }

        if (isset($config['fields']) && ($locale !== $this->defaultLocale || $this->persistDefaultLocaleTranslation)) {
            // fetch translations
            $translationClass = $this->getTranslationClass($ea, $config['useObjectClass']);
            $result = $ea->loadTranslations(
                $object,
                $translationClass,
                $locale,
                $config['useObjectClass']
            );
            // translate object's translatable properties
            foreach ($config['fields'] as $field) {
                $translated = $this->defaultTranslationValue;

                foreach ($result as $entry) {
                    if ($entry['field'] == $field) {
                        $translated = $entry['content'] ?? null;

                        break;
                    }
                }

                // update translation
                if ($this->defaultTranslationValue !== $translated
                    || (!$this->translationFallback && (!isset($config['fallback'][$field]) || !$config['fallback'][$field]))
                    || ($this->translationFallback && isset($config['fallback'][$field]) && !$config['fallback'][$field])
                ) {
                    // CMF: record the translated field
                    if ($object instanceof \CMF\Model\Base) $object->_translated[] = $field;

                    $ea->setTranslationValue($object, $field, $translated);
                    // ensure clean changeset
                    $ea->setOriginalObjectProperty(
                        $om->getUnitOfWork(),
                        $object,
                        $field,
                        $meta->getFieldValue($object, $field)
                    );
                }
            }
        } else if ($object instanceof \CMF\Model\Base) {
            // CMF: in the default locale, everything counts as translated
            $object->_translated = \Admin::getTranslatable($meta->name);
        }
    }
}
