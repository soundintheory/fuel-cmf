<?php

namespace Image;

use Fuel\Core\Input;

class Controller_Image extends \Controller {
    
    protected $modes = array(
        '1' => 'r',  // Normal resize
        '2' => 'cr', // Crop resize
        '3' => 'fr', // Fit resize
        '4' => 'pr',  // Pad resize
        '5' => 'cc',  // Co-ordinate Crop
    );
    
    protected $mime_types = array(
	    'js' => 'application/javascript',
	    'json' => 'application/json',
	    'css' => 'text/css',
	    'jpg' => 'image/jpeg',
	    'jpeg' => 'image/jpeg',
	    'gif' => 'image/gif',
	    'png' => 'image/png',
	);
	
	protected $path;
	protected $resized;
	protected $mime_type;
	protected $mode = null;
	protected $filename;
	
	protected function _init_image($start_segment, $append, $output_ext = null)
	{
	    // Get the path of the source file
		$ext = Input::extension();

		if (is_null($output_ext)) $output_ext = $ext;
		$this->mode = empty($this->mode) ? \Arr::get($this->modes, $this->param('mode', '1'), '') : $this->mode;

		$path_segments = array_slice(\Uri::segments(), $start_segment);
		$path_relative = implode('/', $path_segments);
		$this->path = DOCROOT.$path_relative.'.'.$ext;
		$path_cache = APPPATH.'cache/image/'.dirname($path_relative).'/';
		$this->mime_type = \Arr::get($this->mime_types, strtolower($output_ext), 'text/html');
		if (!empty($this->mode)) $append .= '_'.$this->mode;
		
		// Generate the resized filename and output one
		$this->filename = basename($path_relative).'.'.$output_ext;
		$this->resized = $path_cache.basename($path_relative).'_'.$append.'.'.$output_ext;
		
		// Vars for last modified times
		$path_last_modified = 0;
		$resized_last_modified = 0;
		
		if (!file_exists($this->path))
		{
		    $ext = $output_ext = 'jpg';
			$this->path = PKGPATH.'cmf/modules/image/assets/placeholder.jpg';
			$this->mime_type = 'image/jpeg';
			$path_cache = APPPATH.'cache/image/';
			$this->filename = 'placeholder.jpg';
    		$this->resized = $path_cache.'placeholder'.$append.'.'.$output_ext;
		}
			
		if (file_exists($this->resized))
		{
		    $path_last_modified = filemtime($this->path);
		    $resized_last_modified = filemtime($this->resized);
		    
		    if ($path_last_modified <= $resized_last_modified) {
		        return $this->serve_image($this->resized, $this->mime_type, $resized_last_modified);
	        }
		    
		}
		
		if (!is_dir($path_cache))
		{
            mkdir($path_cache, 0775, true);
        }
		
		return null;
		
	}
	
	public function action_preset($preset_name)
	{
	    $preset = \Config::get('image.presets.'.$preset_name, null);
		if (is_null($preset)) throw new \InvalidArgumentException("Could not load preset $preset, you sure it exists?");
		
		$output_ext = \Arr::get($preset, 'filetype', null);
		$bgcolor = \Arr::get($preset, 'bgcolor', '#fff');
	    
	    // Init the image info with the filename format, and output if a cache has been produced
		$output = $this->_init_image(2, md5(serialize($preset)), $output_ext);
		if (!is_null($output)) return $output;
		
		\Image::load($this->path)
		->config('bgcolor', $bgcolor)
		->preset($preset_name)
		->save($this->resized);
		
		// Serve the file
		return $this->serve_image($this->resized, $this->mime_type);
	}
	public function action_coordinate_crop(){
		//'image/:cropx/:cropy/:cropw/:croph/:w/:h/(:any)' => 'image/coordinate_crop',
		$cropx = intval($this->param('cropx'));
		$cropy = intval($this->param('cropy')); 
		$cropw = intval($this->param('cropw')); 
		$croph = intval($this->param('croph')); 
		$w = intval($this->param('w'));
		$h = intval($this->param('h'));
		$this->mode = 'cc';
		$output = $this->_init_image(7, $cropx."_".$cropy."_".$cropx."_".$cropw."_".$cropy."_".$croph."_".$w."x".$h);
		//var_dump(array_slice(\Uri::segments(), 7));exit;
		if (!is_null($output)) return $output;

		//crop($x1, $y1, $x2, $y2)
		//resize($width, $height = null, $keepar = true, $pad = false)
		/*
		$width	Required	The new width of the image.
		$height	
		null
		The new height of the image
		$keepar	
		true
		If set to true, will keep the Aspect Ratio of the image identical to the original.
		$pad	
		false
		If set to true and $keepar is true, it will pad the image with the configured bgcolor.
		 */
		//redundant
		$bgcolor = '#'.$this->param('bgcolor', 'fff');

		\Image::load($this->path)
		->config('bgcolor', $bgcolor)
  		->crop($cropx, $cropy, $cropx + $cropw, $cropy + $croph)
      ->resize($w, $h)
  		->save($this->resized);

		return $this->serve_image($this->resized, $this->mime_type);


		exit;
	}
	public function action_w_h() {
	    
		// Store our request params in more appropriate formats
		$w = intval($this->param('w'));
		$h = intval($this->param('h'));
		$bgcolor = '#'.$this->param('bgcolor', 'fff');
		
		// Init the image info with the filename format, and output if a cache has been produced
		$output = $this->_init_image(4, $w.'x'.$h);
		if (!is_null($output)) return $output;
		
		// Load the image and apply the filters
		switch ($this->mode)
		{
		    case 'cr':
		        
		        \Image::load($this->path)
		        ->config('bgcolor', $bgcolor)
        		->crop_resize($w, $h)
        		->save($this->resized);
        		
		    break;
		    case 'fr':
		    
		        $img = \Image::load($this->path);
		        $sizes = $img->sizes();
		        if ($sizes->width > $w || $sizes->height > $h) {
		            $img->config('bgcolor', $bgcolor)
            		->resize($w, $h);
		        }
		        $img->save($this->resized);
		        
		    break;
		    case 'pr':
		        
		        \Image::load($this->path)
		        ->config('bgcolor', $bgcolor)
        		->resize($w, $h, true, true)
        		->save($this->resized);
        		
		    break;
		    default:
		        
		        \Image::load($this->path)
		        ->config('bgcolor', $bgcolor)
        		->resize($w, $h, true, false)
        		->save($this->resized);
        		
		    break;
		}
		
		// Serve the file
		return $this->serve_image($this->resized, $this->mime_type);
		
	}
	
	public function serve_image($path, $mime_type, $file_last_modified = 0)
	{
	    if ($file_last_modified == 0) $file_last_modified = filemtime($path);
		$header_modified_since = strtotime(Input::server('HTTP_IF_MODIFIED_SINCE', 0));
		ob_clean();

		$status = 200;

		// Set the response headers for cache etc
		$headers = array(
			'Cache-Control' => 'public',
			'Last-Modified' => gmdate('D, d M Y H:i:s', $file_last_modified).' GMT',
			'Content-Disposition' => 'inline; filename='.$this->filename,
			'Content-Type' => $mime_type
		);
		
		$body = '';
		
		// Return 304 not modified if the file hasn't changed
		if ($header_modified_since >= $file_last_modified) {
			$status = 304;
		} else {
			// Serve up the image
			$body = file_get_contents($path);
		}
		
		//$this->response->body = file_get_contents($path);
		//return $this->response;
		$response = new \Response($body, $status, $headers);
		return $response;
	}
	
}