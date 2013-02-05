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
		
		$metadata = $class_name::metadata();
		\Admin::$current_class = $this->current_class = $class_name;
		
		// Superlock: don't let them create one!!
		if ($this->superlock = $class_name::superlock()) {
			$default_redirect = \Uri::base(false)."admin/$table_name";
			\Response::redirect($default_redirect, 'location');
		}
	    
	    // Create a fresh model
	    $model = new $class_name();
	    
	    // Get stuff ready for the template
	    $this->form = new ModelForm($metadata, $model);
	    $this->plural = $class_name::plural();
		$this->singular = $class_name::singular();
		$this->icon = $class_name::icon();
		$this->static = $class_name::_static();
		$this->table_name = $metadata->table['name'];
		$this->model = $model;
		$this->template = 'admin/item/create.twig';
	    
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
		\Admin::$current_class = $this->current_class = $class_name;
		
		$this->plural = $class_name::plural();
		$this->singular = $class_name::singular();
		
		// Load up the model with the Id
	    $model = $class_name::find($id);
	    if (is_null($model)) return $this->show404("That ".$this->singular." Doesn't Exist!");
	    
	   	// Get stuff ready for the template
	   	$this->form = new ModelForm($metadata, $model);
		$this->icon = $class_name::icon();
		$this->static = $class_name::_static();
		$this->table_name = $metadata->table['name'];
		$this->model = $model;
		$this->superlock = $class_name::superlock();
		$this->template = 'admin/item/edit.twig';
	    
	}
	
	/**
	 * Either creates or updates a model depending on whether an ID is passed in.
	 */
	public function action_save($table_name, $id = null)
	{
	    // Find class name and metadata etc
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404("Can't find that type!");
		
		$metadata = $class_name::metadata();
		\Admin::$current_class = $this->current_class = $class_name;
		$actioned = "saved";
		
		// Find the model, or create a new one if there's no ID
		if (isset($id)) {
			$model = $class_name::find($id);
			if (is_null($model)) return $this->show404("Can't find that model!");
		} else {
			$model = new $class_name();
			$actioned = "created";
		}
		
		//print_r(\Input::post());
		//exit();
		
		// Populate the model with posted data
		$model->populate(\Input::post());
		
		// Validate the model
	    if ($model->validate()) {
	    	
	    	// Save any uploads
	    	\Upload::save();
	    	
	    	$em = \DoctrineFuel::manager();
	    	$em->persist($model);
	        $em->flush();
	        
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
	        		\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-success' ), 'msg' => $class_name::singular()." $actioned successfully" ));
	        		\Response::redirect(\Uri::base(false)."admin/$table_name/".$model->get('id')."/edit", 'location');
	        		break;
	        	
	        }
	        
	    }
	    
	    // If it's come this far, we have a problem. Render out the form with the errors...
	    $this->form = new ModelForm($metadata, $model);
		$this->icon = $class_name::icon();
		$this->table_name = $metadata->table['name'];
		$this->model = $model;
		$this->template = 'admin/item/edit.twig';
	    \Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => "There were errors when saving the ".strtolower($class_name::singular()) ));
	    
	}
	
	/**
	 * Deletes a model
	 */
	public function action_delete($table_name, $id)
	{
	    $em = \DoctrineFuel::manager();
	    $class_name = \Admin::getClassForTable($table_name);
	    
	    // Superlock: don't let them delete it!!
		if ($class_name::superlock()) {
			$default_redirect = \Uri::base(false)."admin/$table_name";
			\Response::redirect($default_redirect, 'location');
		}
	    
	    $singular = $class_name::singular();
	    $entity = $class_name::find($id);
	    
	    if (!is_null($entity)) {
	        $em->remove($entity);
	        $em->flush();
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
	
}