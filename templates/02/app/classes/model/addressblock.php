<?php

use Doctrine\ORM\Mapping as ORM,
    Gedmo\Mapping\Annotation as Gedmo,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="address_blocks")
 * @ORM\Entity
 **/
class Model_Addressblock extends \CMF\Model\Base
{

    protected static $_icon = 'book';
    protected static $_sortable = true;

    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     **/
    protected $address;

    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $number;

    /**
     * @ORM\Column(type="latlng", nullable=true)
     **/
    protected $map_position;

    public function display(){
        return $this->name;
    }
}