<?php

use Doctrine\ORM\Mapping as ORM,
    Gedmo\Mapping\Annotation as Gedmo,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="layout_3_slides")
 * @ORM\Entity
 **/
class Model_Layout3slide extends \CMF\Model\Base
{

    protected static $_fields = array(
        'image' => array( 'crop'=>array(
            'main'=>array('width'=>640,'height'=>360,'title'=>'main')
        ) ),
    );

    protected static $_icon = 'picture';
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
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $cta_text;

    /**
     * @ORM\Column(type="link", nullable=true)
     **/
    protected $cta_link;
}