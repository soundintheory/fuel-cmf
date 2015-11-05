<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="dev_settings")
 * @ORM\HasLifecycleCallbacks
 **/
class DevSettings extends Base
{
    protected static $_fields = array(

    );
    
    protected static $_singular = 'Settings';
    protected static $_icon = 'cog';
    protected static $_static = true;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $parent_site;

    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $parent_site_api_key;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     **/
    protected $use_canonical = true;
    
    /** inheritdoc */
    public function get($field, $default_value = null)
    {   
        if (property_exists($this, $field)) {
            if (($dotpos = strpos($field, ".")) !== false) {
                $prop = substr($field, $dotpos+1);
                return \Arr::get($this->$field, $prop, $default_value);
            }
            return isset($this->$field) ? $this->$field : $default_value;
        } else if (method_exists($this, $field)) {
            return $this->$field();
        } else {
            return $default_value;
        }
    }
    
    /**
     * Retrieves a setting value. Can either be a property of the settings model, or can use dot notation
     * (using Fuel's Arr::get) if the property is nested inside an array
     * @param string $setting_name
     * @param mixed $default_value If the setting isn't found
     * @return mixed
     */
    public static function getSetting($setting_name, $default_value = null)
    {
        $called_class = get_called_class();
        return $called_class::instance()->get($setting_name, $default_value);
    }

    public function display()
    {
        return 'Development Settings';
    }
	
}
