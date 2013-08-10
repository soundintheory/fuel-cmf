<?php

namespace CMF\Doctrine\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * My custom datatype.
 */
class Binary extends Type
{
	const BINARY = 'binary';
	
	/** @override */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
		if ( !isset($fieldDeclaration['length'])) {
            $fieldDeclaration['length'] = $platform->getVarcharDefaultLength();
        }
		$length = $fieldDeclaration['length'];
		return $length ? 'VARBINARY(' . $length . ')' : 'VARBINARY(255)';
    }

    /** @override */
    public function getDefaultLength(AbstractPlatform $platform)
    {
        return $platform->getVarcharDefaultLength();
    }

    /** @override */
    public function getName()
    {
        return self::BINARY;
    }

}