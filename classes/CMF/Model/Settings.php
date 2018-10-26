<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 **/
class Settings extends Base
{
    
    protected static $_fields = array(
        'start_page' => array( 'super' => true, 'after' => 'site_title', 'field' => 'CMF\\Field\\Admin\\StartPage' ),
        'htaccess'  => array( 'visible' => false ),
        'twofa_method' => array('visible' => false),
        'twofa_enabled' => array('title' => 'Enable Two Factor Authentication', 'group' => 'twofactor')
    );

    protected static $_groups = array(
        'main' => array( 'title' => 'Info' ),
        'field_htaccess' => array( 'title' => 'Manual redirects' ),
        'twofactor' => array('title' => 'Two Factor Authentication')
    );
    
    protected static $_singular = 'Settings';
    protected static $_icon = 'cog';
    protected static $_static = true;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $site_title;
    
    /**
     * @ORM\ManyToOne(targetEntity="\CMF\Model\URL")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $start_page;

    /**
     * @ORM\Column(type="htaccess", nullable=true)
     */
    protected $htaccess;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $twofa_enabled;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $twofa_method;
    
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
            // throw new \BadMethodCallException("no field with name '".$field."' exists on '".$this->_metadata()->getName()."'");
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
        $value = $called_class::instance()->get($setting_name, $default_value);

        // Try and fall back to stored config
        if (empty($value) && is_null($default_value)) {
            return \Config::get($setting_name, $default_value);
        }

        return $value;
    }
    
    /** inheritdoc */
    public static function instance()
    {
        $called_class = get_called_class();
        if (!isset($called_class::$instances[$called_class])) {
            $result = $called_class::select('item, start_page')->leftJoin('item.start_page', 'start_page')->setMaxResults(1)->getQuery()->getResult();
            if (count($result) == 0) {
                
                // Create the item if it doesn't exist
                $result = new $called_class();
                $result->blank();
                
                \D::manager()->persist($result);
                \D::manager()->flush();
                $called_class::$instances[$called_class] = $result;
            } else {
                $called_class::$instances[$called_class] = $result[0];
            }
        }
        return $called_class::$instances[$called_class];
    }
    
    public function display()
    {
        return 'General Settings';
    }
	
}
