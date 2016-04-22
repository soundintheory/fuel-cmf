<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert,
	CMF\Model\Base;

/**
 * @ORM\Entity
 * @ORM\Table(name="_files")
 **/
class File extends \CMF\Doctrine\Model implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     **/
    protected $id;

    /**
     * @ORM\Column(type="string")
     **/
    protected $path;
    
    /**
     * @ORM\Column(type="string")
     **/
    protected $url;

    /**
     * @ORM\Column(type="string")
     */
    protected $storage;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $type;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $field;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $params;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $parent;

    /**
     * @ORM\Column(type="datetime")
     **/
    protected $created_at;

    /**
     * @ORM\Column(type="datetime")
     **/
    protected $updated_at;

    public function display()
    {
        return $this->url;
    }
    
    public function __toString()
    {
        return $this->path;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}