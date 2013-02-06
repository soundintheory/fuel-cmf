<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 **/
class Page extends Base
{
    protected static $_fields = array(
        'visible' => array( 'visible' => true ),
        'url' => array( 'after' => 'menu_title' ),
        'menu_title' => array( 'after' => 'title' ),
        'title' => array( 'group' => 'title', 'after' => 'visible' ),
        'html_title' => array( 'group' => 'meta' ),
        'meta_desc' => array( 'title' => 'Meta description', 'after' => 'html_title' ),
        'content' => array( 'widget' => true )
    );
    
    protected static $_list_fields = array('title');
    
    protected static $_groups = array(
        'title' => array( 'title' => 'Title & URLS', 'icon' => 'tag' ),
        'meta' => array( 'title' => 'Meta Data (SEO)', 'icon' => 'globe' ),
        'main' => array( 'title' => 'Info' )
    );
    
    protected static $_default_group = 'main';
    protected static $_slug_fields = array('menu_title');
    
    public function display()
    {
        return strval($this->title);
    }
    
    /** inheritdoc */
    public function blank($ignore_fields = null)
    {
        parent::blank($ignore_fields);
        $this_class = get_class($this);
        $this->title = $this->menu_title = $this->html_title = $this_class::singular();
    }
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $menu_title;
    
    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     **/
    protected $title;
    
    /**
     * @ORM\OneToOne(targetEntity="CMF\Model\URL", orphanRemoval=true)
     **/
    protected $url;
    
    /**
     * @ORM\Column(type="string", nullable=true))
     **/
    protected $html_title;
    
    /**
     * @ORM\Column(type="string", nullable=true))
     **/
    protected $meta_desc;
	
	/**
     * @ORM\Column(type="richtext", nullable=true))
     **/
    protected $content;
	
}