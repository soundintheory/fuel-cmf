<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Translatable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to identity translation entity to be used for translation storage
     */
    const ENTITY_CLASS = 'Gedmo\\Mapping\\Annotation\\TranslationEntity';

    /**
     * Annotation to identify field as translatable
     */
    const TRANSLATABLE = 'Gedmo\\Mapping\\Annotation\\Translatable';

    /**
     * Annotation to identify field which can store used locale or language
     * alias is LANGUAGE
     */
    const LOCALE = 'Gedmo\\Mapping\\Annotation\\Locale';

    /**
     * Annotation to identify field which can store used locale or language
     * alias is LOCALE
     */
    const LANGUAGE = 'Gedmo\\Mapping\\Annotation\\Language';

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::ENTITY_CLASS)) {
            if (!$cl = $this->getRelatedClassName($meta, $annot->class)) {
                throw new InvalidMappingException("Translation class: {$annot->class} does not exist.");
            }
            $config['translationClass'] = $cl;
        }

        // BEGIN CMF EDIT:
        $className = $class->getName();
        $isCmf = $class->isSubclassOf('CMF\\Model\\Base');
        // END CMF EDIT

        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // translatable property
            if ($translatable = $this->reader->getPropertyAnnotation($property, self::TRANSLATABLE)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find translatable [{$field}] as mapped property in entity - {$meta->name}");
                }
                // fields cannot be overrided and throws mapping exception
                $config['fields'][] = $field;
                if (isset($translatable->fallback)) {
                    $config['fallback'][$field] = $translatable->fallback;
                }
            }
            // BEGIN CMF EDIT:
            // Automatically translates all text based fields, so no annotations required
            else if ($isCmf && $className::langEnabled(false)) {
                
                $field = $property->getName();
                if ($meta->hasField($field)) {
                    
                    $allowed = \Config::get('cmf.languages.translatable_fields', array());
                    $fieldMapping = $meta->getFieldMapping($field);
                    
                    if (in_array($fieldMapping['type'], $allowed) && !in_array($field, $className::excludeTranslations())) {
                        $config['fields'][] = $field;
                        \Admin::addTranslatable($className, $field);

                        if (isset($translatable->fallback)) {
                            $config['fallback'][$field] = $translatable->fallback;
                        }
                    }

                }
            }
            // END CMF EDIT

            // locale property
            if ($this->reader->getPropertyAnnotation($property, self::LOCALE)) {
                $field = $property->getName();
                if ($meta->hasField($field)) {
                    throw new InvalidMappingException("Locale field [{$field}] should not be mapped as column property in entity - {$meta->name}, since it makes no sense");
                }
                $config['locale'] = $field;
            } elseif ($this->reader->getPropertyAnnotation($property, self::LANGUAGE)) {
                $field = $property->getName();
                if ($meta->hasField($field)) {
                    throw new InvalidMappingException("Language field [{$field}] should not be mapped as column property in entity - {$meta->name}, since it makes no sense");
                }
                $config['locale'] = $field;
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (is_array($meta->identifier) && count($meta->identifier) > 1) {
                throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->name}");
            }
        }
    }
}
