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
	protected $output;
	protected $root;
	protected $rootOuput;
	protected $reserved_names = array('fields');
	protected $excluded_types = array('binary');
	protected $unselected_joins = array();

	/**
	 * Initialise the wrapper and it's query builder instance
	 * 
	 * @param string $class The model class
	 */
	public function __construct($class, $root, $rootOutput, $params = array())
	{
		$this->class = $class;
		$this->root = $root;
		$this->rootOutput = $rootOutput;
		$this->params = \Input::param();
		if (is_array($params)) {
			$this->params = \Arr::merge($this->params, $params);
		}
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

		// Figure out which fields we need to select
		$fields = array_merge(array($alias), $metadata->getAssociationNames());
		$param_fields = $this->expand($this->param('fields'));
		if (\Arr::is_assoc($param_fields)) {
			$param_fields = $this->expand(\Arr::get($param_fields, $root, array()));
		} else if ($root != $this->root) {
			$param_fields = array();
		}

		if (empty($param_fields) && $root == $this->root && property_exists($target_class, '_rest_fields')) {
			$param_fields = $target_class::$_rest_fields;
		}

		if (\Arr::get($param_fields, 0, '') == '_default_' && property_exists($target_class, '_rest_fields')) {
			array_shift($param_fields);
			$param_fields = array_unique(array_merge($param_fields, $target_class::$_rest_fields));
		}

		$has_param_fields = !empty($param_fields);

		if ($has_param_fields && !in_array('id', $param_fields)) array_unshift($param_fields, 'id');

		// If this is a root item, fields have been defined, and none of those fields are associations then we
		// only need to select the bare minimum of fields
		if ($root == $this->root && $has_param_fields) {
			$associations = array_filter($param_fields, function($field) use($metadata) {
				return $metadata->hasAssociation($field);
			});
			if (empty($associations)) {
				$prefix = "$alias.";
				$fields = $param_fields;
			}
		}

		if ($has_param_fields) {
			$this->fields[$root] = $param_fields;
		}

		//var_dump("SELECTING FOR $root_prefix$root");
		//var_dump($fields);

		//var_dump($fields); exit();

		foreach ($fields as $field) {

			if (in_array($field, $this->reserved_names)) continue;

			if ($field == $alias && !in_array($prefix.$field, $this->selects)) {
				$builder->addSelect($prefix.$field);
				$this->selects[$root_prefix.$root] = $prefix.$field;
				//var_dump("SELECT ALIAS $prefix$field ($root_prefix$root)");
				continue;
			}

			if ($is_association = $metadata->hasAssociation($field)) {

				// Don't select inverse relationships that haven't been explicitly requested, or if there are
				// fields requested and the association is not included
				if (($has_param_fields && !in_array($field, $param_fields)) ||
					$metadata->isAssociationInverseSide($field) && !in_array($field, $param_fields)) {
					continue;
				}

				// Work out the resource name for this field, so we can sideload the records
				$resource = \Inflector::pluralize($field);
				$resource_class = $metadata->getAssociationTargetClass($field);
				if (!isset($this->links[$root])) {
					$this->links[$root] = array();
				}

				if (!isset($this->links[$root][$resource])) {
					$this->links[$root][$resource] = array(
						'field' => $field,
						'class' => $resource_class,
						'parent' => $root,
						'collection' => $metadata->isCollectionValuedAssociation($field),
						'fields' => $param_fields
					);
				}

				$join_alias = '_'.trim($alias.'_'.$field, '_');
				$builder->leftJoin("$alias.$field", $join_alias);
				$this->joins[$join_alias] = $resource_class;
				$field = $join_alias;

				if (!isset($this->selects[$root.'.'.$resource])) {
					$next_level[] = array($resource, $join_alias, $resource_class, $level + 1, $root.'.');
				}

			} else if (!$metadata->hasField($field)) {
				continue;
			}

			if (!($is_association && isset($this->selects[$root_prefix.$root])) && !in_array($prefix.$field, $this->selects)) {
				$builder->addSelect($prefix.$field);
				//var_dump("SELECT $prefix$field ($root_prefix$root)");
				$this->selects[$root_prefix.$root] = $prefix.$field;
			}
			
		}

		foreach ($next_level as $next_level_call) {
			$this->buildQuerySelect($next_level_call[0], $next_level_call[1], $next_level_call[2], $next_level_call[3], $next_level_call[4]);
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
		if (empty($sort) && empty($model_sort)) return;

		$builder = $this->getQueryBuilder();
		$metadata = $this->getMetadata();
		$sort = $this->expand($sort);

		if (empty($sort)) {

			foreach ($model_sort as $field => $dir) {
				$sort[] = (strtolower($dir) == 'desc' ? '-' : '').$field;
			}

		}

		foreach ($sort as $value) {

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
	 * Add joins to a query builder instance
	 *
	 * @return void
	 */
	protected function buildQueryJoins()
	{
		
	}

	/**
	 * The doctrine query builder instance
	 * 
	 * @return \Doctrine\ORM\QueryBuilder 
	 */
	public function getQueryBuilder()
	{
		if (!$this->builder) 
		{
			// Initialise builder and other vars
			$this->joins = array();
			$this->links = array();
			$this->fields = array();
			$this->selects = array();
			$this->output = array();
			$this->builder = \D::manager()->createQueryBuilder()->from($this->class, 'item');

			// Add each part of the query separately
			$this->buildQuerySelect();
			$this->buildQueryWhere();
			$this->buildQuerySort();

			// This must be the last one, so we can perform the limit sub query
			$this->buildQueryLimit();
		}

		return $this->builder;
	}

	protected function processResults($resource, $class, &$results)
	{
		$links = \Arr::get($this->links, $resource);
		$fields = $this->getFieldsForModel($class, $resource);

		$has_links = !empty($links);

		foreach ($results as $num => $result) {

			$result = \Arr::filter_keys($result, $fields);
			if ($has_links) $result = $this->processSingleResult($result, $links);
			
			$results[$num] = $result;
		}
	}

	protected function processSingleResult($result, &$links)
	{
		foreach ($links as $link_key => $link) {

			$field = $link['field'];

			if (!empty($result[$field]) && is_array($result[$field])) {

				$link_output = (!isset($this->output[$link_key])) ? array() : $this->output[$link_key];

				if ($link['collection']) {
					$ids = \Arr::pluck($result[$field], 'id');
					$values = $result[$field];
				} else {
					$ids = $result[$field]['id'];
					$values = array($result[$field]);
				}

				// Filter the array so we don't process duplicates
				$values = array_filter($values, function($value) use($link_output) {
					return !isset($link_output[$value['id']]);
				});

				if (count($values)) {

					// Process these relations before they get filed away
					$this->processResults($link_key, \Arr::get($link, 'class'), $values);

					foreach ($values as $vnum => $value) {
						$link_output[$value['id']] = $value;
					}
				}

				$result[$field] = $ids;
				$this->output[$link_key] = $link_output;
			}

		}

		return $result;
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

	public function getResult()
	{
		$query = $this->getQueryBuilder()->getQuery();
		$query->setHydrationMode(Query::HYDRATE_ARRAY);
		
		if (!empty($this->links)) {
			$paginator = new Paginator($query, true);
			$results = iterator_to_array($paginator->getIterator());
			$this->processResults($this->root, $this->class, $results);

		} else {
			$results = $query->getResult(Query::HYDRATE_ARRAY);
		}

		// Transform the results into something a little more API-friendly
		$this->output[$this->rootOutput] = $results;
		$this->output[$this->root] = $results;
		$this->output = array_map('array_values', array_filter($this->output));

		if (!isset($this->output[$this->root])) $this->output[$this->root] = array();
		if (!isset($this->output[$this->rootOutput])) $this->output[$this->rootOutput] = array();

		return $this->output;
	}

	/**
	 * The metadata associated with the query's model
	 * 
	 * @return \Doctrine\ORM\Mapping\ClassMetadataInfo
	 */
	public function getMetadata()
	{
		$class = $this->class;
		return $class::metadata();
	}

	/**
	 * Transforms a param into an array, if it isn't one already
	 * 
	 * @return [type] [description]
	 */
	public function expand($value, $sep = ',')
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
	public function param($param = null, $default = null)
	{
		if ($param === null) return $this->params;
		return \Arr::get($this->params, $param, $default);
	}
}