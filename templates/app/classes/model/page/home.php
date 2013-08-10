<?php

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="homepage")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 **/
class Model_Page_Home extends Model_Page_Base
{
    protected static $_fields = array(
    	'url' => array( 'visible' => false ),
    	'visible' => array( 'visible' => false ),
    	'title' => array( 'after' => null, 'before' => 'html_title' ),
        'content' => array(  )
    );
    
    protected static $_static = true;
    protected static $_icon = 'home';
    
    /**
     * URL for homepage will always be empty as long as this is overriden
     */
    public function urlSlug()
    {
    	return '';
    }
	
}