<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="permissions")
 * 
 * @package    CMF
 * @subpackage Model
 **/
class Permission extends Base
{
    protected static $_list_fields = array('resource', 'action');
    
    protected static $_icon = 'key';
    
	/**
	 * @ORM\ManyToMany(targetEntity="\CMF\Model\Role", mappedBy="permissions")
	 **/
    protected $roles;

	/**
     * @ORM\Column(type="string", length=20)
     **/
    protected $name;

	/**
     * @ORM\Column(type="string", length=30)
     **/
    protected $resource;

	/**
     * @ORM\Column(type="string", length=30)
     **/
    protected $action;

	/**
     * @ORM\Column(type="string", length=100)
     **/
    protected $description;
    
    public function display()
    {
        return $this->name;
    }
}