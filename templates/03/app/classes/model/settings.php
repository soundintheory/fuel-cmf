<?php

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="settings")
 * @ORM\Entity
 **/
class Model_Settings extends \CMF\Model\Settings
{
	protected static $_fields = array(
			'email' => array( 'group' => 'email' ),
			'email_success_message' => array( 'group' => 'email' ),
			'email_fail_message' => array( 'group' => 'email' ),
			'facebook' => array( 'group' => 'social' ),
			'twitter' => array( 'group' => 'social' ),
			'pinterest' => array( 'group' => 'social' ),
			'googleplus' => array( 'group' => 'social' ),
			'logo' => array( 'after' => 'start_page','group'=>'info' ),
			'phone_number' => array( 'after' => 'logo','group'=>'info' ),
			'start_page' => array( 'group' => 'info' ),
			'site_title' => array( 'group' => 'info' )
	);

	protected static $_groups = array(
			'info' => array( 'title' => 'Info', 'icon' => 'tag' ),
			'email' => array( 'title' => 'Form Settings', 'icon' => 'tag','after'=>'info' ),
			'social' => array( 'title' => 'Social Media', 'icon' => 'tag','after'=>'email' )
	);

	/**
	 * @ORM\Column(type="image", nullable=true)
	 **/
	protected $logo;

	/**
	 * address setting Mapping
	 *
	 * @ORM\ManyToMany(targetEntity="Model_Addressblock",orphanRemoval=true)
	 * @ORM\JoinTable(name="address_block_mapping")
	 */
	protected $addresses;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 **/
	protected $phone_number;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 **/
	protected $email;

	/**
	 * @ORM\Column(type="link", nullable=true)
	 **/
	protected $facebook;
	/**
	 * @ORM\Column(type="link", nullable=true)
	 **/
	protected $twitter;
	/**
	 * @ORM\Column(type="link", nullable=true)
	 **/
	protected $pinterest;

	/**
	 * @ORM\Column(type="link", nullable=true)
	 **/
	protected $googleplus;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 **/
	protected $email_success_message;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 **/
	protected $email_fail_message;
}