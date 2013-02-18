<?php

namespace CMF\Controller;

/**
 * The Base Controller.
 *
 * This provides all the convenience methods for front end controllers and should always be extended
 * 
 * @package  CMF
 * @extends  Controller
 */
class Base extends \Controller
{
    protected $data = array();
    protected $template = null;
    protected $status = 200;
    protected $headers = array();
    
    protected $model;
    
    public function action_index()
    {
        // Nothing
    }

	/**
	 * Will attempt to find an item based on the current URL, and route it through a controller before returning a 404 error
	 * 
	 * @access  public
	 * @return  Response
	 */
	public function action_404()
	{
	    // Will try to find the model based on the URL
	    $model = \CMF::currentModel();
	    
	    // Return the normal 404 error if not found
	    if (is_null($model)) {
	        return \Response::forge(\View::forge('errors/404.twig', array( 'msg' => "That page couldn't be found!" )), 404);
        }
        
        // So the model was found - check if it has a controller to route to
        $template = \CMF::$template;
        $action = \CMF::$action;
        
	    if (\CMF::hasController($template))
	    {
            $module = \CMF::$module;
            $path = \CMF::$path;
            
            $route = (empty($module) ? '' : $module.'/').$path.(empty($action) ? '' : '/'.$action);
            return \Request::forge($route, false)->execute()->response();
        }
        else if (!empty($action))
        {
            return \Response::forge(\View::forge('errors/404.twig', array( 'msg' => "That page couldn't be found!" )), 404);
        }
        else if (\CMF::$root)
        {
            return \Request::forge('root/'.$action, false)->execute()->response();
        }
	    
	}
	
	/**
	 * Automatically locates the ViewModel for the configured template, unless a response has already been generated
	 * 
	 * @access  public
	 * @return  Response
	 */
	public function after($response)
    {
        // If a response has been provided, just go with it
        if (!is_null($response))
        {
            return $response;
        }
        
        if ($this->status == 404) return \Response::forge(\View::forge('errors/404.twig', array( 'msg' => "That page couldn't be found!" )), 404);
        
        // Get the model - this will have previously been found
        $this->model = !is_null($this->model) ? $this->model : \CMF::currentModel();
        
        if (!isset($this->template)) {
            // Try and find the template from the CMF...
            $this->template = \CMF::$template;
        }
        
        if (is_null($this->template)) return \Response::forge(\View::forge('errors/404.twig', array( 'msg' => "That page couldn't be found!" )), 404);
        
        // Determine whether the ViewModel class exists...
        if (\CMF::hasViewModel($this->template)) {
            
            $viewModel = \ViewModel::forge($this->template, 'view', false);
            $this->bindData($viewModel);
            
            return \Response::forge($viewModel, $this->status, $this->headers);
            
        }
        
        try {
            $viewModel = new \View_Base('view', false, $this->template);
            $this->bindData($viewModel);
            return \Response::forge($viewModel, $this->status, $this->headers);
            
        } catch(\Exception $e) {
            
            return \Response::forge(\View::forge('errors/404.twig', array( 'msg' => "The template '".$this->template."' couldn't be found!" )), 404);
            
        }
        
    }
    
    public function router($method, $arguments)
    {
        $input_method = strtolower(\Input::method());
        $controller_method = $input_method.'_'.$method;
        if (method_exists($this, $controller_method))
        {
            return call_user_func_array(array($this, $controller_method), $arguments);
        }
        
        $controller_method = 'action_'.str_replace('-', '_', $method);
        if (method_exists($this, $controller_method))
        {
            return call_user_func_array(array($this, $controller_method), $arguments);
        }
        
        // if not, we got ourselfs a genuine 404!
		throw new \HttpNotFoundException();
		
    }
    
    protected function bindData($viewModel)
    {
        $viewModel->model = $this->model;
        foreach ($this->data as $key => $val)
        {
            $viewModel->$key = $val;
        }
    }
    
    public function __get($key)
    {
        if (isset($this->data[$key])) return $this->data[$key];
        return null;
    }
    
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }
	
}
