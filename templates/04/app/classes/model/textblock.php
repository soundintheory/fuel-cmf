<?php

use Doctrine\ORM\Mapping as ORM,
    Gedmo\Mapping\Annotation as Gedmo,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="text_blocks")
 * @ORM\Entity
 **/
class Model_Textblock extends \CMF\Model\Base
{
    protected static $_fields = array(
    );

    protected static $_icon = 'book';
    protected static $_sortable = true;

    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     **/
    protected $text;

}