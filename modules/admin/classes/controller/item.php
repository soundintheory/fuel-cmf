<?php

namespace Admin;

use \DoctrineFuel,
	\CMF\Auth,
	\CMF\Admin\ModelForm;

/**
 * Provides the CRUD actions for models in the admin system
 */
class Controller_Item extends Controller_Base {
	
	/**
	 * Renders the empty form for creating an item
	 */
	public function action_create($table_name)
	{
		// Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404("Can't find that type!");
		
		$can_edit = \CMF\Auth::can('edit', $class_name);
		
		if (!$can_edit) {
			return $this->show403("You're not allowed to create ".strtolower($class_name::plural())."!");
		}
		
		$metadata = $class_name::metadata();
		\Admin::setCurrentClass($class_name);
		
		// Superlock: don't let them create one!!
		if ($this->superlock = $class_name::superlock()) {
			$default_redirect = \Uri::base(false)."admin/$table_name";
			\Response::redirect($default_redirect, 'location');
		}

		$root_class = $metadata->rootEntityName;
		$root_metadata = $root_class::metadata();
	    
	    // Create a fresh model
	    $model = new $class_name();
	    
	    // Get stuff ready for the template
	    $this->js['model'] = $class_name;
	    $this->form = new ModelForm($metadata, $model);
		$this->static = $class_name::_static();
		$this->table_name = $metadata->table['name'];
		$this->root_table_name = $root_metadata->table['name'];
		$this->root_plural = $root_class::plural();
		$this->model = $model;
		$this->template = 'admin/item/create.twig';
		$this->qs = \Uri::build_query_string(\Input::get());

		// Permissions
		$this->can_edit = $can_edit;
		$this->can_create = \CMF\Auth::can('create', $class_name);
		$this->can_delete = \CMF\Auth::can('delete', $class_name) && !$class_name::_static();
	    
	}
	
	/**
	 * Given an ID, renders the form to edit an item.
	 */
	public function action_edit($table_name, $id = null)
	{
		// Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404("Can't find that type!");
		
		$metadata = $class_name::metadata();
		\Admin::setCurrentClass($class_name);
		
		// Load up the model with the Id
	    $model = $class_name::find($id);
	    if (is_null($model)) {
	    	\Response::redirect(\Uri::base(false)."admin/$table_name", 'location');
	    }
	    
	    $can_edit = \CMF\Auth::can('edit', $model);
		if (!$can_edit) {
			return $this->show403("You're not allowed to edit this ".strtolower($class_name::singular())."!");
		}

		$root_class = $metadata->rootEntityName;
		$root_metadata = $root_class::metadata();
	    
	   	// Get stuff ready for the template
	   	$this->actions = $class_name::actions();
	   	$this->form = new ModelForm($metadata, $model);
		$this->static = $class_name::_static();
		$this->table_name = $metadata->table['name'];
		$this->root_table_name = $root_metadata->table['name'];
		$this->root_plural = $root_class::plural();
		$this->model = $model;
		$this->js['model'] = $class_name;
		$this->js['item_id'] = $model->id;
		$this->js['table_name'] = $table_name;
		$this->superlock = $class_name::superlock();
		$this->template = 'admin/item/edit.twig';
		$this->qs = \Uri::build_query_string(\Input::get());
		
		// Permissions
		$this->can_edit = $can_edit;
		$this->can_create = \CMF\Auth::can('create', $class_name);
		$this->can_delete = \CMF\Auth::can('delete', $class_name) && !$class_name::_static();
	    
	}
	/**
	 * Given an ID, duplicates the form to edit an item.
	 */
	public function action_duplicate($table_name, $id = null)
	{
		ini_set('memory_limit', '100M');
		set_time_limit(60);
		
		// Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404("Can't find that type!");
		
		$metadata = $class_name::metadata();
		\Admin::setCurrentClass($class_name);
		
		//$this->plural = $class_name::plural();
		//$this->singular = $class_name::singular();
		
		// Load up the model with the Id
    	$original_model = $class_name::find($id);
    	
	    if (is_null($original_model)) {
	    	\Response::redirect(\Uri::base(false)."admin/$table_name", 'location');
	    }
	    
	    $error = null;
	    $model = null;
	    
	    try {
	    	$model = clone $original_model;
	    	$model->set('id', null);
	    	$model->set('url', null);
	    	\D::manager()->persist($model);
			\D::manager()->flush();
	    } catch (\Exception $e) {
	    	$error = $e->getMessage();
	    }
    	
	    if ($error !== null) {
	    	$default_redirect = \Uri::base(false)."admin/$table_name";
			\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => "Could not duplicate item: ".$error ));
			\Response::redirect(\Input::referrer($default_redirect), 'location');
	    }
		
		\Response::redirect(\Uri::base(false)."admin/$table_name/".$model->id."/edit", 'location');
		return $this->show403("Problem saving the data!");
	}
	
	/**
	 * Either creates or updates a model depending on whether an ID is passed in.
	 */
	public function action_save($table_name, $id = null, $method = null)
	{
	    // Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) {
			return $this->customPageOr404(array($table_name, $method), "Can't find that type!");
		}
		
		$can_edit = \CMF\Auth::can('edit', $class_name);
		
		if (!$can_edit) {
			return $this->show403("You're not allowed to edit ".strtolower($class_name::plural())."!");
		}
		
		$metadata = $class_name::metadata();
		$plural = $class_name::plural();
		$singular = $class_name::singular();

		if (\Input::param('alias', false) !== false) {
			$plural = 'Links';
			$singular = 'Link';
		}

		\Admin::setCurrentClass($class_name);
		$actioned = "saved";
		
		// Find the model, or create a new one if there's no ID
		if ($exists = isset($id) && !empty($id)) {
			$model = $class_name::find($id);
			if (is_null($model)) return $this->show404("Can't find that model!");
		} else {
			$model = new $class_name();
			$actioned = "created";
		}
		$create_new = \Input::post('create_new');
		
		// Populate the model with posted data
		$model->populate(\Input::post());
		
		// Validate the model
	    if ($model->validate(null, null, array('id', 'pos'))) {
	    	
	    	$em = \D::manager();
	    	$em->persist($model);
	        $em->flush();

	        if (!$exists && $model->_has_processing_errors) {

	        	// Try populating the model again if we've had errors - they could be to do with unit of work
	        	$model->populate(\Input::post());
        		$em->persist($model);
        	    $em->flush();

	        }
	        
	        // Do something depending on what mode we're in...
	        switch (\Input::param('_mode', 'default')) {
	        	
	        	// Renders a page with some JS that will close the fancybox popup
	        	case 'inline':
	        		$options = $class_name::options();
	        		$ids = array_keys($options);
	        		$pos = array_search(strval($model->id), $ids);
	        		if ($pos === false) $pos = 0;
	        		
	        		$this->options = $options;
	        		$this->pos = $pos;
	        		$this->model = $model;
	        		$this->template = 'admin/item/saved-inline.twig';
	        		return;
	        		break;
	        	
	        	default:

	        		$qs = \Uri::build_query_string(\Input::get());
	        		if (strlen($qs) > 0) $qs = '?'.$qs;

	        		\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-success' ), 'msg' => "$singular $actioned successfully" ));
	        		if($create_new) \Response::redirect(\Uri::base(false)."admin/$table_name/create$qs", 'location');
	        		\Response::redirect(\Uri::base(false)."admin/$table_name/".$model->get('id')."/edit$qs", 'location');
	        		break;
	        	
	        }
	        
	    }
	    
	    // If it's come this far, we have a problem. Render out the form with the errors...
	    $this->actions = $class_name::actions();
	    $this->form = new ModelForm($metadata, $model);
		$this->table_name = $metadata->table['name'];
		$this->model = $model;
		$this->qs = \Uri::build_query_string(\Input::get());
		$this->template = 'admin/item/edit.twig';
		
		// Permissions
		$this->can_edit = $can_edit;
		$this->can_create = \CMF\Auth::can('create', $class_name);
		$this->can_delete = \CMF\Auth::can('delete', $class_name);
		
		if ($exists) {
			$this->js['model'] = $class_name;
			$this->js['item_id'] = $model->id;
			$this->js['table_name'] = $table_name;
		}		
		
	    \Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => "There were errors when saving the ".strtolower($class_name::singular()) ));
	    
	}
	
	/**
	 * Deletes a model
	 */
	public function action_delete($table_name, $id)
	{
	    $em = \D::manager();
	    $class_name = \Admin::getClassForTable($table_name);
	    
	    $can_delete = $class_name::superlock() === false && \CMF\Auth::can('delete', $class_name) === true;
	    
	    // Don't let them delete it if they're not allowed!!
		if (!$can_delete) {
			return $this->show403("You're not allowed to delete ".strtolower($class_name::plural())."!");
		}
	    
	    \Admin::setCurrentClass($class_name);
	    $singular = $class_name::singular();
	    $entity = $class_name::find($id);
	    $error = null;

	    $plural = $class_name::plural();
	    $singular = $class_name::singular();

	    if (\Input::param('alias', false) !== false) {
	    	$plural = 'Links';
	    	$singular = 'Link';
	    }
	    
	    if (!is_null($entity)) {
	        try {
	        	$em->remove($entity);
	        	$em->flush();
	        } catch (\Exception $e) {
	        	$error = $e->getMessage();
	        }
	    }
	    
	    if (!empty($error)) {
	    	$default_redirect = \Uri::base(false)."admin/$table_name";
			\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => "Could not delete item: ".$error ));
			\Response::redirect(\Input::referrer($default_redirect), 'location');
	    }
	    
	    // Do something depending on what mode we're in...
	    switch (\Input::param('_mode', 'default')) {
	    	
	    	// Renders a page with some JS that will close the fancybox popup
	    	case 'inline':
	    		$this->id = $id;
	    		$this->template = 'admin/item/deleted-inline.twig';
	    		return;
	    		break;
	    	
	    	default:
	    		$default_redirect = \Uri::base(false)."admin/$table_name";
			    
			    \Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-success' ), 'msg' => "The ".strtolower($singular)." was deleted" ));
			    \Response::redirect(\Input::referrer($default_redirect), 'location');
	    		break;
	    	
	    }
	    
	}
	
	/**
	 * For asyncronous saving - populates a model with the posted data and responds in JSON
	 */
	public function action_populate($table_name, $id=null)
	{
		// Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404("Can't find that type!");
		
		if (!\CMF\Auth::can('edit', $class_name)) {
			return $this->show403("You're not allowed to edit that!");
		}
		
		// Set the output content type
		$this->headers = array("Content-Type: text/plain");
		
		// If $id is null, we're populating multiple items
		if ($id === null) {
			
			// Construct the output
			$result = array( 'success' => true, 'num_updated' => 0 );
			$post_data = \Input::post();
			$ids = array_keys($post_data);
			$em = \D::manager();
			
			if (count($ids) == 0) {
				return \Response::forge(json_encode($result), $this->status, $this->headers);
			}
			
			// Get the items we need to save
			$items = $class_name::select('item')
			->where('item.id IN(?1)')
			->setParameter(1, $ids)
			->getQuery()
			->getResult();
			
			if (count($items) == 0) {
				return \Response::forge(json_encode($result), $this->status, $this->headers);
			}
			
			foreach ($items as $item) {
				
				$id = $item->id;
				if (!isset($post_data[$id])) continue;
				
				$result['num_updated'] += 1;
				$data = $post_data[$id];
				$item->populate($data, false);
				
				if (!$item->validate()) {
					$result['success'] = false;
				}
				
				$em->persist($item);
				
			}
			
			// Try and save them all
			try {
		        $em->flush();
			} catch (\Exception $e) {
				$result['success'] = false;
				$result['error'] = $e->getMessage();
			}
			
			// Return the JSON response
	        return \Response::forge(json_encode($result), $this->status, $this->headers);
			
		}
		
		// Find the model, return 404 if not found
		$model = $class_name::find($id);
		if (is_null($model)) return $this->show404("Can't find that model!");
		
		// Populate with the POST data
		$model->populate(\Input::post(), false);
		
		// Construct the output
		$result = array( 'success' => false );
		
		// Check validation
		if ($model->validate()) {
			$result['success'] = true;
		} else {
			$result['validation_errors'] = $model->errors;
		}
		
		// Try and save it
		try {
			
			$em = \D::manager();
			$em->persist($model);
			$em->flush();
			
			$result['updated_at'] = $model->updated_at->format("d/m/Y \\a\\t H:i:s");
	        
		} catch (\Exception $e) {
			$result['success'] = false;
			$result['error'] = $e->getMessage();
		}
		
		// Return the JSON response
        return \Response::forge(json_encode($result), $this->status, $this->headers);
		
	}
	
	/**
	 * Processes an action on the item from the $_actions array
	 */
	public function action_action($table_name, $id, $action_id)
	{
		// Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		\Admin::setCurrentClass($class_name);
		
		if ($class_name === false) {
			return $this->customPageOr404(array($table_name, $action_id), "Can't find that type!");
		}
		
		// Load up the model with the Id
	    $model = $class_name::find($id);
	    if (is_null($model)) {
	    	\Response::redirect(\Uri::base(false)."admin/$table_name", 'location');
	    }
		
		$actions = $class_name::actions();
		if (!isset($actions[$action_id])) {
			return $this->customPageOr404(array($table_name, $action_id), "The page you requested could not be found");
		}
		
		$action = $actions[$action_id];
		$type = \Arr::get($action, 'type');
		
		switch ($type) {
			
			case 'method':
				
				// Call a method on the model...
				$method = "action_".\Arr::get($action, 'method', $action_id);
				$result = null;
				$error = null;
				
				try {
					$result = $model->$method($table_name);
				} catch (\Exception $e) {
					$error = $e->getMessage();
				}
				
				if (!is_null($error)) {
					\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => $error ));
				} else {
					\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-success' ), 'msg' => $result ));
				}
				
				$redirect = \Input::referrer(\Uri::base(false)."admin/$table_name/$id");
				\Response::redirect($redirect, 'location');
				
			break;
			default:
				
				return $this->customPageOr404(array($table_name, $action_id), "The page you requested could not be found");
				
			break;
			
		}
		
	}
	
}