<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo;

/**
 * @package    CMF
 * @subpackage Model
 **/
#[ORM\Entity]
#[ORM\Table(name: 'permissions')]
class Permission extends Base
{
    protected static $_list_fields = array('resource', 'action');
    
    protected static $_icon = 'key';
    
    protected static $_lang_enabled = false;
    
	#[ORM\ManyToMany(targetEntity: \CMF\Model\Role::class, mappedBy: 'permissions')]
    protected $roles;
    
    #[ORM\Column(type: 'integer', nullable: true)]
    protected $item_id;

	#[ORM\Column(type: 'string')]
    protected $resource;

	#[ORM\Column(type: 'string')]
    protected $action;

	#[ORM\Column(type: 'string', nullable: true)]
    protected $description;
    
    public function display()
    {
        return $this->name;
    }
}