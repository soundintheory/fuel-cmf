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
        '6' => 'gc',  // Grid Crop
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
		try {
            set_time_limit(0);
            ini_set('memory_limit', '512M');
        } catch (\Exception $e) {
            // Nothing!
        }
		
	    // Get the path of the source file
		$ext = Input::extension();

		if (is_null($output_ext)) $output_ext = $ext;
		if (!empty($this->mode)) $this->mode = \Arr::get($this->modes, $this->mode, $this->mode);

		$path_segments = array_slice(\Uri::segments(), $start_segment);
		$path_relative = implode('/', $path_segments);

		$this->path = DOCROOT.$path_relative.'.'.$ext;
		if (!file_exists($this->path)) {
			foreach ($path_segments as $key => $value) {
				$path_segments[$key] = urldecode($value);
			}
			$path_relative = implode('/', $path_segments);
		}
		
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
	
	public function action_coordinate_crop($cropx = null, $cropy = null, $cropw = null, $croph = null, $w = null, $h = null, $path = null)
	{
		$cropx = intval($cropx);
		$cropy = intval($cropy); 
		$cropw = intval($cropw); 
		$croph = intval($croph); 
		$w = intval($w);
		$h = intval($h);
		$this->mode = 'cc';
		
		$output = $this->_init_image(7, $cropx."_".$cropy."_".$cropx."_".$cropw."_".$cropy."_".$croph."_".$w."x".$h, \Input::param('format'));
		
		if (!is_null($output)) return $output;
		
		$bgcolor = '#'.\Input::param('bgcolor', 'fff');
		
		\Image::load($this->path)
		->config('bgcolor', $bgcolor)
  		->crop($cropx, $cropy, $cropx + $cropw, $cropy + $croph)
  		->resize($w, $h, false)
  		->save($this->resized);
  		
		return $this->serve_image($this->resized, $this->mime_type);
	}
	public function action_grid_crop($grid = 'c', $w = null, $h = null, $path = null)
	{
		$w = intval($w);
		$h = intval($h);
		$this->mode = 'gc';
		
		$output = $this->_init_image(4, $grid."_".$w."x".$h, \Input::param('format'));
		if (!is_null($output)) return $output;
		
		$bgcolor = '#'.\Input::param('bgcolor', 'fff');
		//var_dump($this->path);
		//exit;
		$image_size = getimagesize($this->path);
		$image = \Image::load($this->path);

		$org_width = $image_size[0];
		$org_height = $image_size[1];
		//get natural width and height
		//get desired final ratio (h/w)
		//get original ratio
		//if they are the same we can just resize.
		//if they are not the same we have to crop to achieve the desired ratio. crop at max width and height
		//if new ratio is bigger, than the height is the biggest. if its smaller then the width is the biggest.
		//if the height is the biggest, (old height divided by new height) 
		//if width is the biggest, (old width divided by new width)
		//times the modifier by new width and new height separately
		//we now have the maximum crop.
		//figure out the starting position based on TL,L etc.
		//crop it 
		//resize it up.
		$org_ratio = $org_height / $org_width;
		$new_ratio = $h / $w;
		if($org_ratio != $new_ratio){
			//different ratio, work out maximum crop before resizing.
			if($new_ratio > $org_ratio){
				//height should be biggest in the new ratio
				$modifier = $org_height / $h;
				
			}
			else{
				//width is biggest in new ratio
				$modifier = $org_width / $w;
			}
			$final_w = round($w * $modifier);
			$final_h = round($h * $modifier);
			$src_x = round((($org_width - $final_w) / 2));
			$src_y = round((($org_height - $final_h) / 2));
			
			$dst_x = $src_x + $final_w;
			$dst_y = $src_y + $final_h;
			// positional cropping!
			if (strpos ($grid, 't') !== false) {
				$src_y = 0;
				$dst_y = $final_h;
			}
			if (strpos ($grid, 'b') !== false) {
				$dst_y = $org_height;
				$src_y = round($org_height - $final_h);
			}
			if (strpos ($grid, 'l') !== false) {
				$src_x = 0;
				$dst_x = $final_w;
			}
			if (strpos ($grid, 'r') !== false) {
				$dst_x = $org_width;
				$src_x = round($org_width - $final_w);
			}
			$image->crop($src_x, $src_y, $dst_x, $dst_y);
		}
		$image->resize($w, $h, false, true)
		->save($this->resized);
		return $this->serve_image($this->resized, $this->mime_type);
	}
	public function action_w_h($mode = null, $w = null, $h = null, $path = null) {
	    
		// Store our request params in more appropriate formats
		$this->mode = $mode;
		$w = intval($w);
		$h = intval($h);
		//this isnt yet set to be passed in
		$bgcolor = '#'.\Input::param('bgcolor', 'fff');

		if ($w === 0 || $h === 0) {
			$this->mode = '1';
		}
		
		// Init the image info with the filename format, and output if a cache has been produced
		$output = $this->_init_image(4, $w.'x'.$h, \Input::param('format'));
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