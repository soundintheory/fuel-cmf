<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="lang")
 * @ORM\HasLifecycleCallbacks
 * @Assert\UniqueEntity(
 *     fields={"identifier", "language"}
 * )
 **/
class Lang extends Base
{
    protected static $_fields = array(
        'identifier' => array(  ),
        'language' => array( 'visible' => false ),
        'lang' => array( 'title' => 'Translation' ),
        'hash' => array( 'visible' => false ),
    );
    
    protected static $_plural = 'Common Phrases';
    protected static $_singular = 'Common Phrase';
    
    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $identifier;
    
    /**
     * @ORM\Column(type="string", length=10, nullable=false)
     */
    protected $language;
    
    /**
     * @ORM\Column(type="text", nullable=false)
     */
    protected $lang;
    
    /**
     * @ORM\Column(type="string", length=13, nullable=false)
     */
    protected $hash;
	
}