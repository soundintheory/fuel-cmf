<?php

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="generic_pages")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 **/
class Model_Page_Generic extends Model_Page_Base
{
    protected static $_fields = array(
        'content' => array(  ),
        'title' => array(  )
    );
    
    protected static $_icon = 'file';
	
}