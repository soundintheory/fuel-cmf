<?php

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="homepage")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 **/
class Model_Page_Homepage extends Model_Page_Page
{
    protected static $_fields = array(
        'content' => array(  ),
        'title' => array(  )
    );
    
    protected static $_static = true;
    protected static $_icon = 'home';
	
}