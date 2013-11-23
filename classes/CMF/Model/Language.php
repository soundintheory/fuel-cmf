<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="languages")
 * @ORM\HasLifecycleCallbacks
 **/
class Language extends Base
{
    protected static $_fields = array(
        'visible' => array(
            'visible' => true,
            'title' => 'Active',
            'description' => 'Whether this language is visible on the live website'
        ),
        'code' => array(
            'title' => 'Language'
        ),
        'top_level_domain' => array(
            'visible' => false
        )
    );
    
    protected static $_list_fields = array(
        'code',
        'visible'
    );
    
    protected static $_lang_enabled = false;
    
    protected static $_icon = 'globe';
    
    protected static $_sortable = true;
    
    public function display()
    {
        return \Lang::get("languages.{$this->code}");
    }
    
    public function validate($groups = null, $fields = null, $exclude_fields = null, $exclude_types = null)
    {
        if (parent::validate($groups, $fields, $exclude_fields)) {
            
            $qb = static::select('item.id, item.code')->where("item.code = '{$this->code}'");
            if (isset($this->id)) $qb->andWhere("item.id != {$this->id}");
            
            // Throw an error if there are other languages set to this
            if (count($qb->getQuery()->getArrayResult()) > 0) {
                $this->addErrorForField('code', 'This language has already been added!');
            }
            
            return count($this->errors) === 0;
            
        }
        return false;
    }
    
    /**
     * @ORM\Column(type="language")
     **/
    protected $code;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $top_level_domain;
	
}