<?php

namespace CMF\Doctrine;

/**
 * Doctrine ORM 3 represents field and association mappings as objects. Much of
 * the CMF reads (and builds settings from) mappings as arrays, as ORM 2 used,
 * so these helpers return them in that format.
 */
class Mapping
{
    /**
     * Converts a single mapping object to an array
     *
     * @param  object|array|null $mapping
     * @return array|null
     */
    public static function toArray($mapping)
    {
        if ($mapping === null || is_array($mapping)) {
            return $mapping;
        }

        // association mappings include 'type' and 'isOwningSide'
        if (method_exists($mapping, 'toArray')) {
            return $mapping->toArray();
        }

        return array_filter(get_object_vars($mapping), function($value) {
            return $value !== null;
        });
    }

    /**
     * All field mappings for a class, as arrays
     *
     * @param  \Doctrine\ORM\Mapping\ClassMetadata $metadata
     * @return array
     */
    public static function fields($metadata)
    {
        return array_map(array(get_called_class(), 'toArray'), $metadata->fieldMappings);
    }

    /**
     * All association mappings for a class, as arrays
     *
     * @param  \Doctrine\ORM\Mapping\ClassMetadata $metadata
     * @return array
     */
    public static function associations($metadata)
    {
        return array_map(array(get_called_class(), 'toArray'), $metadata->associationMappings);
    }

    /**
     * Replacement for ORM 2's ClassMetadata::getAssociationsByTargetClass()
     *
     * @param  \Doctrine\ORM\Mapping\ClassMetadata $metadata
     * @param  string $targetClass
     * @return array
     */
    public static function associationsByTargetClass($metadata, $targetClass)
    {
        return array_filter(static::associations($metadata), function($mapping) use ($targetClass) {
            return $mapping['targetEntity'] == $targetClass;
        });
    }

    /**
     * The discriminator column mapping as an array, or null if there isn't one
     *
     * @param  \Doctrine\ORM\Mapping\ClassMetadata $metadata
     * @return array|null
     */
    public static function discriminatorColumn($metadata)
    {
        return static::toArray($metadata->discriminatorColumn);
    }
}
