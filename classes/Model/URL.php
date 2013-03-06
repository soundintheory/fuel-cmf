<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert,
	CMF\Model\Base;

/**
 * @ORM\Entity
 * @ORM\Table(name="urls")
 * @ORM\HasLifecycleCallbacks
 * @Assert\UniqueEntity("url")
 **/
class URL extends Base
{
    protected static $_fields = array(
        'item_id' => array( 'visible' => false ),
        'type' => array( 'visible' => false ),
        'url' => array( 'visible' => false ),
        'prefix' => array( 'visible' => false ),
        'slug' => array(
            'prepend' => 'prefix'
        )
    );
    
    protected static $_has_permissions = false;
    
    /**
     * Returns an instance of the item this url is associated with.
     * @return object The model that owns this url
     */
    public function item()
    {
        if (empty($this->type) || is_null($this->item_id)) return null;
        
        $type = $this->type;
        return $type::select('item')->where('item.id = '.$this->item_id)->getQuery()->getResult();
    }
    
    public function slug()
    {
        return $this->slug;
    }
    
    public function _slug()
    {
        return $this->slug;
    }
    
    public function display()
    {
        return $this->url;
    }
    
    public function __toString()
    {
        return strval($this->url);
    }
    
    /**
     * @ORM\Column(type="integer", length=11, nullable=true)
     **/
    protected $item_id;
    
    /**
     * @ORM\Column(type="string")
     **/
    protected $url;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $slug;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $prefix;
    
    /**
     * @ORM\Column(type="string"))
     **/
    protected $type = '';
	
}