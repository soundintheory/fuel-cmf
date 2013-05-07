<?php

namespace CMF\Cache\Driver;

class Simple implements Driver {
	
	protected $request;
	protected $path;
	protected $content_type = 'text/html';
	
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
			'Content-Type' => $this->content_type
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
	
}