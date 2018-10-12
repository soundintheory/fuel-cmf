<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="roles")
 *
 * @package    CMF
 * @subpackage Model
 **/
class Role extends Base
{
    protected static $_icon = 'tags';
    
    protected static $_list_fields = array(
        'name',
        'description'
    );
    
    protected static $_lang_enabled = false;
	
    public function display()
    {
        return $this->name;
    }
	
    /**
     * @ORM\Column(type="string", length=20)
     **/
    protected $name;
    
    /**
     * @ORM\Column(type="string", length=100)
     **/
    protected $description;
    
	/**
     * @ORM\ManyToMany(targetEntity="\CMF\Model\Permission", inversedBy="roles")
     * @ORM\JoinTable(name="role_permission")
     **/
    protected $permissions;    
}