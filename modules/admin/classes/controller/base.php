<?php

namespace Admin;

class Controller_Base extends \Controller {
	
	protected $data = array();
    protected $template = 'admin/dashboard.twig';
    protected $status = 200;
    protected $headers = array();
    protected $assets = array();
    protected $js = array();
	
	public function before() {
		
		if (!\CMF\Auth::check('admin')) {
            \Response::redirect(\Uri::base(false)."admin/login?next=".\Uri::string(), 'location');
        }
        
        // Allows us to set the interface template via an integer
        $this->mode = \Input::param('_mode', 'default');
        $this->interface_template = \Config::get('cmf.admin.interface_templates.'.$this->mode);
        
        // A unique ID that can be passed through
        $this->cid = \Input::param('_cid', 'none');
		
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
        
        // HTML Title
        $this->admin_title = \Config::get("cmf.admin.title", array());
        
        return \Response::forge(\View::forge($this->template, $this->data, false), $this->status, $this->headers);
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
    
    protected function show404($msg)
    {
        $this->template = 'admin/errors/404.twig';
        $this->status = 404;
        $this->data = array( 'msg' => $msg );
        return;
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