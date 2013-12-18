<?php

namespace CMF;

class Cache {
	
    protected static $modified = false;
	protected static $active = false;
	protected static $uriCacheKey = null;
	protected static $started = false;
	protected static $finished = false;
	protected static $driver;
	
	/**
	 * Runs on 'controller_started' event
	 */
	public static function start()
	{
		$controller = \Request::active()->controller_instance;
		$nocache = \Input::param('nocache', \Session::get_flash('nocache', false, true));
		$controller_nocache = (!is_null($controller) && method_exists($controller, 'cache') && $controller->cache() === false);
		
		// Don't run if it's already started, if we have a POST or if the controller says not to
		if ($nocache !== false || static::$started === true || strtolower(\Input::method()) == 'post' || $controller_nocache) return false;
		
		$config = \Config::get('cmf.cache');
		if ($config['enabled'] !== true) {
			return;
		}
		
		// Check for excluded URLS
		$uri = '/'.str_replace(array('?debug', '&debug'), '', trim($_SERVER['REQUEST_URI'], '/'));
        $jq = \Input::get('_', null);
        if ($jq !== null) $uri = str_replace(array("?_=$jq", "&_=$jq"), '', $uri);
        
		$excluded_urls = $config['excluded_urls'];
		foreach ($excluded_urls as $url) {
			if (strpos($url, '*') !== false && strpos($uri.'/', str_replace('*', '', $url)) === 0) {
				return;
			}
			if ($uri == $url) return;
		}
		
		// Create the driver and try to get cached content from it
		$driver = static::driver();
		static::$started = true;
		
        // Add any extra files to check
        $files = \Arr::get($config, 'check_files', array());
        foreach ($files as $file) {
            $driver->addFile($file);
        }
        // Try and get the cached content
        $content = $driver->get($uri);
        
		// Serve the cached content if found, or continue and add the finish listener
		if (static::$active = ($content !== false)) {
			$driver->serve($content, static::$modified);
		} else {
			\Event::register('request_finished', 'CMF\\Cache::finish');
		}
	}
	
	/**
	 * Runs on 'request_finished' event
	 */
	public static function finish()
	{
		// Don't re-run if it's already finished
		if (static::$finished === true) return false;
		
		static::$finished = true;
		static::$driver->set(\Request::active()->response);
		return false;
	}
	
	/**
	 * Constructs a cache key unique to the provided URL, or for the current
	 * url if one is not provided
	 * 
	 * @param string $uri
	 * @return string
	 */
	public static function uriCacheKey($uri = null)
    {
    	if ($uri === null && static::$uriCacheKey !== null) return static::$uriCacheKey;
    	$uri = ($uri !== null) ? trim($uri, '/') : trim($_SERVER['REQUEST_URI'], '/');
    	if ($uri == '') $uri = '__home__';
        if (\CMF::$lang_enabled) $uri .= \CMF::lang();
    	
    	$session_keys = \Config::get('cmf.cache.session_index', array());
    	$session = \Session::get();
        
    	foreach ($session as $key => $value) {
    		if (in_array($key, $session_keys)) {
    			return static::$uriCacheKey = $key.'/'.$value.'/'.md5($uri);
    		}
    	}
    	
    	return static::$uriCacheKey = md5($uri);
    }
    
    /**
     * Parses and returns the names of any 'nocache' areas inside page content
     * 
     * @return array
     */
    public static function getNoCacheAreas($content)
    {
    	if (strpos($content, '<!-- nocache') === false) return array();
    	
        preg_match_all('/<!-- nocache_(.*) \'(.*)\' -->/U', $content, $hits);
        
        if(empty($hits[1]) && empty($hits[2]))
            return array();
        
    	return array_combine($hits[1], $hits[2]);
    }
    
    /**
     * Loops through the provided nocache names and injects their executed values
     * into the content
     * 
     */
    public static function addNoCacheAreas($names, $content)
    {
    	if (count($names) === 0 || !$names) return $content;
        
        static::$modified = true;
        
    	// Set up the twig environment for rendering the non-cached parts
    	\View_Twig::initLoader();
        $env = \View_Twig::parser();
        
    	$template = new \CMF\Twig\TemplateInclude($env);
    	
    	foreach ($names as $name => $include) {
    		$content = preg_replace('/<!-- nocache_'.$name.' .*<!-- endnocache_'.$name.' -->/sU', $template->renderInclude($include), $content);
    	}
        
    	return $content;
    }
    
    public static function writeCacheFile($file, $content)
    {
        
    	$dir = dirname($file);
    	if (!is_dir($dir)) {
    	    if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
    	        throw new \Exception(sprintf("Unable to create the cache directory (%s).", $dir));
    	    }
    	}
    	
    	return false !== @file_put_contents($file, $content);
    }
    
    public static function active()
    {
    	return static::$active;
    }
    
    public static function driver()
    {
    	if (isset(static::$driver)) return static::$driver;
    	$class = 'CMF\\Cache\\Driver\\'.ucfirst(\Config::get('cmf.cache.driver', 'Simple'));
    	return static::$driver = new $class();
    }
    
    public static function __callStatic($name, $args)
    {
        if (!isset(static::$driver)) return null;
        
        if (method_exists(static::$driver, $name)) {
            return call_user_func_array(array(static::$driver, $name), $args);
        }
        
        throw new \Exception("Method '$name' not found in the cache driver");
    }
	
}