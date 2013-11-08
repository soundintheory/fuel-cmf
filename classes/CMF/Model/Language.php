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
        'code' => array(
            'title' => 'Language'
        )
    );
    
    protected static $_list_fields = array(
        'code'
    );
    
    protected static $_icon = 'globe';
    
    protected static $_sortable = true;
    
    public function display()
    {
        return __("languages.{$this->code}");
    }
    
    public function validate($groups = null, $fields = null, $exclude_fields = null, $exclude_types = null)
    {
        if (parent::validate($groups, $fields, $exclude_fields)) {
            
            $qb = static::select('item.code')->where("item.code = '{$this->code}'");
            if (isset($this->id)) $qb->where("item.id != {$this->id}");
            
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
	
}