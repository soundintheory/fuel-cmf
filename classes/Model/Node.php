<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
    Gedmo\Mapping\Annotation as Gedmo,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
class Node extends Base
{
    
    protected static $_fields = array(
        'is_root' => array( 'visible' => false ),
        'lft' => array( 'visible' => false ),
        'rgt' => array( 'visible' => false ),
        'lvl' => array( 'visible' => false ),
        'root' => array( 'visible' => false ),
        'parent' => array( 'visible' => false ),
        'children' => array( 'visible' => false )
    );
    
    protected static $_slug_fields = array('title');
    
    /**
     * Does exactly what it says - results are in array form.
     * @return array Nested result set representing the whole tree.
     */
    public static function getEntireTree()
	{
	    return \DoctrineFuel::manager()->getRepository(get_called_class())->childrenHierarchy();
	}
    
    /**
     * Returns a flat array of ids for all children of the model. Useful for filtering in queries.
     * @param  boolean $direct Whether to only select direct children of this model
     * @param  string|null  $sortByField Which field to sort by
     * @param  string  $direction Sort direction
     * @param  boolean $includeNode Whether to include this model's id in the results
     * @return array Array of ids
     */
    public function getChildrenIds($direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $children = \DoctrineFuel::manager()->getRepository(get_class($this))->childrenQueryBuilder($this, $direct, $sortByField, $direction, $includeNode)->select('node.id')->getQuery()->getScalarResult();
        
        return array_map(function($item) {
            return $item['id'];
        }, $children);
    }
	
	/**
	 * @ORM\PrePersist
     * Event that creates a root node if one doesn't exist
     */
	public function createRootNode()
	{
	    if ($this->is_root) return;
	    
	    $self_class = get_class($this);
	    $root = $self_class::getRootNode();
	    
	    if (is_null($this->parent)) {
	        $this->parent = $root;
	    }
	}
	
    /**
     * Retrieves the root node for this tree. Creates one if it doesn't exist
     * @param  boolean $flush Whether to flush the entity manager when creating the root item
     * @return object The root node
     */
	public static function getRootNode($flush = false)
	{
        $called_class = get_called_class();
	    $metadata = $called_class::metadata();
        
	    $root_class = $metadata->rootEntityName;
	    $root = $root_class::findOneBy(array( 'is_root' => true ));
	    
	    if (is_null($root)) {
	        
	        $em = \DoctrineFuel::manager();
	        $root = new $root_class();
            $root->blank(array('lft', 'lvl', 'rgt', 'root', 'url'));
	        $root->set('is_root', true);
            
	        $em->persist($root);
	        if ($flush === true) {
                //print("Created root node for ".$root_class." and flushing\n");
                $em->flush();
            } else {
                //print("Created root node for ".$root_class."\n");
            }
	        
	    }
        
	    return $root;
	}
    
    /** inheritdoc */
    public static function options(array $filters = array(), array $orderBy = array(), $limit = null, $offset = null)
    {
        $called_class = get_called_class();
        $cache_id = md5($called_class.serialize($filters).serialize($orderBy).$limit.$offset);
        if (isset($called_class::$_options[$cache_id])) return $called_class::$_options[$cache_id];
        
        $results = $called_class::select('item')->leftJoin('item.children', 'children')
        ->addSelect('children')
        ->where("item.lvl = 1")
        ->orderBy('item.root, item.lft', 'ASC')
        ->getQuery()->getResult();
        
        return $called_class::$_options[$cache_id] = static::buildTreeOptions($results);
    }
    
    protected static function buildTreeOptions($tree)
    {
        $options = array();
        
        foreach ($tree as $model) {
            
            $options[strval($model->id)] = str_repeat(' &#8594;&nbsp; ', $model->lvl-1).' '.$model->display();
            
            if (isset($model->children) && $model->children instanceof \Doctrine\Common\Collections\Collection) {
                
                $options = $options + static::buildTreeOptions($model->children->toArray());
                
            }
            
        }
        
        return $options;
    }
    
    public function url()
    {
        return ($this->is_root) ? '' : parent::url();
    }
    
    public function slug()
    {
        return ($this->is_root) ? '' : parent::slug();
    }
    
    /**
     * Creates a url path by getting the url from the parent of the model
     * @override
     */
    public function urlPrefix()
    {
        if (isset($this->parent)) {
            $parent_url = $this->parent->url();
            return $parent_url . (($parent_url == '/') ? '' : '/');
        }
        return '/';
    }
    
    /** inheritdoc */
    public static function sortable()
    {
        return false;
    }
    
    /** inheritdoc */
    public static function sortGroup()
    {
        return null;
    }
    
    public function display()
    {
        return strval($this->title);
    }
    
    /** inheritdoc */
    public function blank($ignore_fields = null)
    {
        parent::blank($ignore_fields);
        $this_class = get_class($this);
        $this->title = $this_class::singular();
    }
    
    /** inheritdoc */
    public static function instance()
    {
        $called_class = get_called_class();
        if (!isset($called_class::$instances[$called_class])) {
            $result = $called_class::select('item')->setMaxResults(1)->getQuery()->getResult();
            if (count($result) == 0) {
                $root_node = $called_class::getRootNode(true);
                
                // Create the item if it doesn't exist
                $result = new $called_class();
                $result->blank();
                
                $called_class::repository()->persistAsFirstChildOf($result, $root_node);
                \DoctrineFuel::manager()->flush();
                
                $called_class::$instances[$called_class] = $result;
            } else {
                $called_class::$instances[$called_class] = $result[0];
            }
        }
        return $called_class::$instances[$called_class];
    }
	
	/**
     * @ORM\Column(type="boolean")
     **/
    protected $is_root = false;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank
     **/
    protected $title;
    
    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    protected $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    protected $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    protected $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    protected $root;
    
    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Node", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $parent;
    
    /**
     * @ORM\OneToMany(targetEntity="Node", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    protected $children;
	
}

?>