<?php

namespace CMF\Cache\Driver;

class Simple implements Driver {
	
	protected $request;
	protected $path;
	protected $content_type = 'text/html';
	protected $files = array();
	
	public function get($url)
	{
		$this->request = \Request::active();
		$this->path = APPPATH.'cache/pages/'.\CMF\Cache::uriCacheKey($url).'.cache';
		
		if (file_exists($this->path)) {
			return file_get_contents($this->path);
		}
		
		return false;
	}
	
	public function set($response)
	{
		if (!is_dir(APPPATH.'cache/pages')) mkdir(APPPATH.'cache/pages', 0775, true);
		file_put_contents($this->path, strval($response));
	}
	
	public function serve($content)
	{
		$cache_last_modified = filemtime($this->path);
		$header_modified_since = strtotime(\Input::server('HTTP_IF_MODIFIED_SINCE', 0));
		$status = 200;
		
		// Set the response headers for cache etc
		$headers = array(
			'Cache-Control' => 'public',
			'Last-Modified' => gmdate('D, d M Y H:i:s', $cache_last_modified).' GMT',
			'Content-Type' => $this->content_type,
			'X-UA-Compatible' => 'IE=edge'
		);
		
		// Still call the before method on the controller... is this a good idea? Perhaps not.
		/* if (isset($this->request) && $controller = $this->request->controller_instance) {
			if (method_exists($controller, 'before')) $controller->before($content);
		} */
		
		// Return 304 not modified if the content hasn't changed, but only if the profiler isn't enabled.
		if (!\Fuel::$profiling) {
			$headers['Content-Length'] = strlen($content);
			
			if ($header_modified_since >= $cache_last_modified) {
				header('HTTP/1.1 304 Not Modified');
    			exit();
			}
			
		}
		
		// Send the response
		\Response::forge($content, $status, $headers)->send(true);
		
		if (\Fuel::$profiling) {
			\Profiler::mark('CMF Cache Served');
		}
		
		exit();
		
	}
	
	/**
	 * Adds a file into the list to check for last modified date
	 * 
	 * @param string $path
	 */
	public function addFile($path)
	{
		if (is_dir($path)) {
			$contents = \File::read_dir($path, 0, array(
			    '!^\.', // no hidden files/dirs
			    '!^private' => 'dir', // no private dirs
			    '!^compiled' => 'dir', // no private dirs
			    '\.css$' => 'file', // or css files
			    '\.js$' => 'file', // or css files
			    '\.scss$' => 'file', // or css files
			    '!^_', // exclude everything that starts with an underscore.
			));
			$this->addFiles($contents, rtrim($path).'/');
		} else if (file_exists($path)) {
			$this->files[] = str_replace(PROJECTROOT, '', $path);
		}
	}
	
	/**
	 * Loops through an array of files and adds them, recursively if there are any arrays
	 */
	public function addFiles($files, $prefix = '')
	{
		foreach ($files as $key => $path) {
			if (is_array($path)) {
				$this->addFiles($path, $prefix.$key);
			} else {
				$this->addFile($prefix.$path);
			}
		}
	}
	
}