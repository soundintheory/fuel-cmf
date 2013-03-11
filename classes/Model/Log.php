<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert,
	CMF\Model\Base;

/**
 * @ORM\Entity
 * @ORM\Table(name="logs")
 **/
class Log extends \Doctrine\Fuel\Model
{
    
    public function display()
    {
        return 'LOG DISPLAY';
    }
    
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     **/
    protected $id;
    
    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     **/
    protected $date;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     **/
    protected $user_id;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $user_type;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     **/
    protected $item_id;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $item_type;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $item_label;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $action;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $message;
	
}