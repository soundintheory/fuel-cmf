<?php

namespace Doctrine\ORM\Mapping;

/**
 * @Annotation
 * @Target({"PROPERTY","ANNOTATION"})
 */
final class Column implements Annotation
{
    /** @var string */
    public $name;
    /** @var mixed */
    public $type = 'string';
    /** @var integer */
    public $length;
    /** @var integer */
    public $precision = 0; // The precision for a decimal (exact numeric) column (Applies only for decimal column)
    /** @var integer */
    public $scale = 0; // The scale for a decimal (exact numeric) column (Applies only for decimal column)
    /** @var boolean */
    public $unique = false;
    /** @var boolean */
    public $nullable = true;
    /** @var array */
    public $options = array();
    /** @var string */
    public $columnDefinition;
}