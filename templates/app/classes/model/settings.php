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
    
}