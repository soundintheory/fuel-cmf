<?php

namespace CMF\Doctrine;

use Doctrine\DBAL\LockMode,
	Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection;

/**
 * This class is intended to be extended for every mapped class. It provides numerous
 * convenience methods to speed up development and to keep your code DRY
 */
abstract class Model
{
    /**
     * A stored version of this model's metadata for efficiency.
     * 
     * @var \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    protected static $metadata = null;
    
    /**
     * Stores errors for the model after validating.
     * 
     * @see \CMF\Doctrine\Model::validate()
     * @see \CMF\Doctrine\Model::addErrorForField()
     * @see \CMF\Doctrine\Model::getErrorsForField()
     * 
     * @var array
     */
	public $errors = array();
    
    /**
     * Tracks whether the properties or any relations of this model have changed.
     * 
     * @var boolean
     */
	public $changed = false;
    
    /**
     * Caches the result of toArray()
     * @var array
     */
    protected $array_data;
    
    /**
     * Specifies the default sort order for the model. Will only work when selecting
     * through static methods on the model (not directly through Doctrine)
     * @see \CMF\Doctrine\Model::order()
     * @var array
     */
    protected static $_order = array();
    
    /**
     * A quick way to get a query builder for the model. The best way to query models in general
     * as it is the most efficient and the most flexible.
     * 
     * <code>
     * $images = \Model_Category::select('item.title')
     *           ->where('item.id = 1')
     *           ->getQuery()
     *           ->getArrayResult();
     * </code>
     * 
     * @param  string $fields
     * @param  string $alias Defaults to 'item'
     * @param  string|null $indexBy
     * @return \Doctrine\ORM\QueryBuilder QueryBuilder with the model already selected
     */
    public static function select($fields = '', $alias = 'item', $indexBy = null)
    {
        return \D::manager()->createQueryBuilder()->select($fields)->from(get_called_class(), $alias, $indexBy);
    }
    
    /**
     * A convenient alias for the find method on the model's repository
     * 
     * @see \Doctrine\ORM\EntityRepository::find()
     *
     * @param mixed $id The identifier.
     * @param integer $lockMode
     * @param integer $lockVersion
     *
     * @return object The entity.
     */
    public static function find($id, $lockMode = LockMode::NONE, $lockVersion = null)
    {
        return \D::manager()->getRepository(get_called_class())->find($id, $lockMode, $lockVersion);
    }
    
    /**
     * Basically just an alias for the findBy method that doesn't specify any
     * criteria, just so you can be more verbose in your code.
     * 
     * @see \CMF\Model\Item::findBy()
     * 
     * @param array $orderBy Fields to order by
     * @param int $limit Limit the number of results
     * @param int $offset Offset the results
     * 
     * @return array Array of results as instances of the model
     */
    public static function findAll(array $orderBy = array(), $limit = null, $offset = null)
    {
        $called_class = get_called_class();
        return $called_class::findBy(array(), $orderBy, $limit, $offset)->getQuery()->getResult();
    }
    
    /**
     * The same functionality as the findBy method on the model's repository
     * but this uses the query builder instead, so it's a bit more efficient and flexible
     * 
     * @see \Doctrine\ORM\EntityRepository::findBy()
     * 
     * @param array $filters
     * @param array $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * 
     * @return array Array of results as instances of the model
     */
    public static function findBy(array $filters, array $orderBy = array(), $limit = null, $offset = null, $params = null)
    {
        $called_class = get_called_class();
        $qb = $called_class::select('item');
        $order = (empty($orderBy) || is_null($orderBy)) ? $called_class::$_order :  $orderBy;
        
        foreach ($filters as $num => $filter)
        {
            if ($num === 0) {
                $qb->where('item.'.$filter);
            } else {
                $qb->andWhere('item.'.$filter);
            }
        }
        
        if (count($order) > 0) {
            $o = 0;
            foreach ($order as $field => $dir)
            {
                if ($o === 0) {
                    $qb->orderBy('item.'.$field, $dir);
                } else {
                    $qb->addOrderBy('item.'.$field, $dir);
                }
                
                $o++;
            }
        }
        
        if (is_array($params)) {
            foreach ($params as $num => $param) {
                $qb->setParameter($num+1, $param);
            }
        }
        
        if ($limit !== null) $qb->setMaxResults($limit);
        if ($offset !== null) $qb->setFirstResult($offset);
        return $qb;
    }
    
    /**
     * An alias for the findOneBy method on this model's repository.
     * 
     * @param  array  $criteria
     * @return object An instance of this model
     */
    public static function findOneBy(array $criteria)
    {
        return \D::manager()->getRepository(get_called_class())->findOneBy($criteria);
    }
    
    /**
     * Gets the first item, using the default sort order unless one is passed in
     * 
     * @return object|null An instance of the model, or null if there isn't one
     */
    public static function findFirst(array $order = array())
    {
        $called_class = get_called_class();
        $result = $called_class::findBy(array(), $order, 1)->getQuery()->getResult();
        return (count($result) === 1) ? $result[0] : null;
    }
    
    /**
     * Gets the last item, using the default sort order unless one is passed in
     * 
     * @return object|null An instance of the model, or null if there isn't one
     */
    public static function findLast(array $order = array())
    {
        $called_class = get_called_class();
        $key = $called_class::_metadata()->getSingleIdentifierFieldName();
        $order = array_merge(array( $key => 'ASC' ), $called_class::$_order, $order);
        $order = array_map(function($dir) {
            return ($dir == 'DESC') ? 'ASC' : 'DESC';
        }, $order);
        
        $result = $called_class::findBy(array(), $order, 1)->getQuery()->getResult();
        return (count($result) === 1) ? $result[0] : null;
    }
	
    /**
     * Validates the model's properties and association with the Symfony Validator.
     * Stores any errors in the model's $errors property.
     * 
     * @param array|null $groups The validator groups to use for validating
     * @param array|null $fields Field names to validate. All fields will be validated if omitted.
     * @param array|null $fields Field names to exclude from validation
     * 
     * @return bool Whether the model has passed validation
     */
	public function validate($groups = null, $fields = null, $exclude_fields = null, $exclude_types = null)
	{
        $metadata = $this->_metadata();
	    $result = \D::validator()->validate($this, $groups);
	    $this->errors = array();
	    
	    foreach ($result->getIterator() as $violation) {
            
	        $prop = $violation->getPropertyPath();
            if ($prop == 'id' || ($fields !== null && !in_array($prop, $fields)) || ($exclude_fields !== null && in_array($prop, $exclude_fields)))
                continue;
            
	        $msg = $violation->getMessage();
	        $this->addErrorForField($prop, $msg);
            
	    }
        
	    return count($this->errors) === 0;
	}
    
    /**
     * Populates the model with an associative array of data - ideal for passing in
     * data straight from POST. Silently ignores anything that isn't a property.
     * 
     * @param array $data The data to populate
     * @return void
     */
    public function populate($data, $overwrite=true)
    {
        if (is_array($data)) {
            $overwrite = \Arr::get($data, '__overwrite__', $overwrite);
        }
        
        foreach ($data as $field_name => $field_value) {
            
            if (property_exists($this, $field_name)) {
                $this->set($field_name, $field_value, $overwrite);
            }
            
        }
        
    }
	
    /**
     * @param string $field_name
     * @return array Errors logged against the given field name
     */
	public function getErrorsForField($field_name)
	{
	    return \Arr::get($this->errors, $field_name, array());
	}
	
    /**
     * Logs an error message (or array of messages) against the given field name.
     * 
     * @param string $field_name
     * @param string|array $msg The error message(s)
     */
	public function addErrorForField($field_name, $msg)
	{
	    if (is_array($msg)) {
	        $errors = $msg;
	    } else {
	        $errors = \Arr::get($this->errors, $field_name, array());
	        $errors[] = $msg;
	    }
        $this->errors[$field_name] = $errors;
	}
    
    /**
     * Get a field's value.
     *
     * @param string $field
     * @param mixed $default_value If the property or method is null or not found
     * @return mixed
     */
    public function get($field, $default_value = null)
    {   
        if (property_exists($this, $field)) {
            return isset($this->$field) ? $this->$field : $default_value;
        } else if (method_exists($this, $field)) {
            return $this->$field();
        } else if (strpos($field, '.') !== false) {
            $parts = explode(".", $field);
            $field_name = array_shift($parts);
            $assoc_field = array_shift($parts);
            if (property_exists($this, $field_name) && isset($this->$field_name)) {
                return $this->$field_name->$assoc_field;
            }
            return $default_value;
        } else {
            return $default_value;
        }
    }

    /**
     * Set a field's value.
     *
     * @throws InvalidArgumentException - When the wrong target object type is passed to an association
     * @throws BadMethodCallException - When no property exists by that name.
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function set($field, $value, $overwrite=true)
    {
        $metadata = $this->_metadata();
        
        // First of all, if this is an association then we'll need to do some detective work...
        if ($metadata->hasAssociation($field)) {
            
            $target_class = $metadata->getAssociationTargetClass($field);
            
            if ($metadata->isSingleValuedAssociation($field)) {
                
                if ($value instanceof $target_class) {
                    $association = $value;
                } else if (is_numeric($value)) {
                    $association = $target_class::find($value);
                } else if (is_array($value)) {
                    $association = (isset($this->$field) && $this->$field instanceof $target_class) ? $this->$field : new $target_class();
                } else {
                    $this->$field = $association = null;
                    return;
                }
                
                if (isset($association) && !$association instanceof Model)
                    throw new \InvalidArgumentException("The relation '$field' of '".get_class($this)."' is not an instance of CMF\Doctrine\Model - this convenience method won't work!");
                
                if (isset($association) && is_array($value)) $association->populate($value);
                
                $this->$field = $association;
                $this->completeOwningSide($field, $target_class, $association);
                
                if (isset($association)) \D::manager()->persist($association);
                
            } else if ($metadata->isCollectionValuedAssociation($field)) {
                
                if (is_null($value) || empty($value)) {
                    $value = array();
                } else if (is_numeric($value) || $value instanceof \CMF\Doctrine\Model) {
                    $value = array($value);
                } else if ($value instanceof Collection) {
                    $value = $value->toArray();
                } else if (!is_array($value) && !($value instanceof Collection))  {
                    throw new \InvalidArgumentException("The value '$value' passed to '$field' of '".get_class($this)."' is not a collection or an array");
                }
                
                $value = array_values($value);
                $ids = array();
                $collection = (!isset($this->$field)) ? new ArrayCollection() : $this->$field;
                
                $len = count($value);
                for ($i=0; $i < $len; $i++) {
                    
                    $item = $value[$i];
                    $item_target_class = $target_class;
                    
                    if (is_numeric($item)) {
                        $item = $target_class::find($item);
                    } else if (is_array($item)) {
                        if (array_key_exists('id', $item) && !empty($item['id'])) {
                            $new_item = $target_class::find($item['id']);
                        } else {
                            if (isset($item['__type__']) && is_subclass_of($item['__type__'], $target_class)) $item_target_class = $item['__type__'];
                            $new_item = new $item_target_class();
                        }
                        $new_item->populate($item);
                        $item = $new_item;
                    }
                    
                    if (!$collection->contains($item)) {
                        $collection->add($item);
                        $this->completeOwningSide($field, $item_target_class, $item);
                    }
                    
                    \D::manager()->persist($item);
                    $ids[] = $item->get('id');
                    if ($item->changed) $this->changed = true;
                    
                }
                
                // If overwrite is true, remove collection items that aren't present in the given array
                if ($overwrite === true) {
                    
                    foreach ($collection as $collection_item) {
                        $cid = $collection_item->get('id');
                        if (!is_null($cid) && !in_array($cid, $ids)) {
                            $collection->removeElement($collection_item);
                            $this->completeOwningSide($field, $target_class, $collection_item, true);
                            $this->changed = true;
                        }
                    }
                    
                }
                
                $this->$field = $collection;
                
            }
            
            return;
            
        }
        
        if (property_exists($this, $field)) {
            
            // Otherwise, this is a normal property. Try and set it...
            
            if ($this->$field !== $value) {
                $this->$field = $value;
                $this->changed = true;
            }
            
            return;
            
        } else if (($pos = strpos($field, '[')) !== false || ($pos = strpos($field, '.')) !== false) {
            
            // The property may be written in array syntax or dot notation. Try and decipher it...
            
            $field_name = substr($field, 0, $pos);
            $field_prop = substr(str_replace(array('[', ']'), array('.', ''), $field), $pos+1);
            
            if (property_exists($this, $field_name)) {
                
                $field_val = $this->$field_name;
                
                if ($field_val instanceof Model) {
                    $field_val->set($field_prop, $value);
                    return;
                }
                
                if (!isset($this->$field_name) || !is_array($this->$field_name)) $this->$field_name = array();
                \Arr::set($this->$field_name, $field_prop, $value);
                return;
                
            }
            
        }
        
        throw new \BadMethodCallException("no field with name '".$field."' exists on '".$metadata->getName()."'");
        
    }

    /**
     * Add an object to a collection
     *
     * @param string $field
     * @param mixed $object
     */
    public function add($field, $object)
    {
        $metadata = $this->_metadata();

        if ($metadata->hasAssociation($field) && $metadata->isCollectionValuedAssociation($field)) {
            $target_class = $metadata->getAssociationTargetClass($field);
            if (!($object instanceof $target_class)) {
                throw new \InvalidArgumentException("Expected persistent object of type '".$target_class."'");
            }
            if (!($this->$field instanceof Collection)) {
                $this->$field = new ArrayCollection($this->$field ?: array());
            }
            if (!$this->$field->contains($object)) {
                $this->$field->add($object);
                $this->completeOwningSide($field, $target_class, $object);
            }
        } else {
            throw new \BadMethodCallException("There is no method add".$field."() on ".$metadata->getName());
        }
    }
    
    /**
     * Remove an object from a collection
     *
     * @param string $field
     * @param mixed $object
     */
    public function remove($field, $object)
    {
        $metadata = $this->_metadata();

        if ($metadata->hasAssociation($field) && $metadata->isCollectionValuedAssociation($field)) {
            $target_class = $metadata->getAssociationTargetClass($field);
            if (!($this->$field instanceof Collection)) {
                $this->$field = new ArrayCollection($this->$field ?: array());
            }
            if ($this->$field->contains($object)) {
                $this->$field->removeElement($object);
                $this->completeOwningSide($field, $target_class, $object);
            }
        }
    }
    
    /**
     * If this is an inverse side association complete the owning side.
     *
     * @param string $field
     * @param ClassMetadata $target_class
     * @param object $target_object
     * @param bool $null Whether to remove the association
     */
    protected function completeOwningSide($field, $target_class, $target_object, $null = false)
    {
        $metadata = $this->_metadata();
        
        // Add this object on the owning side aswell, for obvious infinite recursion
        // reasons this is only done when called on the inverse side.
        if ($metadata->isAssociationInverseSide($field)) {
            $mapped_by = $metadata->getAssociationMappedByTargetField($field);
            $target_metadata = \D::manager()->getClassMetadata($target_class);
            
            if ($null === true) {
                if ($target_metadata->isCollectionValuedAssociation($mapped_by)) {
                    $target_object->remove($mapped_by, $this);
                } else {
                    $target_object->set($mapped_by, null);
                }
            } else if (!is_null($target_object)) {
                $setter = $target_metadata->isCollectionValuedAssociation($mapped_by) ? "add" : "set";
                $target_object->$setter($mapped_by, $this);
            }
        }
    }
    
    public static function repository()
    {
        return \D::manager()->getRepository(get_called_class());
    }

    /**
     * Used internally as an alternative to the static version.
     *
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    protected function _metadata()
    {
        $called_class = get_class($this);
        //if ($called_class::$metadata === null) return $called_class::$metadata = \D::manager()->getClassMetadata($called_class);
        return \D::manager()->getClassMetadata($called_class);
    }
    
    /**
     * Get Doctrine Metadata for the model.
     *
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    public static function metadata()
    {
        $called_class = get_called_class();
        return \D::manager()->getClassMetadata($called_class);
    }
    
    /**
     * @see \CMF\Doctrine\Model::$_order
     * @return string The default sort order for the model
     */
    public static function order()
    {
        $called_class = get_called_class();
        return $called_class::$_order;
    }
    
    /**
     * Converts the model into an array format
     * @return array
     */
    public function toArray($include_associations = true)
    {
        if (isset($this->array_data)) return $this->array_data;
        
        $output = array();
        $metadata = $this->_metadata();
        foreach ($metadata->fieldMappings as $field_name => $field) {
            $value = $this->$field_name;
            switch ($field['type']) {
                case 'date':
                    $output[$field_name] = ($value instanceof \DateTime) ? $value->format('d/m/Y H:i:s') : strval($value);
                    
                case 'datetime':
                    $output[$field_name] = ($value instanceof \DateTime) ? $value->format('d M Y') : strval($value);
                    break;
                
                case 'object':
                    $output[$field_name] = is_object($value) ? get_object_vars($value) : $value;
                
                default:
                    $output[$field_name] = $value;
                    break;
            }
        }
        
        if ($include_associations === false) return $this->array_data = $output;
        
        foreach ($metadata->associationMappings as $assoc_name => $assoc) {
            $value = $this->$assoc_name;
            if (!is_null($value) && $value instanceof \CMF\Doctrine\Model) $output[$assoc_name] = $value->toArray(false);
        }
        
        return $this->array_data = $output;
    }
    
    /**
     * Magic methods for getProperty(), setProperty(), addAssociation(), removeAssociation()
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array($this->$method, $args);
        } else if (property_exists($this, $method)) {
            return $this->$method;
        }
        
        $action = substr($method, 0, 3);
        switch ($action) {
            case 'get':
                return $this->get(lcfirst(substr($method, 3)));
                break;
                
            case 'set':
                return $this->set(lcfirst(substr($method, 3)), $args[0]);
                break;
        }
        
        return null;
    }
    
    /**
     * Magic getter to enable read-only access to private / protected properties
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else if (method_exists($this, $name)) {
            return $this->$name();
        }
        
        return null;
    }
    
    public function __isset($name)
    {
        return isset($this->$name);
    }
	
}