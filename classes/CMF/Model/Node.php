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

    protected static $_allowed_parents = null;
    protected static $_allowed_children = null;
    protected static $_disallowed_parents = null;
    protected static $_disallowed_children = null;
    
    protected $root_url = '';

    /**
     * Returns an array of types (class names) that the model can be a child of
     * @return array|null
     */
    public static function allowedParents()
    {
        $called_class = get_called_class();
        return $called_class::$_allowed_parents;
    }

    /**
     * Returns an array of types (class names) that the model can have as children
     * @return array|null
     */
    public static function allowedChildren()
    {
        $called_class = get_called_class();
        return $called_class::$_allowed_children;
    }

    /**
     * Returns an array of types (class names) that the model cannot be a child of
     * @return array|null
     */
    public static function disallowedParents()
    {
        $called_class = get_called_class();
        return $called_class::$_disallowed_parents;
    }

    /**
     * Returns an array of types (class names) that the model cannot have as children
     * @return array|null
     */
    public static function disallowedChildren()
    {
        $called_class = get_called_class();
        return $called_class::$_disallowed_children;
    }
    
    /**
     * Does exactly what it says - results are in array form.
     * @return array Nested result set representing the whole tree.
     */
    public static function getEntireTree()
	{
	    return \D::manager()->getRepository(get_called_class())->childrenHierarchy();
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
        $children = \D::manager()->getRepository(get_class($this))->childrenQueryBuilder($this, $direct, $sortByField, $direction, $includeNode)->select('node.id')->getQuery()->getScalarResult();
        
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
	        
	        $em = \D::manager();
	        $root = new $root_class();
            $root->blank(array('lft', 'lvl', 'rgt', 'root', 'url'));
	        $root->set('is_root', true);
            
	        $em->persist($root);
	        if ($flush === true) {
                $em->flush();
            }
	        
	    }
        
	    return $root;
	}
    
    /** inheritdoc */
    public static function options($filters = array(), $orderBy = array(), $limit = null, $offset = null, $params = null, $allow_html = true, $group_by = null)
    {
        $called_class = get_called_class();
        $cache_id = md5($called_class.serialize($filters).serialize($orderBy).$limit.$offset);
        if (isset($called_class::$_options[$cache_id])) return $called_class::$_options[$cache_id];
        
        $metadata = $called_class::metadata();
        $is_root = ($metadata->name == $metadata->rootEntityName);

        if (!is_null($group_by)) {
            return parent::options($filters, $orderBy, $limit, $offset, $params, $allow_html, $group_by);
        }

        $results = $called_class::select('item')->leftJoin('item.children', 'children')
        ->addSelect('children')
        ->orderBy('item.root, item.lft', 'ASC');

        if ($is_root) $results->where('item.lvl = 1');
        $results = $results->getQuery()->getResult();
        
        return $called_class::$_options[$cache_id] = static::buildTreeOptions($results, $is_root);
    }
    
    protected static function buildTreeOptions($tree, $indent = true)
    {
        $options = array();
        
        foreach ($tree as $model) {
            
            $thumbnail = $model->thumbnail();
            $display = $model->display();
            $options[strval($model->id)] = ($indent === true ? str_repeat(' &#8594;&nbsp; ', $model->lvl-1) : '').' '.($thumbnail !== false ? $thumbnail.' ' : '').(!empty($display) ? $display : '-');
            
            if (isset($model->children) && $model->children instanceof \Doctrine\Common\Collections\Collection) {
                
                $options = $options + static::buildTreeOptions($model->children->toArray());
                
            }
            
        }
        
        return $options;
    }
    
    public function getUrl()
    {
        return ($this->is_root) ? $this->root_url : parent::getUrl();
    }
    
    public function urlSlug()
    {
        return ($this->is_root) ? '' : parent::urlSlug();
    }
    
    /**
     * Creates a url path by getting the url from the parent of the model
     * @override
     */
    public function urlPrefix()
    {
        if (isset($this->parent)) {

            if (property_exists($this, 'url')) {

                $url = $this->url;
                $parent_url = $this->parent->url;

                if (!is_null($parent_url) && !is_null($url)) {

                    $parent_alias = $parent_url->alias;
                    if (!is_null($parent_alias)) {

                        return $parent_alias->prefix;

                    }

                }

            }

            $parent_url = $this->parent->getUrl();
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
                \D::manager()->flush();
                
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