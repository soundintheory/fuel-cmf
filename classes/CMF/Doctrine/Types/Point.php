<?php

namespace CMF\Doctrine\Types;
 
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
 
/**
 * Mapping type for spatial POINT objects
 */
class Point extends Type
{
    const POINT = 'point';
 
    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return self::POINT;
    }
 
    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration The field declaration.
     * @param AbstractPlatform $platform The currently used database platform.
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'POINT';
    }
 
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        //Null fields come in as empty strings
        if($value == '') return null;
 
        $data = unpack('x/x/x/x/corder/Ltype/dlat/dlon', $value);
        return new \Doctrine\Fuel\Spatial\Point($data['lat'], $data['lon']);
    }
 
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!$value) return;
 
        return pack('xxxxcLdd', '0', 1, $value->latitude, $value->longitude);
    }
}