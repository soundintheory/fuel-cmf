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
    public $data = array();
    public $template = null;
    public $status = 200;
    public $headers = array();
    public $model = null;
    
    public function action_index()
    {
        // Nothing
    }
    
    public function before()
    {
        
    }
    
    /**
     * Whether or not to attempt caching for this controller
     * @return boolean
     */
    public function cache()
    {
        return true;
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
	    $model = $this->model = \CMF::currentModel();
        \CMF::$routed = true;
	    
	    // Return the normal 404 error if not found
	    if (is_null($model)) {
            $action = trim(\Input::uri(), '/');
            if (!empty($action)) return \Request::forge('base/'.$action, false)->execute()->response();
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
            return \Request::forge('base/'.$action, false)->execute()->response();
        }
	    
	}
    
    public function show404($msg = null)
    {
        return \Response::forge(\View::forge('errors/404.twig', array( 'msg' => ($msg !== null ? $msg : "That page couldn't be found!") )), 404);
    }
	
	/**
	 * Automatically locates the ViewModel for the configured template, unless a response has already been generated.
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
        if (is_null($this->model)) $this->model = \CMF::currentModel();
        
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
        
        // Still route through the CMF if it hasn't been touched
        if (!\CMF::$routed) {
            return call_user_func_array(array($this, 'action_404'), $arguments);
        }
        
        // if not, we got ourselfs a genuine 404!
        return \Response::forge(\View::forge('errors/404.twig', array( 'msg' => "That page couldn't be found!" )), 404);
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
