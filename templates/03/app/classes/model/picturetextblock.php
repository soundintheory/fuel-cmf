<?php

use Doctrine\ORM\Mapping as ORM,
    Gedmo\Mapping\Annotation as Gedmo,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="picture_text_blocks")
 * @ORM\Entity
 **/
class Model_PictureTextblock extends \CMF\Model\Base
{
    protected static $_fields = array(
        'image' => array( 'crop'=>array(
            'main'=>array('width'=>400,'height'=>300,'title'=>'main'),
            'smaller'=>array('width'=>400,'height'=>250,'title'=>'smaller'),
        ) ),
    );

    protected static $_icon = 'book';
    protected static $_sortable = true;

    /**
     * @ORM\Column(type="image", nullable=true)
     **/
    protected $image;

    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     **/
    protected $text;

    /**
     * @ORM\Column(type="link", nullable=true)
     **/
    protected $link;
}