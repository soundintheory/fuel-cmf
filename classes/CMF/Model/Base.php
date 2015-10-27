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
class Base extends \CMF\Doctrine\Model
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
    protected $pos = -1;
    
    /**
     * @Gedmo\Locale
     */
    protected $locale;
    
    public $_translated = array();
    
    protected static $_exclude_translations = array();
    protected static $_exclude_autotranslations = array();

    protected static $_lang_enabled = true;
    
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
    	'created_at' => array( 'readonly' => true, 'visible' => false, 'format' => 'Y-m-d H:i:s' ),
    	'updated_at' => array( 'readonly' => true, 'visible' => false, 'format' => 'Y-m-d H:i:s' ),
        'visible' => array( 'visible' => false ),
        'settings' => array( 'visible' => false ),
        'pos' => array( 'visible' => false )
    );

    /**
     * Associations to join automatically when rendering the list
     */
    protected static $_joins = array();
    
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
     * The module for the entity, to override the default
     */
    protected static $_module = null;
    
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
     * Tabs that should appear in the admin list for the model.
     * 
     * This is not inherited from parent classes. When defining a new list order for 
     * child classes, the whole lot must be redeclared.
     * 
     * @var array
     */
    protected static $_list_tabs = array();
    
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
     * Whether the model has any query fields in the admin interface. Will search 
     * @see \CMF\Model\Base::pagination()
     * @var string
     */
    protected static $_query_fields = array();

    /**
     * List of associations to filter by on the list page
     * @see \CMF\Model\Base::list_filters()
     * @var string
     */
    protected static $_list_filters = array();
    
    /**
     * Whether the model is pagination in the admin interface. Uses the $_per_page property.
     * @see \CMF\Model\Base::pagination()
     * @var string
     */
    protected static $_pagination = false;
    
    /**
     * Items per page
     * @see \CMF\Model\Base::per_page()
     * @var string
     */
    protected static $_per_page = "50";
    
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
     * Whether or not the sort listener should run on these items if pos has changed
     * @see \CMF\Model\Base::sortProcess()
     * @var string
     */
    protected static $_sort_process = true;

    /**
     * Whether the model is exportable via the Json api
     * @see \CMF\Model\Base::exportable()
     * @var boolean
     */
    protected static $_exportable = false;

    /**
     * Whether the model is exportable via the Json api
     * @see \CMF\Model\Base::importModel()
     * @var array
     * eg. array('file', 'api')
     */
    protected static $_import_type = null;

    /**
     * Whether the model is exportable via the Json api
     * @see \CMF\Model\Base::importModel()
     * @var string
     */
    protected static $_import_model = null;

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
    protected static $_icon = 'file-o';

    /**
    * Set Description For Model
    * @var string
    */
    protected static $_description = null;
    
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
    public function urlSlug()
    {
        $class_name = get_class($this);
        $self = $this;
        $values = array_map(function($prop) use($self) {
            return $self->$prop;
        }, $class_name::$_slug_fields);
        
        return \CMF::slug(implode(' ', $values), true, true);
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
     * Returns the url for the model (returns blank on the base model)
     */
    public function getUrl()
    {
        if (property_exists($this, 'url') && isset($this->url))
            return $this->url->get('url', '/');

        return $this->urlPrefix().$this->urlSlug();
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
        if ($called_class::$_template !== null) return $called_class::$_template;
        return $called_class::$_template = strtolower(str_replace(array('Model_', '_', '\\'), array('', '/', '/'), $called_class)).'.twig';
	}

    public $_has_processing_errors = false;
    
    /**
     * Passes all values through the process() method on any configured fields 
     * before setting them to the model.
     * 
     * @see \CMF\Field\Base::process()
     * @inheritdoc
     */
    public function populate($data, $overwrite=true)
    {
        $this->_has_processing_errors = false;

        if (is_array($data)) {
            $overwrite = \Arr::get($data, '__overwrite__', $overwrite);
        }
        
        // Merge settings in first!
        if (isset($data['settings']) && is_array($data['settings']))
            $this->settings = \Arr::merge($this->settings(), $data['settings']);
        
        $fields = $this->fieldSettings();
        
        // Pre process the data
        foreach ($fields as $field_name => $field) {
            
            $field_class = $field['field'];
            if (!isset($data[$field_name]) || !is_callable($field_class.'::preProcess')) continue;
            try {
                $data[$field_name] = $field_class::preProcess($data[$field_name], $field, $this);
            } catch (\Exception $e) {
                $this->_has_processing_errors = true;
            }
        }
        
        parent::populate($data, $overwrite);
        
        // Process the data once it's populated
        foreach ($fields as $field_name => $field) {
            
            $field_class = $field['field'];
            if (!isset($data[$field_name]) && $field_class::$always_process !== true) continue;
            try {
                $this->$field_name = $field_class::process($this->$field_name, $field, $this);
            } catch (\Exception $e) {
                $this->_has_processing_errors = true;
            }
        }
        
        try {
            $this->postPopulate();
        } catch (\Exception $e) {
            $this->_has_processing_errors = true;
        }
    }
    
    /**
     * Custom CMF event handler - can be overridden to execute actions on the model before saving to the database
     * @return void
     */
    protected function postPopulate() {}
    
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
        
        foreach ($field_settings as $field_name => $field_setting) {
            if (isset($fields[$field_name])) {
                $fields[$field_name] = \Arr::merge($fields[$field_name], $field_setting);
            }
        }
        
        return $this->_field_settings = $fields;
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
     * Returns the name of the module the model belongs to. By default, this is the class namespace
     * @return string
     */
    public static function getModule()
    {
        $called_class = get_called_class();
        if ($called_class::$_module != null) return $called_class::$_module;
        return trim(\Inflector::underscore(str_replace('\\', '/', \Inflector::get_namespace($called_class))), '/');
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
     * @see \CMF\Model\Base::$_list_tabs
     * @return array
     */
    public static function listTabs()
    {
        $called_class = get_called_class();
        return $called_class::$_list_tabs;
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
     * @see \CMF\Model\Base::$_query_fields
     * @return array
     */
    public static function query_fields()
    {
        $called_class = get_called_class();
        return $called_class::$_query_fields;
    }

    /**
     * @see \CMF\Model\Base::$_list_filters
     * @return array
     */
    public static function list_filters()
    {
        $called_class = get_called_class();
        return $called_class::$_list_filters;
    }
    
    /**
     * @see \CMF\Model\Base::$_pagination
     * @return array
     */
    public static function pagination()
    {
        $called_class = get_called_class();
        return $called_class::$_pagination;
    }
    
    /**
     * @see \CMF\Model\Base::$_per_page
     * @return array
     */
    public static function per_page()
    {
        $called_class = get_called_class();
        return $called_class::$_per_page;
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
    
    /**
     * @see \CMF\Model\Base::$_sort_process
     * @return array
     */
    public static function sortProcess($value = null)
    {
        $called_class = get_called_class();
        if ($value !== null) $called_class::$_sort_process = $value;
        return $called_class::$_sort_process;
    }
    
    /**
     * Provides a string identifier for an entity in order to group sorting
     */
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
        } elseif ($value instanceof \CMF\Doctrine\Model) {
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
     * @see \CMF\Model\Base::$_joins
     * @return array
     */
    public static function joins()
    {
        $called_class = get_called_class();
        return $called_class::$_joins;
    }

    /**
     * @see \CMF\Model\Base::$_exportable
     * @return bool
     */
    public static function exportable()
    {
        $called_class = get_called_class();
        return $called_class::$_exportable;
    }

    /**
     * @see \CMF\Model\Base::$_import_model
     * @return array
     */
    public static function importType()
    {
        $called_class = get_called_class();
        return $called_class::$_import_type;
    }

    /**
     * @see \CMF\Model\Base::$_import_model
     * @return string
     */
    public static function importModel()
    {
        $called_class = get_called_class();
        return $called_class::$_import_model;
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
     * @see \CMF\Model\Base::$_description
     * @return string
     */
    public static function description()
    {
        $called_class = get_called_class();
        return $called_class::$_description;
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
        $metadata = \D::manager()->getClassMetadata($called_class);
        $qb = $called_class::select('item');
        
        foreach ($metadata->associationMappings as $field => $mapping) {
            $qb->leftJoin('item.'.$field, $field)->addSelect($field);
        }
        
        $sql = $qb->getQuery()->getSQL();
        $items = $qb->getQuery()->getResult();
        
        foreach ($items as $num => $item) {
            $item->updated_at = new \Datetime();
            $item->populate(array());
            \D::manager()->persist($item);

            // Persist associations!
            foreach ($metadata->associationMappings as $field => $mapping)
            {
                $collection = $metadata->isCollectionValuedAssociation($field);
                $cascadePersist = \Arr::get($mapping, 'isCascadePersist', false);
                $value = $item->get($field);

                if (!$cascadePersist && $value) {
                    if ($collection && count($value)) {
                        foreach ($value as $relation) {
                            \D::manager()->persist($relation);
                        }
                    } else if (!$collection) {
                        \D::manager()->persist($value);
                    }
                }
            }
        }

        \D::manager()->flush();
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
    public static function options($filters = array(), $orderBy = array(), $limit = null, $offset = null, $params = null, $allow_html = true, $group_by = null)
    {
        $called_class = get_called_class();
        $cache_id = md5($called_class.serialize($filters).serialize($orderBy).$limit.$offset);
        if (isset($called_class::$_options[$cache_id])) return $called_class::$_options[$cache_id];

        $results = $called_class::findBy($filters, $orderBy, $limit, $offset, $params);
        $options = array();

        $group_column_names = !is_null($group_by) ? explode('.', $group_by) : null;

        if (!is_null($group_column_names) && property_exists($called_class, $group_column_names[0])) {

            $metadata = $called_class::metadata();

            // Join any relations
            if ($groupByRelation = $metadata->hasAssociation($group_column_names[0])) {
                $results->leftJoin("item.".$group_column_names[0], $group_column_names[0])->addSelect($group_column_names[0]);
            }

            $results = $results->getQuery()->getResult();

            foreach ($results as $result)
            {
                $thumbnail = $result->thumbnail();
                $display = $result->display();
                $val = !empty($display) ? $display : '-';
                if ($thumbnail !== false && $allow_html)
                    $val = '<img src="/image/2/40/40/'.$thumbnail.'" style="width:40px;height:40px;" /> '.$val;

                $group_key = null;

                if ($groupByRelation) {
                    $column_name = $group_column_names[0];
                    $relation = $result->get($column_name);
                    if (count($group_column_names) > 1) {
                        $group_key = $relation->get($group_column_names[1]);
                    } else {
                        $group_key = $relation->display();
                    }
                } else {
                    $group_key = $result->get($group_by);
                }

                if (!empty($group_key)) {
                    if (!isset($options[$group_key])) $options[$group_key] = array();
                    $options[$group_key][strval($result->get('id'))] = $val;
                } else {
                    $options[strval($result->get('id'))] = $val;
                }
            }

        } else {

            $results = $results->getQuery()->getResult();

            foreach ($results as $result) {
                $thumbnail = $result->thumbnail();
                $display = $result->display();
                $val = !empty($display) ? $display : '-';
                if ($thumbnail !== false && $allow_html) $val = '<img src="/image/2/40/40/'.$thumbnail.'" style="width:40px;height:40px;" /> '.$val;
                $options[strval($result->get('id'))] = $val;
            }

        }

        if ((is_null($orderBy) || empty($orderBy)) && (is_null($called_class::$_order) || empty($called_class::$_order))) {
            uksort($options, function($a, $b) use($options) {
                $aval = $options[$a];
                $bval = $options[$b];
                if (is_array($aval)) $aval = $a;
                if (is_array($bval)) $bval = $b;
                return strcmp(strtolower($aval), strtolower($bval));
            });
        }
        
        return $called_class::$_options[$cache_id] = $options;
    }
    
    /**
     * Gets a list of translatable fields for the object
     */
    public function translatable()
    {
        return \Admin::getTranslatable(get_class($this));
    }
    
    /**
     * Returns true if the object has all the translations
     */
    public function hasAllTranslations()
    {
        return count(array_diff($this->translatable(), $this->_translated)) === 0;
    }
    
    /**
     * Returns true if the field has been translated for the item
     */
    public function hasTranslation($field)
    {
        $class = get_class($this);
        return in_array($field, $class::$_exclude_translations) || in_array($field, $this->_translated);
    }
    
    /**
     * Returns a list of fields excluded from translation
     */
    public static function excludeTranslations()
    {
        $called_class = get_called_class();
        return $called_class::$_exclude_translations;
    }

    /**
     * Returns a list of fields excluded from auto translation
     */
    public static function excludeAutoTranslations()
    {
        $called_class = get_called_class();
        return $called_class::$_exclude_autotranslations;
    }
    
    /**
     * Checks if a particular field is translatable
     */
    public function isTranslatable($field)
    {
        $class = get_class($this);
        return in_array($field, \Admin::getTranslatable($class));
    }
    
    /**
     * Checks whether the model is translatable or not
     */
    public static function langEnabled($check_fields = true)
    {
        if ($check_fields) return static::$_lang_enabled && count(\Admin::getTranslatable(get_called_class())) > 0;
        return static::$_lang_enabled;
    }

    /**
     * Checks whether the entity has a URL relation
     */
    public static function hasUrlField()
    {
        $called_class = get_called_class();
        $metadata = $called_class::metadata();
        $url_associations = $metadata->getAssociationsByTargetClass('CMF\\Model\\URL');

        if (!empty($url_associations)) {
            
            foreach ($url_associations as $key => $association) {
                if ($association['type'] == ClassMetadataInfo::ONE_TO_ONE && $association['orphanRemoval']) {
                    return $key;
                    break;
                }
            }
        }

        return false;
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
            
            $value = $this->$field_name;
            
            if (!(is_null($value) || empty($value)) || $field_mapping['nullable'] === true || in_array($field_name, $ignore_fields)) continue;

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
                
                \D::manager()->persist($result);
                \D::manager()->flush();
                $called_class::$instances[$called_class] = $result;
            } else {
                $called_class::$instances[$called_class] = $result[0];
            }
        }
        return $called_class::$instances[$called_class];
    }
    
    public function settings($name = null, $value = null)
    {
        if ($name !== null) {
            $settings = is_array($this->settings) ? $this->settings : array();
            if ($value !== null) {
                \Arr::set($settings, $name, $value);
                $this->settings = $settings;
            } else {
                return \Arr::get($settings, $name);
            }
        }
        
        if (is_array($this->settings)) {
            return $this->settings;
        }
        return $this->settings = array();
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
    /**
     * Need this for duplicating items.
     * @return array associative array of object vars
     */
    public function get_object_vars(){
        return get_object_vars($this);
    }
	
}