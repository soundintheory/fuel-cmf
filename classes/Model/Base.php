<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\DBAL\LockMode,
	Gedmo\Mapping\Annotation as Gedmo,
	CMF\Model\URL;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 **/
class Base extends \Doctrine\Fuel\Model
{
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     **/
    protected $id;
    
    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     * @var datetime
     **/
    protected $created_at;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     * @var datetime
     **/
    protected $updated_at;
    
    /**
     * @ORM\Column(type="object", nullable=true)
     **/
    protected $settings;
    
    /**
     * @ORM\Column(type="boolean", nullable=true))
     **/
    protected $visible = true;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     **/
    protected $pos = 0;
    
    /**
     * Associative array containing settings for all the model's fields - tells the
     * admin module how to display the edit form.
     * 
     * This is inherited from any parent classes. The array will be merged.
     * 
     * @var array
     */
    protected static $_fields = array(
    	'id' => array( 'visible' => false ),
    	'created_at' => array( 'readonly' => true, 'visible' => false ),
    	'updated_at' => array( 'readonly' => true, 'visible' => false ),
        'visible' => array( 'visible' => false ),
        'settings' => array( 'visible' => false ),
        'pos' => array( 'visible' => false )
    );
    
    /**
     * Fields to search on this model when performing full text searches.
     * @var array
     */
    protected static $_search = array();
    
    /**
     * Associative array containing the group settings for the form. Fields should reference these
     * by key in their settings, eg. array( 'group' => 'main' ). The value is not inherited.
     * @var array
     */
    protected static $_groups = array(
        'main' => array( 'title' => 'Info' )
    );
    
    /**
     * Associative array containing the tab ids => names for the form. Groups should reference these by
     * key in their settings, eg. array( 'tab' => 'main' ). The value is not inherited.
     * @var array
     */
    protected static $_tabs = array(
        'main' => 'Main'
    );
    
    /**
     * TODO: Actions functionality
     * @var array
     */
    protected static $_actions = array();
    
    /**
     * If a field does not specify a group or is not configured to go before or after another 
     * field, this group will be used
     * @var integer
     */
    protected static $_default_group = 'main';
    
    /**
     * If a group does not specify a tab or is not configured to go before or after another 
     * group, this tab will be used
     * @var integer
     */
    protected static $_default_tab = 'main';
    
    /**
     * Fields that should appear in the admin list for the model.
     * 
     * This is not inherited from parent classes. When defining a new list order for 
     * child classes, the whole lot must be redeclared.
     * 
     * @var array
     */
    protected static $_list_fields = array();
    
    /**
     * The plural verbose name for the model (eg. Categories)
     * @see \CMF\Model\Base::plural()
     * @var string
     */
    protected static $_plural = null;
    
    /**
     * The singular verbose name for the model (eg. Category)
     * @see \CMF\Model\Base::singular()
     * @var string
     */
    protected static $_singular = null;
    
    /**
     * Whether the model is sortable in the admin interface. Uses the $pos property.
     * @see \CMF\Model\Base::sortable()
     * @var string
     */
    protected static $_sortable = false;
    
    /**
     * A field to group by when sorting
     * @see \CMF\Model\Base::sortGroup()
     * @var string
     */
    protected static $_sort_group = null;
    
    /**
     * Whether the model if static. Static means there will only ever be one record,
     * that cannot be removed. Use it for one-off pages, like a homepage model.
     * @var boolean
     */
    protected static $_static = false;
    
    /**
     * @see CMF\Model\Base::template()
     * @var string
     */
    protected static $_template = null;
    
    /**
     * This means that noone apart from super users will be able to create or remove entries for the model.
     * Useful when using semi-hardcoded database items.
     * @var string
     */
    protected static $_superlock = false;
    
    /**
     * A bit like Doctrine's MappedSuperclass, when set to true tells the system that this model was never intended
     * to be created or edited, so it gets left out of 'select a type...' lists and so on
     * @var boolean
     */
    protected static $_superclass = false;
    
    /**
     * The icon to use for the model in the admin site.
     * @link http://fortawesome.github.com/Font-Awesome/ See the options
     * @see CMF\Model\Base::icon()
     * @var string
     */
    protected static $_icon = 'file';
    
    /**
     * Tells the system which fields to use when generating the model's slug
     * @var array
     */
    protected static $_slug_fields = array('id');
    
    /**
     * Whether or not permissions can be set for this model
     * @see CMF\Model\Base::hasPermissions()
     * @var string
     */
    protected static $_has_permissions = true;
    
    /**
     * Stores the model's options as a form of cache
     * @var array|null
     */
    protected static $_options = array();
    
    /**
     * Stores the instance of this class - used if the model is static
     * @var object
     */
    protected static $instances = array();
    
    /**
     * Stores this model's field settings as a form of cache
     * @see CMF\Model\Base::fieldSettings()
     * @var array
     */
    protected $_field_settings = null;
    
    /**
     * The URL-friendly identifier for the model. Uses the static slug_fields property to generate it.
     * @see  CMF\Model\Base::$_slug_fields
     * @return string
     */
    public function slug()
    {
        $class_name = get_class($this);
        $self = $this;
        $values = array_map(function($prop) use($self) {
            return $self->$prop;
        }, $class_name::$_slug_fields);
        return \CMF::slug(implode(' ', $values));
    }
    
    /**
     * Returns a path that should prefix the model's slug to form the URL. Default is '/'.
     * 
     * @return string
     */
    public function urlPrefix()
    {
        return '/';
    }
    
    /**
     * The full URL for the model. By default it's a combination of urlPrefix() and slug().
     * 
     * @return string the URL
     */
    public function url()
    {
        return $this->urlPrefix().$this->slug();
    }
    
    /**
     * The folder name under which this model's controller, viewmodel and template
     * should be categorised. Default is 'website'
     * 
     * eg. the controller will go in 'app/classes/controller/website/', the view
     * will go in 'app/classes/view/website/'and the template will go in
     * 'app/views/website/
     * 
     * @return string The group name
     */
    public static function group()
	{
	    return 'website';
	}
    
    /**
     * The name of the template the model will try to render to. By default it is automatically generated
     * from the de-namespaced (and lower cased) class name. eg. 'Model_Image' would be 'image.twig'
     * 
     * If the static property $template is not null, that value will be used instead
     * 
     * @return string The template name
     */
    public static function template()
	{
        $called_class = get_called_class();
        if ($called_class::$_template !== null) return $called_class::group().'/'.$called_class::$_template;
	    $called_class::$_template = str_replace(array("model_", "_"), array("", "/"), strtolower(\Inflector::denamespace(get_called_class()))).'.twig';
        return $called_class::group().'/'.$called_class::$_template;
	}
    
    /**
     * Passes all values through the process() method on any configured fields 
     * before setting them to the model.
     * 
     * @see \CMF\Field\Base::process()
     * @inheritdoc
     */
    public function populate($data, $overwrite=true)
    {
        $fields = $this->fieldSettings();
        parent::populate($data, $overwrite);
        
        foreach ($fields as $field_name => $field) {
            
            $field_class = $field['field'];
            if (!isset($data[$field_name]) && $field_class::$always_process !== true) continue;
            $this->$field_name = $field_class::process($this->$field_name, $field, $this);
            
        }
        
    }
    
    /**
     * @inheritdoc
     * If there are any uploaded files, this will save them to their final location
     */
    public function validate($groups = null, $fields = null, $exclude_fields = null, $exclude_types = null)
    {
        if (parent::validate($groups, $fields, $exclude_fields)) {
            
            $class_name = get_class($this);
            $field_settings = \Admin::getFieldSettings($class_name);
            $user_field_settings = $this->settings();
            $field_settings = \Arr::merge($field_settings, $user_field_settings);
            
            foreach ($field_settings as $field_name => $field) {
                
                if ($field_name == 'id' || ($fields !== null && !in_array($field_name, $fields)) || ($exclude_fields !== null && in_array($field_name, $exclude_fields)))
                    continue;
                
                $value = $this->get($field_name);
                $field_class = $field['field'];
                $field_class::validate($value, $field, $this);
                
            }
            
            return count($this->errors) === 0;
            
        }
        return false;
    }
    
    /**
     * This will return the specified field on the model after having been passed through the CMF field class associated with it.
     * It's a bit heavy because it has to grab the field settings etc, so this is an optional method
     * 
     * @return mixed
     */
    public function field($field_name, $extra_settings = null, $field_override = null)
    {
        if (!property_exists($this, $field_name)) return null;
        $field_settings = $this->fieldSettings();
        $field_settings = \Arr::get($field_settings, $field_name);
        if ($extra_settings !== null) $field_settings = \Arr::merge($field_settings, $extra_settings);
        $field_class = ($field_override !== null) ? $field_override : $field_settings['field'];
        return $field_class::getValue($this->$field_name, $field_class::settings($field_settings), $this);
    }
    
    /**
     * Gets the field settings for this model, merges in any database ones and stores the result for later.
     * @return array
     */
    public function fieldSettings()
    {
        if ($this->_field_settings !== null) return $this->_field_settings;
        
        $class_name = get_class($this);
        $fields = \Admin::getFieldSettings($class_name);
        $field_settings = $this->settings();
        return $this->_field_settings = \Arr::merge($fields, $field_settings);
    }
    
    /**
     * Checks the $changed property at the 'PreFlush' stage and if it is true, updates the
     * $updated_at property. This means that even if only relations of the model have been 
     * changed through it's setter methods, the model will still register as updated.
     * 
     * @ORM\PreFlush
     * @return void
     */
    public function preFlush()
    {
        if ($this->changed) {
            // This will force there to be a changeset, even if it was only associations that were updated.
            $this->updated_at = new \Datetime();
        }
    }
    
    /**
     * @see \CMF\Model\Base::$_singular
     * @return string The singular verbose name for the model (eg. Category)
     */
    public static function singular()
    {
        $called_class = get_called_class();
        if ($called_class::$_singular !== null) return $called_class::$_singular;
        $metadata = $called_class::metadata();
        return \Inflector::singularize(\Inflector::humanize($metadata->table['name']));
    }
    
    /**
     * @see \CMF\Model\Base::$_plural
     * @return string The plural verbose name for the model (eg. Categories)
     */
    public static function plural()
    {
        $called_class = get_called_class();
        if ($called_class::$_plural !== null) return $called_class::$_plural;
        $metadata = $called_class::metadata();
        return \Inflector::pluralize(\Inflector::humanize($metadata->table['name']));
    }
    
    /**
     * @see \CMF\Model\Base::$_fields
     * @return array
     */
    public static function fields()
    {
        $called_class = get_called_class();
        $parent_class = get_parent_class($called_class);
        
        if ($parent_class !== false && method_exists($parent_class, 'fields'))
            return \Arr::merge($parent_class::fields(), $called_class::$_fields);
        
        return $called_class::$_fields;
    }
    
    /**
     * @see \CMF\Model\Base::$_list_fields
     * @return array
     */
    public static function listFields()
    {
        $called_class = get_called_class();
        return $called_class::$_list_fields;
    }
    
    /**
     * @see \CMF\Model\Base::$_slug_fields
     * @return array
     */
    public static function slugFields()
    {
        $called_class = get_called_class();
        return $called_class::$_slug_fields;
    }
    
    /**
     * @see \CMF\Model\Base::$_sortable
     * @return array
     */
    public static function sortable()
    {
        $called_class = get_called_class();
        return $called_class::$_sortable;
    }
    
    /**
     * @see \CMF\Model\Base::$_sort_group
     * @return array
     */
    public static function sortGroup()
    {
        $called_class = get_called_class();
        return $called_class::$_sort_group;
    }
    
    public function sortGroupId($value=null)
    {
        if (is_null($value)) {
            $sort_group = static::sortGroup();
            if (is_null($sort_group) || !property_exists($this, $sort_group)) return '__ungrouped__';
            $value = $this->get($sort_group);
        }
        
        $identifier = $value;
        
        // If the group is an object or array, get a string identifier
        if($value instanceof \DateTime) {
            $identifier = $value->format('c');
        } elseif ($value instanceof \Doctrine\Fuel\Model) {
            $id = $value->get('id');
            $identifier = (!is_null($id)) ? $id : 'newitem';
        } elseif (is_array($value)) {
            $identifier = serialize($value);
        } elseif (is_object($value)) {
            $identifier = get_class($value).spl_object_hash($value);
        }
        
        // Force it to be a string
        return strval($identifier);
    }
    
    /**
     * @see \CMF\Model\Base::$_search
     * @return array
     */
    public static function search()
    {
        $called_class = get_called_class();
        return $called_class::$_search;
    }
    
    /**
     * @see \CMF\Model\Base::$_static
     * @return bool
     */
    public static function _static()
    {
        $called_class = get_called_class();
        return $called_class::$_static;
    }
    
    /**
     * @see \CMF\Model\Base::$_icon
     * @return string
     */
    public static function icon()
    {
        $called_class = get_called_class();
        return $called_class::$_icon;
    }
    
    /**
     * @see \CMF\Model\Base::$_has_permissions
     * @return string
     */
    public static function hasPermissions()
    {
        $called_class = get_called_class();
        return $called_class::$_has_permissions;
    }
    
    /**
     * @see \CMF\Model\Base::$_groups
     * @return string
     */
    public static function groups()
    {
        $called_class = get_called_class();
        if (empty($called_class::$_groups)) return $called_class::$_groups = array( 'title' => 'Info' );
        return $called_class::$_groups;
    }
    
    /**
     * @see \CMF\Model\Base::$_tabs
     * @return string
     */
    public static function tabs()
    {
        $called_class = get_called_class();
        if (empty($called_class::$_tabs)) return $called_class::$_tabs = array('Main');
        return $called_class::$_tabs;
    }
    
    /**
     * @see \CMF\Model\Base::$_default_group
     * @return string
     */
    public static function defaultGroup()
    {
        $called_class = get_called_class();
        return $called_class::$_default_group;
    }
    
    /**
     * @see \CMF\Model\Base::$_default_tab
     * @return string
     */
    public static function defaultTab()
    {
        $called_class = get_called_class();
        return $called_class::$_default_tab;
    }
    
    /**
     * @see \CMF\Model\Base::$_superlock
     * @return string
     */
    public static function superlock()
    {
        $called_class = get_called_class();
        return $called_class::$_superlock;
    }
    
    /**
     * @see \CMF\Model\Base::$_superclass
     * @return string
     */
    public static function superclass()
    {
        $called_class = get_called_class();
        return $called_class::$_superclass;
    }
    
    /**
     * Resaves all of the items in the DB
     * @return void
     */
    public static function saveAll()
    {
        $called_class = get_called_class();
        $metadata = \DoctrineFuel::manager()->getClassMetadata($called_class);
        $qb = $called_class::select('item');
        
        foreach ($metadata->associationMappings as $field => $mapping) {
            $qb->leftJoin('item.'.$field, $field)->addSelect($field);
        }
        
        $items = $qb->getQuery()->getResult();
        
        foreach ($items as $num => $item) {
            $item->updated_at = new \Datetime();
            $item->populate(array());
            \DoctrineFuel::manager()->persist($item);
        }
        
        \DoctrineFuel::manager()->flush();
        
    }
    
    /**
     * Gets this model's actions
     * @return array
     */
    public static function actions()
    {
        $called_class = get_called_class();
        return $called_class::$_actions;
    }
    
    /**
     * Basically the same as the findBy method, this returns the results in such a way that they can be used to
     * populate Fuel's form select helper. If no ordering is specified they come out in alphabetical order.
     * 
     * @see \DoctrineFuel\Model::findBy()
     * @see \Fuel\Core\Form::select()
     * @return array
     */
    public static function options($filters = array(), $orderBy = array(), $limit = null, $offset = null, $params = null, $allow_html = true)
    {
        $called_class = get_called_class();
        $cache_id = md5($called_class.serialize($filters).serialize($orderBy).$limit.$offset);
        if (isset($called_class::$_options[$cache_id])) return $called_class::$_options[$cache_id];
        
        $results = $called_class::findBy($filters, $orderBy, $limit, $offset, $params)->getQuery()->getResult();
        $options = array();
        
        foreach ($results as $result) {
            $thumbnail = $result->thumbnail();
            $display = $result->display();
            $val = !empty($display) ? $display : '-';
            if ($thumbnail !== false && $allow_html) $val = '<img src="/image/2/40/40/'.$thumbnail.'" style="width:40px;height:40px;" /> '.$val;
            $options[strval($result->get('id'))] = $val;
        }
        
        if ((is_null($orderBy) || empty($orderBy)) && (is_null($called_class::$_order) || empty($called_class::$_order))) {
            uasort($options, function($a, $b) {
                return strcmp(strtolower($a), strtolower($b));
            });
        }
        
        return $called_class::$_options[$cache_id] = $options;
    }
    
    /**
     * Populates the model's non-nullable fields so that a blank one can be saved
     * @return void
     */
    public function blank($ignore_fields = null)
    {
        if (!is_array($ignore_fields)) $ignore_fields = array($ignore_fields);
        
        // Ignore some fields for sure
        array_push($ignore_fields, 'visible');
        $this->visible = true;
        
        // Get the field mappings
        $metadata = $this->_metadata();
        $field_mappings = $metadata->fieldMappings;
        
        // Make sure any non nullable fields are filled in...
        foreach ($field_mappings as $field_name => $field_mapping) {
            
            if (isset($this->$field_name) || $field_mapping['nullable'] === true || in_array($field_name, $ignore_fields)) continue;
            
            switch ($field_mapping['type']) {
                case 'boolean':
                    $this->set($field_name, false);
                    break;
                
                case 'text':
                case 'string':
                    $this->set($field_name, '');
                    break;
                
                case 'date':
                case 'datetime':
                    $this->set($field_name, new \DateTime());
                    break;
                
                case 'array':
                case 'object':
                    $this->set($field_name, array());
                
                default:
                    $this->set($field_name, 0);
                    break;
            }
        }
        
    }
    
    /**
     * Gets an instance of the model. Creates one if one doesn't exist
     * @return \CMF\Model\Base
     */
    public static function instance()
    {
        $called_class = get_called_class();
        if (!isset($called_class::$instances[$called_class])) {
            $result = $called_class::select('item')->setMaxResults(1)->getQuery()->getResult();
            if (count($result) == 0) {
                
                // Create the item if it doesn't exist
                $result = new $called_class();
                $result->blank();
                
                \DoctrineFuel::manager()->persist($result);
                \DoctrineFuel::manager()->flush();
                $called_class::$instances[$called_class] = $result;
            } else {
                $called_class::$instances[$called_class] = $result[0];
            }
        }
        return $called_class::$instances[$called_class];
    }
    
    /**
     * Called when Fuel's autoloader discovers the model class
     * 
     * @return void
     */
    public static function _init()
    {
        if (!empty($_FILES)) \Upload::prepare();
    }
    
    public function settings()
    {
        if (isset($this->settings)) {
            return $this->settings;
        }
        return array();
    }
    
    /**
     * A string representing the model. Used by the admin module when displaying items in dropdowns
     * etc - override this if you want to customise the way the model is represented in the admin site.
     * 
     * @return string
     */
    public function display()
    {
        return strval($this->id);
    }
    
    /**
     * Returns a relative path from the document root to an image to use as a thumbnail.
     * This should be the original source image, with no transformations applied
     * If this returns false (default), then no thumbnails will be shown for the item
     * Eg. "uploads/images/thumbnail.jpg"
     * @return mixed
     */
    public function thumbnail()
    {
        return false;
    }
    
    public function __toString()
    {
        return strval($this->id);
    }
	
}