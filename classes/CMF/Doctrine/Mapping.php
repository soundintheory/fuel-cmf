<?php

namespace CMF\Doctrine;

/**
 * Doctrine ORM 3 represents field and association mappings as objects. Much of
 * the CMF reads (and builds settings from) mappings as arrays, as ORM 2 used,
 * so these helpers return them in that format: the same keys and defaults, in
 * the same order (the entity's own properties first, then inherited ones).
 */
class Mapping
{
    /**
     * Keys ORM 2 always set on field mappings, with their defaults
     */
    protected static $field_defaults = array(
        'fieldName' => null,
        'type' => null,
        'scale' => 0,
        'length' => null,
        'unique' => false,
        'nullable' => false,
        'precision' => 0,
        'columnName' => null,
    );

    /**
     * Other field mapping keys ORM 2 set when relevant
     */
    protected static $field_optional = array('id', 'options', 'columnDefinition', 'version', 'inherited', 'declared');

    /**
     * Keys ORM 2 always set on association mappings, with their defaults
     */
    protected static $association_defaults = array(
        'fieldName' => null,
        'targetEntity' => null,
        'mappedBy' => null,
        'inversedBy' => null,
        'cascade' => array(),
        'orphanRemoval' => false,
        'fetch' => 2,
        'type' => null,
        'isOwningSide' => true,
        'sourceEntity' => null,
    );

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

        if ($mapping instanceof \Doctrine\ORM\Mapping\AssociationMapping) {
            $array = array_merge(static::$association_defaults, $mapping->toArray());

            foreach (array('remove', 'persist', 'refresh', 'detach') as $cascade) {
                $array['isCascade'.ucfirst($cascade)] = in_array($cascade, $array['cascade']);
            }
            $array['isCascadeMerge'] = in_array('merge', $array['cascade']);

            // ORM 3.7+ uses the SortDirection enum where ORM 2 had 'ASC' / 'DESC'
            if (!empty($array['orderBy'])) {
                $array['orderBy'] = array_map(function($direction) {
                    return $direction instanceof \UnitEnum ? ($direction->name === 'Descending' ? 'DESC' : 'ASC') : $direction;
                }, $array['orderBy']);
            }

            return $array;
        }

        $vars = get_object_vars($mapping);
        $array = array_merge(static::$field_defaults, array_intersect_key(array_filter($vars, function($value) {
            return $value !== null;
        }), static::$field_defaults));

        foreach (static::$field_optional as $key) {
            if (isset($vars[$key])) $array[$key] = $vars[$key];
        }

        // ORM 3 also records where mapped superclass fields were declared; ORM 2 only did for inherited entity fields
        if (!isset($vars['inherited'])) unset($array['declared']);

        return $array;
    }

    /**
     * All field mappings for a class, as arrays
     *
     * @param  \Doctrine\ORM\Mapping\ClassMetadata $metadata
     * @return array
     */
    public static function fields($metadata)
    {
        return static::inPropertyOrder($metadata, array_map(array(get_called_class(), 'toArray'), $metadata->fieldMappings));
    }

    /**
     * All association mappings for a class, as arrays
     *
     * @param  \Doctrine\ORM\Mapping\ClassMetadata $metadata
     * @return array
     */
    public static function associations($metadata)
    {
        return static::inPropertyOrder($metadata, array_map(array(get_called_class(), 'toArray'), $metadata->associationMappings));
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
        $column = $metadata->discriminatorColumn;
        return $column === null ? null : array_filter(get_object_vars($column), function($value) {
            return $value !== null;
        });
    }

    /**
     * Orders mappings as ORM 2 on PHP 5 did: the class's own properties first,
     * then each parent's (PHP 7.4+ reflection lists parents' properties first)
     */
    protected static function inPropertyOrder($metadata, array $mappings)
    {
        static $orders = array();

        if (!isset($orders[$metadata->name])) {
            $orders[$metadata->name] = array();
            for ($class = new \ReflectionClass($metadata->name); $class; $class = $class->getParentClass()) {
                foreach ($class->getProperties() as $property) {
                    if ($property->class === $class->name && !isset($orders[$metadata->name][$property->name])) {
                        $orders[$metadata->name][$property->name] = count($orders[$metadata->name]);
                    }
                }
            }
        }
        $order = $orders[$metadata->name];

        uksort($mappings, function($a, $b) use ($order) {
            return (isset($order[$a]) ? $order[$a] : PHP_INT_MAX) <=> (isset($order[$b]) ? $order[$b] : PHP_INT_MAX);
        });

        return $mappings;
    }
}
