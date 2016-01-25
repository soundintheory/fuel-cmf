<?php

use Doctrine\ORM\Mapping as ORM,
    Gedmo\Mapping\Annotation as Gedmo,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="layout_4_slides")
 * @ORM\Entity
 **/
class Model_Layout4slide extends \CMF\Model\Base
{

    protected static $_fields = array(
        'image' => array( 'crop'=>array(
            'main'=>array('width'=>1225,'height'=>400,'title'=>'main')
        ) ),
    );

    protected static $_icon = 'picture';
    protected static $_sortable = true;

    /**
     * @ORM\Column(type="image", nullable=true)
     **/
    protected $image;
}