<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 **/
class PageNode extends Node
{
    protected static $_fields = array(
        'visible' => array( 'visible' => true, 'group' => 'title' ),
        'url' => array( 'after' => 'menu_title' ),
        'menu_title' => array( 'after' => 'title', 'template' => '{{ model.title }}' ),
        'title' => array( 'group' => 'title', 'after' => 'visible' ),
        'html_title' => array( 'group' => 'meta', 'template' => '{{ model.title }}' ),
        'meta_desc' => array( 'title' => 'Meta description', 'after' => 'html_title', 'field' => 'CMF\\Field\\Textarea' ),
        'content' => array( 'widget' => true ),
        'url_alias' => array( 'visible' => false ),
    	'extra_meta' => array('fields' => array(
    				'og:title'=>array('type'=>'string'),
    				'og:site_name'=>array('type'=>'string'),
    				'og:url'=>array('type'=>'string'),
    				'og:description'=>array('type'=>'text'),
    				'og:image'=>array('type'=>'string'),
    		),
    				'dynamic'=>true,
    		)
    );
    
    protected static $_list_fields = array('title');
    
    protected static $_groups = array(
        'title' => array( 'title' => 'Title & URL', 'icon' => 'tag' ),
        'meta' => array( 'title' => 'Meta Data (SEO)', 'icon' => 'globe' ,'tab'=>'meta'),
    	'field_extra_meta' => array ('tab'=>'meta'),
        'main' => array( 'title' => 'Info' )
    );
    
    protected static $_tabs = array(
    		'main' => 'Main',
    		'meta'	=> 'Meta Data (SEO)'
    );
    
    protected static $_default_group = 'main';
    protected static $_slug_fields = array('title');
    
    public function display()
    {
        return strval($this->menu_title);
    }
    
    /** inheritdoc */
    public function blank($ignore_fields = null)
    {
        parent::blank($ignore_fields);
        if (!$this->menu_title) $this->menu_title = $this->title;
        if (!$this->html_title) $this->html_title = $this->title;
    }
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $menu_title;
    
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
    
    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $parent;
    
    /**
     * @ORM\OneToMany(targetEntity="Page", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    protected $children;
    
    /**
     * @ORM\Column(type="object", nullable=true))
     **/
    protected $extra_meta = array();
	
}