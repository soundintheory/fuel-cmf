<?php

namespace Api;

use Doctrine\ORM\Tools\Pagination\Paginator,
	Doctrine\ORM\Query;

/**
 * A wrapper for the doctrine query builder, which can build itself from URL parameters
 */
class Rest_Query
{
	protected $selects;
	protected $links;
	protected $joins;
	protected $fields;
	protected $class;
	protected $builder;
	protected $params;
	protected $root;
	protected $rootOuput;
	protected $field;
	protected $reserved_names = array('fields');
	protected $excluded_types = array('binary');
	protected $unselected_joins = array();

	protected $output = array();
	protected $meta = array();
	protected $hydrations = array();

	/**
	 * Initialise the wrapper and it's query builder instance
	 * 
	 * @param string $class The model class
	 */
	public function __construct($class, $root, $rootOutput, $params = array(), $field = null)
	{
		$this->class = $class;
		$this->root = $root;
		$this->rootOutput = $rootOutput;
		$this->params = \Input::param();
		$this->field = $field;

		if (is_array($params)) {
			$this->params = \Arr::merge($this->params, $params);
		}
	}

	/**
	 * Sets up the query, builds the output and returns it ready for JSON encoding
	 */
	public function getResult()
	{
		$query = $this->getQueryBuilder()->getQuery();
		
		// Only use the paginator if we have joins
		if (!empty($this->links)) {
			$paginator = new Paginator($query, true);
			$results = iterator_to_array($paginator->getIterator());
		} else {
			$results = $query->getResult();
		}

		// Build the output array
		if ($this->field) {
			$this->buildFieldOutput($results, $this->field);
		} else {

			// Get meta info for the root type
			$this->buildOutputMeta($results);

			// Get Doctrine to add the missing collection data
			$this->performHydrations($this->class, array_keys($results));

			$this->buildOutput($results);
		}

		return $this->output;
	}

	/**
	 * Takes a reference to an association, and recursively makes sure that all the required joins have been made
	 * 
	 * @param string $alias 		The alias to refer to the join by
	 * @param string $field_name 	The field on the joined model
	 * @param string $sep 			The notation style (eg. dot) for accessing the join's property
	 * @param string $prefix 		The prefix to add to the join alias
	 * @return array|null
	 */
	protected function parseJoinField($class, $field_name, $alias, $sep = '.')
	{
		$builder = $this->getQueryBuilder();
		$metadata = $class::metadata();
		$parts = explode($sep, $field_name);

		// If we've found a field, return the details
		if ($metadata->hasField($parts[0])) {
			return array($alias, $parts[0]);
		}

		// If it's not an association either, we can't continue
		if (!$metadata->hasAssociation($parts[0])) return false;

		// If a join has already been made, no need to do it again!
		$join_alias = (substr($alias, 0, 1) == '_' ? '' : '_').$alias.'_'.$parts[0];
		$join_class = $metadata->getAssociationTargetClass($parts[0]);

		if (!isset($this->joins[$join_alias])) {
			$builder->leftJoin("$alias.".$parts[0], $join_alias);
			//var_dump("JOIN $alias.".$parts[0]." AS $join_alias");
			$this->joins[$join_alias] = $join_class;
		}

		// Recursively call this function until we find a field
		return $this->parseJoinField($join_class, implode($sep, array_slice($parts, 1)), $join_alias, $sep);
	}

	/**
	 * Decides which fields the query should select based on params
	 *
	 * @return void
	 */
	protected function buildQuerySelect($root = null, $alias = 'item', $target_class = null, $level = 0, $root_prefix = null)
	{
		$root = $root ?: $this->root;
		$root_prefix = $root_prefix ?: $this->root.'.';
		$target_class = $target_class ?: $this->class;
		$builder = $this->getQueryBuilder();
		$metadata = $target_class::metadata();
		$prefix = '';
		$next_level = array();

		// If it's just one field we need, select only the bare essentials
		if ($this->field)
		{
			$partial_fields = array('id');
			if ($metadata->hasField($this->field)) {
				$partial_fields[] = $this->field;
			} else {
				$builder->leftJoin("$alias.".$this->field, $this->field)->addSelect($this->field);
			}

			$builder->addSelect("PARTIAL $alias.{".implode(',', $partial_fields)."}");
			return;
		}

		// Select the root item
		$builder->addSelect($alias);

		// Select its associations
		foreach ($metadata->getAssociationNames() as $assoc) {
			if ($metadata->isSingleValuedAssociation($assoc)) {

				// If it's a single association, only select the ID so we can perform that 
				$builder->leftJoin("$alias.$assoc", $assoc)->addSelect($assoc);

			} else {

				// If it's a multi-valued association don't select it now! We'll hydrate them separately to improve performance
				$this->hydrateCollectionLater($target_class, $assoc);
			}
		}
	}

	/**
	 * Decides how the query should be sorted based on params
	 *
	 * @return void
	 */
	protected function buildQuerySort()
	{
		$class = $this->class;
		$sort = $this->param('sort');
		$model_sort = $class::order();
		if ((empty($sort) && empty($model_sort)) || $this->field) return;

		$builder = $this->getQueryBuilder();
		$metadata = $this->getMetadata();
		$sort = $this->expand($sort);

		if (empty($sort))
		{
			foreach ($model_sort as $field => $dir) {
				$sort[] = (strtolower($dir) == 'desc' ? '-' : '').$field;
			}
		}

		foreach ($sort as $value)
		{
			$alias = 'item';
			$field = $value;
			$dir = 'ASC';
			$is_field = false;

			// A "-" sign prepended to the sort field makes it descending
			if (substr($field, 0, 1) == '-') {
				$dir = 'DESC';
				$field = substr($field, 1);
			}

			// Check whether we're using an association
			if (strpos($field, '.') !== false) {

				// Attempt to process as a join field
				$selector = $this->parseJoinField($this->class, $field, $alias, '.');
				if (is_array($selector)) {
					$is_field = true;
					$alias = $selector[0];
					$field = $selector[1];
				}

			} else {
				$is_field = $metadata->hasField($field);
			}

			$builder->addOrderBy("$alias.$field", $dir);
		}
	}

	/**
	 * Adds offset and limit to the query based on params
	 *
	 * @return void
	 */
	protected function buildQueryLimit()
	{
		if ($this->field) return;

		$builder = $this->getQueryBuilder();
		$metadata = $this->getMetadata();
		$offset = intval($this->param('offset'));
		$limit = intval($this->param('limit'));

		if ($offset) $builder->setFirstResult($offset);
		if ($limit) $builder->setMaxResults($limit);
	}

	/**
	 * Adds 'where' comparisons to the query based on params
	 *
	 * @return void
	 */
	protected function buildQueryWhere()
	{
		$builder = $this->getQueryBuilder();
		$metadata = $this->getMetadata();
		$fields = \Arr::flatten($this->param(), '->');

		foreach ($fields as $field => $value) {

			if (in_array($field, $this->reserved_names)) continue;

			$alias = 'item';
			$is_field = $is_association = false;

			// Check for NOT
			if ($negative = (substr($value, 0, 1) == '!')) {
				$value = substr($value, 1);
			}

			// Check for LIKE
			if ($like = (substr($value, 0, 1) == '~')) {
				$value = substr($value, 1);
			}

			// Check whether we're using an association
			if (strpos($field, '->') !== false) {
				// Attempt to process as a join field
				$parts = $this->parseJoinField($this->class, $field, $alias, '->');

				if (is_array($parts)) {
					$is_field = true;
					$alias = $parts[0];
					$field = $parts[1];
				}

			} else {
				// Check the field exists
				$is_association = $metadata->hasAssociation($field);
				$is_field = $metadata->hasField($field);

				if (!$is_field && !$is_association) {

					// This might be a plural version of a field, in which case we can make it an "in array"
					$field = \Inflector::singularize($field);
					$is_association = $metadata->hasAssociation($field);
					$is_field = $metadata->hasField($field);
					$values = $this->expand($value);
					$value = count($values) == 1 ? $values[0] : implode(',', $values);
				}
			}

			if (!$is_field && !$is_association) continue;

			// Set up a field name to use for our parameter
			$fieldname = substr($alias, 1).'_'.$field;

			// Support for "LIKE"
			if ($like && !$is_association) {

				$operator = $negative ? 'NOT LIKE' : 'LIKE';

				// Check if it's a literal comparison (wrapped in double quotes)
				if ($literal = substr($value, 0, 1) == '"' && substr($value, -1) == '"')
					$value = substr($value, 1, -1);

				if (!$literal) {
					$values = explode(' ', trim($value));
					foreach ($values as $num => $_value) {
						$_fieldname = $fieldname.'_'.$num;
						$builder->setParameter($_fieldname, "%$_value%")->andWhere("$alias.$field $operator :$_fieldname");
					}
				} else {
					$builder->setParameter($fieldname, "%$value%")->andWhere("$alias.$field $operator :$fieldname");
				}

				continue;
			}

			// Support for "in array"
			if (is_array($value) || strpos($value, ',') !== false) {
				$values = $this->expand($value);
				$operator = $negative ? 'NOT IN' : 'IN';
				$builder->setParameter($fieldname, $values)->andWhere("$alias.$field $operator(:$fieldname)");
				continue;
			}

			// Standard comparison
			$operator = $negative ? '!=' : '=';
			$builder->setParameter($fieldname, $value)->andWhere("$alias.$field $operator :$fieldname");
		}

		return $builder;
	}

	/**
	 * Hydrate a collection after the initial query
	 */
	protected function hydrateCollectionLater($model, $field)
	{
		if (!isset($this->hydrations[$model])) $this->hydrations[$model] = array();
		if (!in_array($field, $this->hydrations[$model])) $this->hydrations[$model][] = $field;
	}

	/**
	 * The doctrine query builder instance
	 * 
	 * @return \Doctrine\ORM\QueryBuilder 
	 */
	protected function getQueryBuilder()
	{
		if (!$this->builder) 
		{
			// Initialise builder and other vars
			$this->joins = array();
			$this->links = array();
			$this->fields = array();
			$this->selects = array();
			$this->output = array();
			$this->builder = \D::manager()->createQueryBuilder()->from($this->class, 'item', 'item.id');

			// Add each part of the query separately
			$this->buildQuerySelect();
			$this->buildQueryWhere();
			$this->buildQuerySort();

			// This must be the last one, so we can perform the limit sub query
			$this->buildQueryLimit();
		}

		return $this->builder;
	}

	protected function getFieldsForModel($model, $resource)
	{
		if (!empty($this->fields[$resource])) return $this->fields[$resource];

		$metadata = $model::metadata();
		$excluded = $this->excluded_types;
		$fields = $this->fields[$resource] = array_merge($metadata->getFieldNames(), $metadata->getAssociationNames());

        $filtered = array_values(array_filter($fields, function($field) use($metadata, $excluded) {

        	if (!$metadata->hasField($field)) return true;
        	
        	$mapping = $metadata->getFieldMapping($field);
            return !in_array($mapping['type'], $excluded);
        }));

        return $this->fields[$resource] = $filtered;
	}

	/**
	 * Performs hydrations that were scheduled during the build of the query
	 */
	protected function performHydrations($class, $ids = null)
	{
		if (!isset($this->hydrations[$class])) return;

		$extra = '';
		if (is_array($ids))
		{
			if (!($class == $this->class && count($ids) == $this->meta['total'])) {
				$extra = " WHERE item.id IN(".implode(',', $ids).")";
			}
		}

		foreach ($this->hydrations[$class] as $assoc)
		{
			\D::manager()->createQuery("SELECT PARTIAL item.{id}, $assoc FROM $class item LEFT JOIN item.$assoc $assoc".$extra)->getResult();
		}
	}

	/**
	 * Gets meta info for the output
	 */
	protected function buildOutputMeta($results)
	{
		$class = $this->class;
		$this->meta = array(
			'count' => count($results),
			'total' => intval($class::select('COUNT(item.id)')->getQuery()->getSingleScalarResult())
		);
	}

	/**
	 * Transforms raw query results into a fully fledged JSON API response
	 */
	protected function buildOutput($results)
	{
		$this->output = array();
		$this->output['meta'] = $this->meta;
		$this->output[$this->rootOutput] = array();
		$this->output['included'] = array();
		$metadata = $this->getMetadata();
		$associations = $metadata->getAssociationNames();
		$root_type = $this->root;

		foreach ($results as $result)
		{
			// Create a simple array version of the result
			$output = $result->toArray(false);
			$output_type = $metadata->name;
			$output_id = $result->id;

			// Put the associations into their respective sideloaded arrays
			foreach ($associations as $assoc)
			{
				if (empty($result->$assoc)) continue;

				$assoc_class = $metadata->getAssociationTargetClass($assoc);
				$type = \Inflector::pluralize(\Admin::getTableForClass($assoc_class));
				if (!isset($this->output['included'][$type])) $this->output['included'][$type] = array();

				if ($metadata->isCollectionValuedAssociation($assoc))
				{
					$output[$assoc] = array(
						'ids' => array(),
						'type' => $type,
						'href' => \Uri::base(false)."api/$root_type/$output_id/$assoc"
					);
					foreach ($result->$assoc as $assoc_value) {
						$assoc_id = $assoc_value->id;
						$output[$assoc]['ids'][] = $assoc_id;

						if (!($type == $this->root && isset($this->output[$this->rootOutput][$assoc_id]))) {
							$this->output['included'][$type][$assoc_id] = $assoc_value->toArray(false);
						}
					}
				}
				else
				{
					$assoc_id = $result->$assoc->id;
					$output[$assoc] = array(
						'id' => $assoc_id,
						'type' => $type,
						'href' => \Uri::base(false)."api/$type/$assoc_id"
					);
					if (!($type == $this->root && isset($this->output[$this->rootOutput][$assoc_id]))) {
						$this->output['included'][$type][$assoc_id] = $result->$assoc->toArray(false);
					}
				}
			}

			// Put the result into the root output
			$this->output[$this->rootOutput][$output_id] = $output;
		}

		if ($this->param('id') && count($this->output[$this->rootOutput]) === 1) {
			$this->output[$this->rootOutput] = $this->output[$this->rootOutput][$this->param('id')];
		} else {
			$this->output[$this->rootOutput] = array_values($this->output[$this->rootOutput]);
		}

		if (!empty($this->output['included'])) {
			$this->output['included'] = array_filter($this->output['included'], function($included) {
				return !empty($included);
			});
			if (empty($this->output['included'])) unset($this->output['included']);
		} else if (isset($this->output['included'])) {
			unset($this->output['included']);
		}
	}

	/**
	 * Transforms raw query results into a fully fledged JSON API response
	 */
	protected function buildFieldOutput($results, $field)
	{
		$this->output = array();
		$this->output[$this->rootOutput] = array();
		$this->output['included'] = array();
		$metadata = $this->getMetadata();

		if ($isAssociation = $metadata->hasAssociation($field)) {
			$fieldClass = $metadata->getAssociationTargetClass($field);
			$fieldMetadata = $fieldClass::metadata();
			$fieldAssociations = $fieldMetadata->getAssociationNames();
		}

		foreach ($results as $result)
		{
			if (!$isAssociation) {
				var_dump('yeah?'); exit();
				continue;
			}

			$entities = $metadata->isSingleValuedAssociation($field) ? array($result->$field) : $result->$field->toArray();
			foreach ($entities as $entity)
			{
				// Create a simple array version of the entity
				$output = $entity->toArray(false);
				$output_id = $entity->id;

				// Put the associations into their respective sideloaded arrays
				foreach ($fieldAssociations as $assoc)
				{
					if (empty($entity->$assoc)) continue;

					$assoc_class = $fieldMetadata->getAssociationTargetClass($assoc);
					$type = \Inflector::pluralize(\Admin::getTableForClass($assoc_class));
					if (!isset($this->output['included'][$type])) $this->output['included'][$type] = array();

					if ($fieldMetadata->isCollectionValuedAssociation($assoc))
					{
						$output[$assoc] = array(
							'ids' => array(),
							'type' => $type,
							'href' => \Uri::base(false)."api/$type/$output_id/$assoc"
						);

						foreach ($entity->$assoc as $assoc_value) {
							$assoc_id = $assoc_value->id;
							$output[$assoc]['ids'][] = $assoc_id;

							if (!($type == $this->root && isset($this->output[$this->rootOutput][$assoc_id]))) {
								$this->output['included'][$type][$assoc_id] = $assoc_value->toArray(false);
							}
						}
					}
					else
					{
						$assoc_id = $entity->$assoc->id;
						$output[$assoc] = array(
							'id' => $assoc_id,
							'type' => $type,
							'href' => \Uri::base(false)."api/$type/$assoc_id"
						);
						if (!($type == $this->root && isset($assoc_id, $this->output[$this->rootOutput]))) {
							$this->output['included'][$type][$assoc_id] = $entity->$assoc->toArray(false);
						}
					}
				}

				// Put the result into the root output
				$this->output[$this->rootOutput][$output_id] = $output;
			}
		}

		if ($isAssociation && $metadata->isSingleValuedAssociation($field)) {
			$this->output[$this->rootOutput] = array_shift($this->output[$this->rootOutput]);
		} else {
			$this->output[$this->rootOutput] = array_values($this->output[$this->rootOutput]);
		}

		if (!empty($this->output['included'])) {
			$this->output['included'] = array_filter($this->output['included'], function($included) {
				return !empty($included);
			});
			if (empty($this->output['included'])) unset($this->output['included']);
		} else if (isset($this->output['included'])) {
			unset($this->output['included']);
		}
	}

	/**
	 * The metadata associated with the query's model
	 * 
	 * @return \Doctrine\ORM\Mapping\ClassMetadataInfo
	 */
	protected function getMetadata()
	{
		$class = $this->class;
		return $class::metadata();
	}

	/**
	 * Transforms a param into an array, if it isn't one already
	 */
	protected function expand($value, $sep = ',')
	{
		if (is_array($value)) return $value;
		if (empty($value)) return array();

		return explode($sep, trim($value, $sep));
	}

	/**
	 * Gets a param for the query, falling back to a default value if not found.
	 * If no param is specified, it returns the whole array
	 * 
	 * @param string $param
	 * @param mixed $default
	 * @return mixed
	 */
	protected function param($param = null, $default = null)
	{
		if ($param === null) return $this->params;
		return \Arr::get($this->params, $param, $default);
	}
}