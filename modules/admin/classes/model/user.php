<?php

namespace Admin;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @Assert\UniqueEntity("username")
 *
 * @package    Admin
 **/
class Model_User extends \CMF\Model\User
{
	
    protected static $_list_fields = array(
        'username',
        'email'
    );
    
    protected static $_icon = 'user';
    
    public function display()
    {
        return $this->username;
    }
	
}