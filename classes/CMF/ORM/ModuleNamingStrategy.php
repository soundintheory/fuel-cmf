<?php

namespace CMF\ORM;

use \Doctrine\ORM\Mapping\NamingStrategy,
	\Doctrine\ORM\Mapping\DefaultNamingStrategy;

/**
 * Uses the short class name for tables, and the full namespaced names for join tables
 */
class ModuleNamingStrategy extends DefaultNamingStrategy implements NamingStrategy
{
	/**
     * {@inheritdoc}
     */
    public function classToTableName(string $className): string
    {
        if (strpos($className, '\\') !== false) {
            return substr($className, strrpos($className, '\\') + 1);
        }

        return $className;
    }

     /**
     * {@inheritdoc}
     */
    public function joinTableName(string $sourceEntity, string $targetEntity, ?string $propertyName = null): string
    {
        return strtolower($this->namespacedTableName($sourceEntity) . '_' .
                $this->namespacedTableName($targetEntity));
    }

    protected function namespacedTableName(string $className): string
    {
        return str_replace('\\', '_', $className);
    }

}
