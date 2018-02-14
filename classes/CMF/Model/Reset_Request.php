<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\ORM\Annotation,
    Fuel\Core\Database_Exception,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Gedmo\Mapping\Annotation as Gedmo,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="password_reset_requests")
 *
 * @package    CMF
 * @subpackage Model
 **/
class Reset_Request extends Base
{

    /**
     * @ORM\OneToOne(targetEntity="\CMF\Model\User", mappedBy="reset_request")
     */
    protected $user;

    /**
     * @ORM\Column(type="binary", nullable=false)
     */
    protected $token;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $created_at;

    // Getters

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param mixed $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }


}