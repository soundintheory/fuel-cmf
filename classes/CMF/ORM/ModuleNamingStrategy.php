<?php

namespace CMF\ORM;

use \Doctrine\ORM\Mapping\NamingStrategy,
	\Doctrine\ORM\Mapping\DefaultNamingStrategy;

/**
 * Provides Twig support for commonly used FuelPHP classes and methods.
 */
class ModuleNamingStrategy extends DefaultNamingStrategy implements NamingStrategy
{
	/**
     * {@inheritdoc}
     */
    public function classToTableName($className,$keepNamespace = false)
    {
        if($keepNamespace)
            return str_replace('\\','_',$className);

        if (strpos($className, '\\') !== false) {
            return substr($className, strrpos($className, '\\') + 1);
        }

        return $className;
    }
    
     /**
     * {@inheritdoc}
     */
    public function joinTableName($sourceEntity, $targetEntity, $propertyName = null)
    {
        return strtolower($this->classToTableName($sourceEntity,true) . '_' .
                $this->classToTableName($targetEntity,true));
    }

}
