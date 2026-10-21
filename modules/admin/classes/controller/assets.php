<?php

namespace Admin;

/**
 * A quick and dirty way of serving admin's assets without having anything in the public folder
 * TODO: Minify and combine the JS
 * 
 * @package Admin
 */
class Controller_Assets extends \Controller {
	
	protected $mime_types = array(
	    'js' => 'application/javascript',
	    'json' => 'application/json',
	    'css' => 'text/css',
	    'jpg' => 'image/jpeg',
	    'jpeg' => 'image/jpeg',
	    'gif' => 'image/gif',
	    'png' => 'image/png',
	    'svg' => 'image/svg+xml',
	    'wav' => 'audio/wav',
	);
	
	public function action_index()
	{
	    $path = implode('/', array_slice(\Uri::segments(), 2));
	    $ext = \Input::extension();
	    $mime_type = \Arr::get($this->mime_types, $ext, 'text/html');
	    
	    if ($ext == 'less') return $this->serveLess(CMFPATH.'modules/admin/public/assets/'.$path.'.'.$ext);
		return $this->serveFile(CMFPATH.'modules/admin/public/assets/'.$path.'.'.$ext, $mime_type);
	}
	
	public function serveLess($path)
	{
		if (!file_exists($path)) {
			echo 'File not found: ' . $path;
			exit();
		}
		
		$header_modified_since = strtotime(\Input::server('HTTP_IF_MODIFIED_SINCE', 0));
		ob_clean();
		
		// Compile with less.php, which caches the output (and tracks imports) in its cache dir
		$cache_dir = APPPATH.'cache/less/';
		if (!is_dir($cache_dir)) mkdir($cache_dir, 0777, true);
		$compiled = $cache_dir.\Less_Cache::Get(array( $path => '' ), array( 'cache_dir' => $cache_dir ));
		$updated = filemtime($compiled);
		
		// Set the response headers for cache etc
		$headers = array(
			'Cache-Control' => 'public',
			'Last-Modified' => gmdate('D, d M Y H:i:s', $updated).' GMT',
			'Content-Type' => 'text/css'
		);
		
		// Return 304 not modified if the file hasn't changed
		if ($header_modified_since >= $updated) {
			return \Response::forge(null, 304, $headers);
		}
		
		// Serve up the file
		return \Response::forge(file_get_contents($compiled), 200, $headers);
	}
	
	/**
	 * Takes a path to a file and serves it up
	 * @param  string $path The full path to the file
	 * @param  string $mime The mime type of the file
	 * @return \Fuel\Core\Response
	 */
	public function serveFile($path, $mime = null)
	{
	    $file_last_modified = filemtime($path);
		$header_modified_since = strtotime(\Input::server('HTTP_IF_MODIFIED_SINCE', 0));
		ob_clean();
		
		// Set the response headers for cache etc
		$headers = array(
			'Cache-Control' => 'public',
			'Last-Modified' => gmdate('D, d M Y H:i:s', $file_last_modified).' GMT',
			'Content-Type' => $mime
		);
		
		// Return 304 not modified if the file hasn't changed
		if ($header_modified_since >= $file_last_modified) {
			return \Response::forge(null, 304, $headers);
		}
		
		// Serve up the file
		return \Response::forge(file_get_contents($path), 200, $headers);
	}
	
}