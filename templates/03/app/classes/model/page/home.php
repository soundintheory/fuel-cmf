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

    protected static $_tabs = array(
        'main' => 'Main',
        'meta'	=> 'Meta Data (SEO)'
    );
    
    protected static $_static = true;
    protected static $_icon = 'home';

    /**
     * address setting Mapping
     *
     * @ORM\ManyToMany(targetEntity="Model_Layout3slide",orphanRemoval=true)
     * @ORM\JoinTable(name="layout3_slides_homepage_mapping")
     */
    protected $layout3slides;

    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $first_section_title;

    /**
     * address setting Mapping
     *
     * @ORM\ManyToMany(targetEntity="Model_PictureTextblock",orphanRemoval=true)
     * @ORM\JoinTable(name="first_section_homepage_mapping")
     */
    protected $first_section;

    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $second_section_title;

    /**
     * address setting Mapping
     *
     * @ORM\ManyToMany(targetEntity="Model_PictureTextblock",orphanRemoval=true)
     * @ORM\JoinTable(name="second_section_homepage_mapping")
     */
    protected $second_section;

    /**
     * URL for homepage will always be empty as long as this is overriden
     */
    public function urlSlug()
    {
    	return '';
    }
	
}