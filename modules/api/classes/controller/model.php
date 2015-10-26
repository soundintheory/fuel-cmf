<?php

namespace Api;

/**
 * Deals with dynamic requests for models
 * 
 * @package  app
 * @extends  Controller
 */
class Controller_Model extends Controller_Resource
{
	protected $params = array();
	protected $unique;
	protected $model;
	protected $query;
	protected $singular;
	protected $plural;
	protected $id;

	/**
	 * If no action is provided, use one of the BREAD ones
	 */
	public function router($resource, $arguments)
	{
		$id = array_values(array_filter($this->expand(\Input::param('id', \Arr::get($arguments, 'id'))), 'is_numeric'));
		$single = count($id) == 1;

		$this->unique = \Arr::get($arguments, 'unique') || $single;
		$this->model = \Arr::get($arguments, 'model');
		$this->singular = \Arr::get($arguments, 'singular');
		$this->plural = \Arr::get($arguments, 'plural');

		if (count($id)) {
			$id_key = $single ? 'id' : 'ids';

			$this->params = array(
				$id_key => $single ? $id[0] : $id
			);

			$this->id = $id[0];
		}

		if ($this->unique && !count($id))
			throw new \HttpException('A single item was requested, but no ID was specified', \HttpException::BAD_REQUEST);

		if (!$resource)
		{
			$method = strtolower(\Input::method());
			switch ($method) {
				case 'get':
					$resource = $single ? 'read' : 'browse';
					break;
				case 'post':
					$resource = $single ? 'edit' : 'add';
					break;
				case 'put':
				case 'patch':
					$resource = $single ? 'edit' : null;
					break;
				case 'delete':
					$resource = 'delete';
					break;
				case 'options':
				case 'head':
					throw new \HttpException('HEAD and OPTIONS methods have not been implemented yet!', \HttpException::NOT_IMPLEMENTED);
					break;
			}
		}

		return parent::router($resource, $arguments);
	}

	/**
	 * Return the query builder for this model
	 * 
	 * @return \Api\Rest_Query
	 */
	protected function getQuery($params = array())
	{
		if (!$this->query) {
			$this->query = new Rest_Query($this->model, $this->plural, $this->unique ? $this->singular : $this->plural, \Arr::merge($this->params, $params));
		}
		return $this->query;
	}

	/**
	 * Return a list of entities
	 * @return array
	 */
	public function action_browse()
	{
		$class = $this->class;
		$result = $this->getQuery()->getResult();
		
		return $result;
	}

	/**
	 * Return a single entity
	 * @return array
	 */
	public function action_read()
	{
		$class = $this->class;
		$result = $this->getQuery()->getResult();

		if (!count($result))
			throw new \HttpException(ucfirst($this->singular).' was not found', \HttpException::NOT_FOUND);
		
		return $result;
	}

	/**
	 * Attempt to find a single entity and inject request params into it
	 * @return array
	 */
	public function action_edit()
	{
		$model = $this->model;
		$data = \Arr::get(\Input::json(), $this->singular, array());
		$id = $this->id;
		$entity = null;

		if (!$id && isset($data['id']) && $data['id']) {
			$id = $data['id'];
		}

		if (!$id) {
			throw new \HttpException('An edit action was called, but no ID was specified', \HttpException::BAD_REQUEST);
		}

		$entity = $model::find($id);
		if (is_null($entity)) {
			throw new \HttpException(ucfirst($this->singular).' was not found', \HttpException::NOT_FOUND);
		}

		$entity->populate($data);
		$success = true;
		$msg = '';

		try {
			\D::manager()->persist($entity);
			\D::manager()->flush();
		} catch (\Exception $e) {
			$success = false;
			$msg = $e->getMessage();
		}

		if ($success) {
			$this->http_status = 204;
			return array();
			//$this->response->set_header('Location', \Uri::base(false).'api/'.$this->singular.'/'.$entity->id);
		} else {
			print_r($msg); exit();
		}
	}

	/**
	 * Create a new entity and inject request params into it
	 * @return array
	 */
	public function action_add()
	{
		$model = $this->model;
		$data = \Arr::get(\Input::json(), $this->singular, array());
		$entity = null;

		if (isset($data['id']) && $data['id']) {
			$entity = $model::find($data['id']);
		}

		if (!$entity) {
			$entity = new $model();
		}

		$entity->populate($data);
		$success = true;
		$msg = '';

		try {
			\D::manager()->persist($entity);
			\D::manager()->flush();
		} catch (\Exception $e) {
			$success = false;
			$msg = $e->getMessage();
		}

		if ($success) {

			$this->http_status = 201;
			$this->params['id'] = $this->id = $entity->id;
			$this->unique = true;
			$this->response->set_header('Location', \Uri::base(false).'api/'.$this->singular.'/'.$entity->id);

			$result = $this->getQuery()->getResult();

			if (!count($result))
				throw new \HttpException(ucfirst($this->singular).' was not found', \HttpException::NOT_FOUND);
			
			return $result;

		}

		return array( 'error' => $msg );
	}

	/**
	 * Attempt to find a single entity and delete it
	 * @return array
	 */
	public function action_delete()
	{
		return array(
			'action' => 'delete',
			'model' => $this->model
		);
	}

	/**
	 * Transforms a param into an array, if it isn't one already
	 * 
	 * @return array
	 */
	public function expand($value, $sep = ',')
	{
		if (is_array($value)) return $value;
		if (empty($value)) return array();

		return explode($sep, trim($value, $sep));
	}
}