<?php

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	CMF\Model\PageNode,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="pages")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="_type", type="string")
 **/
class Model_Page_Page extends PageNode
{
    protected static $_fields = array(
        'url' => array(),
        'title' => array(),
        'visible' => array( 'visible' => true )
    );
    
    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Model_Page_Page", inversedBy="children")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $parent;
    
    /**
     * @ORM\OneToMany(targetEntity="Model_Page_Page", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    protected $children;
	
}