<?php

use Doctrine\ORM\Mapping as ORM,
    Gedmo\Mapping\Annotation as Gedmo,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="picture_blocks")
 * @ORM\Entity
 **/
class Model_PictureTextblock extends \CMF\Model\Base
{
    protected static $_fields = array(
        'image' => array( 'crop'=>array(
            'main'=>array('width'=>400,'height'=>300,'title'=>'main'),
        ) ),
    );

    protected static $_icon = 'book';
    protected static $_sortable = true;

    /**
     * @ORM\Column(type="image", nullable=true)
     **/
    protected $image;

    /**
     * @ORM\Column(type="link", nullable=true)
     **/
    protected $link;
}