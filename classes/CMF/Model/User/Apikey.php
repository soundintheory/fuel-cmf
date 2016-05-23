<?php

namespace CMF\Model\User;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	CMF\Model\Base,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="api_keys")
 * @ORM\Entity
 **/
class Apikey extends Base
{
    protected static $_fields = array(
        'user_type' => array( 'visible' => false ),
        'scope' => array( 'visible' => false ),
        'user_id' => array( 'visible' => false ),
        'access_token' => array(),
        'expires_at' => array(
            'default_offset' => '+1000 year',
            'list_format' => 'M jS H:i Y'
        )
    );

    protected static $_list_fields = array(
        'access_token',
        'expires_at'
    );

    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $user_type = 'Admin\\Model_User';

    /**
     * @ORM\Column(type="integer", nullable=true)
     **/
    protected $user_id;

    /**
     * @ORM\Column(type="random_key")
     */
    protected $access_token;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $scope = 'api';

    /**
     * @ORM\Column(type="datetime")
     **/
    protected $expires_at;
}