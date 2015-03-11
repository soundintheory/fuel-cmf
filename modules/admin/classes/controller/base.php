<?php

namespace Admin;

class Controller_Base extends \Controller {
	
	public $data = array();
    public $template = 'admin/dashboard.twig';
    public $status = 200;
    public $headers = array();
    public $assets = array();
    public $js = array();
    
    /**
     * Whether or not to attempt caching for this controller
     * @return boolean
     */
    public function cache()
    {
        return false;
    }
	
	public function before() {
		
		if (!\CMF\Auth::check(null, 'view', 'admin_site')) {
            \Response::redirect(\Uri::base(false)."admin/login?next=".\Uri::string(), 'location');
        }
        
        // Find the lang from the session, or from the user
        if ($this->lang_enabled = \Config::get('cmf.languages.enabled', false)) {
            $lang = \Session::get('cmf.admin.language');
            if ($lang === null) {
                $user = \CMF\Auth::current_user();
                $lang = $user->default_language;
            }
            if (!empty($lang) && strlen($lang) !== 0 && $lang !== null) \CMF::setLang($lang);
        }
        
        // Allows us to set the interface template via an integer
        $this->mode = \Input::param('_mode', 'default');
        $this->interface_template = \Config::get('cmf.admin.interface_templates.'.$this->mode);
        
        // A unique ID that can be passed through
        $this->cid = \Input::param('_cid', 'none');
        $this->current_lang = \Lang::get_lang();
        $this->fallback_lang = \Lang::$fallback;
		
	}
	
	public function after($response)
    {
        // If a response has been provided, just go with it
        if (!is_null($response)) return $response;
        
        // Populate the sidebar
        $this->sidebar = \Admin::getSidebarConfig();
        
        // Add assets
        $this->data['assets'] = array(
            'js' => \Arr::get($this->assets, 'js', array()),
            'css' => \Arr::get($this->assets, 'css', array())
        );
        
        // JSON encode the JS
        $this->data['js_data'] = json_encode($this->js);
        
        // Info about the user
        $user = \CMF\Auth::current_user();
        $this->user = array(
            'account' => '/admin/users/'.$user->id.'/edit',
            'username' => $user->username,
            'super_user' => $user->super_user
        );
        
        // Some vital settings
        $this->admin_title = \Config::get("cmf.admin.title", array());
        $this->base_url = \Admin::$base;
        $this->modules = \Config::get('cmf.admin.modules', false);
        $this->current_module = \Admin::$current_module;
        $this->current_class = \Admin::$current_class;
        $this->dashboard_title = \Config::get('cmf.admin.modules.'.\Admin::$current_module.'.title', 'Dashboard');
        
        $this->headers['X-XSS-Protection'] = 0;
        
        return \Response::forge(\View::forge($this->template, $this->data, false), $this->status, $this->headers);
    }
    
    public function action_phpinfo()
    {
        phpinfo();
        exit();
    }
    
    public function addAssets($new_assets)
	{
	    foreach ($new_assets as $type => $assets)
	    {
	        foreach ($assets as $asset)
	        {
	            $this->addAsset($type, $asset);
	        }
	    }
	}
	
	protected function addAsset($type, $src)
	{
	    $current = \Arr::get($this->assets, $type, array());
	    if (!in_array($src, $current)) $current[] = $src;
	    $this->assets[$type] = $current;
	}
    
    protected function customPageOr404($segments, $msg)
    {
        $main = array_shift($segments);
        $method = strtolower(\Input::method());
        $action = ((count($segments) > 0) ? array_shift($segments) : 'index');
        if ($action == null) $action = 'index';
        $controller = 'Controller_Admin_'.ucfirst($main);
        $action = str_replace('-', '_', $action);
        
        // Return normal 404 if we can't find the controller class
        if (!class_exists($controller)) return $this->show404($msg);
        
        // Load the controller using reflection
        $class = new \ReflectionClass($controller);
        $request = \Request::active();
        $controller_instance = new $controller($request);
        $controller_instance->template = 'admin/'.$main.'.twig';
        
        // Return normal 404 if we can't find the action method
        if (!$class->hasMethod($method."_".$action)) {
            $method = 'action';
            if (!$class->hasMethod("action_".$action)) return $this->show404($msg);
        }
        
        // Run through the before > action > after process
        $action_method = $class->getMethod($method."_".$action);
        $class->hasMethod('before') and $class->getMethod('before')->invoke($controller_instance);
        $response = $action_method->invokeArgs($controller_instance, $request->method_params);
        $class->hasMethod('after') and $response = $class->getMethod('after')->invoke($controller_instance, $response);
        
        return $response;
        
    }
    
    protected function show404($msg)
    {
        $this->template = 'admin/errors/404.twig';
        $this->status = 404;
        $this->data = array( 'msg' => $msg );
        return;
    }
    
    protected function show403($msg)
    {
        $this->template = 'admin/errors/403.twig';
        $this->status = 403;
        $this->data = array( 'msg' => $msg );
        return;
    }
    
    public function router($method, $arguments)
    {
        $input_method = strtolower(\Input::method());
        $method = str_replace('-', '_', $method);
        $controller_method = $input_method.'_'.$method;
        if (method_exists($this, $controller_method))
        {
            return call_user_func_array(array($this, $controller_method), $arguments);
        }
        
        $controller_method = 'action_'.$method;
        if (method_exists($this, $controller_method))
        {
            return call_user_func_array(array($this, $controller_method), $arguments);
        }
        
        // if not, we got ourselfs a genuine 404!
        $this->show404('That page could not be found!');
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

    public function action_all_links($key=null){

        $defaults = \CMF\Field\Object\Link::getDefaults();

        $options = \CMF\Field\Object\Link::getOptionsStatic($defaults,null);

        if(!$key){
            $output = array();
            foreach (array_keys($options) as $key ) {
                $output[] = $key;
            } 
        }else{
            foreach ($options[$key] as $key=>$value ) {
                $output[$key] = $value;
            } 
        }
        $output = json_encode ($output);
        echo $output;
        exit();
    }
	
}