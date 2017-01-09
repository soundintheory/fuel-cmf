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

    const TYPE_EXTERNAL = 'External';
    
    /**
     * Returns an instance of the item this url is associated with.
     * @return object The model that owns this url
     */
    public function item()
    {
        if (empty($this->type) || is_null($this->item_id) || !class_exists($this->type)) return null;
        
        $type = $this->type;
        return $type::select('item')->where('item.id = '.$this->item_id)->getQuery()->getResult();
    }
    
    public function urlSlug()
    {
        return $this->slug;
    }
    
    public function display()
    {
        return $this->url;
    }

    public function isExternal()
    {
        return $this->type == static::TYPE_EXTERNAL;
    }

    public function isRedirect()
    {
        return !empty($this->type) && is_numeric($this->type);
    }
    
    public function __toString()
    {
        return \CMF::link(strval($this->url));
    }

    public static function cleanOld()
    {
        $urls = \CMF\Model\URL::select('item')->getQuery()->getResult();
        $deleted = 0;
        
        foreach ($urls as $url)
        {
            if ($url->isExternal() || $url->isRedirect()) {
                continue;
            }
            $item = $url->item();
            if (empty($item)) {
                \D::manager()->remove($url);
                $deleted++;
            }
        }

        \D::manager()->flush();
        return $deleted;
    }

    public $processed = false;
    
    /**
     * @ORM\Column(type="integer", length=11, nullable=true)
     **/
    protected $item_id;

    /**
     * @ORM\Column(type="integer", length=11, nullable=true)
     **/
    protected $parent_id;
    
    /**
     * @ORM\Column(type="string", nullable=true)
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
     * @ORM\ManyToOne(targetEntity="URL", inversedBy="aliases")
     * @ORM\JoinColumn(name="alias_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $alias;

    /**
     * @ORM\OneToMany(targetEntity="URL", mappedBy="alias")
     */
    protected $aliases;
    
    /**
     * @ORM\Column(type="string"))
     **/
    protected $type = '';

}