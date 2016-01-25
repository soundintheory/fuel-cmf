<?php

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="contact")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 **/
class Model_Page_Contact extends Model_Page_Base
{
    protected static $_fields = array(

    );
    
    protected static $_static = true;
    protected static $_icon = 'pencil';

	
}