<?php

namespace CMF;

use Fuel\Core\Input;

class ImageObject
{
	protected $modes = array(
        '1' => 'r',  // Normal resize
        '2' => 'cr', // Crop resize
        '3' => 'fr', // Fit resize
        '4' => 'pr'  // Pad resize
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
	
	public $pathinfo;
	public $resized;
	public $mime_type;
	public $mode;
	public $last_mod = 0;
	
	public function __construct($path, $relative=true)
	{
		if (is_array($path)) $path = \Arr::get($path, 'src', 'placholder.png');
		
		// The details of the source file
		$this->pathinfo = pathinfo($path);
		
		if ($relative) {
			$this->pathinfo['path'] = DOCROOT.$path;
			$this->pathinfo['path_relative'] = $path;
		} else {
			$this->pathinfo['path'] = $path;
			$this->pathinfo['path_relative'] = str_replace(DOCROOT, '', $path);
		}
	}
	
	protected function _init_image($append, $output_ext = null)
	{
		if (!is_null($output_ext)) $this->pathinfo['extension'] = $output_ext;
		$path_cache = APPPATH.'cache/image/'.dirname($this->pathinfo['path_relative']).'/';
		$this->mime_type = \Arr::get($this->mime_types, strtolower($this->pathinfo['extension']), 'text/html');
		
		// Generate the resized filename
		$this->resized = $path_cache.$this->pathinfo['filename'].'_'.$append.'.'.$this->pathinfo['extension'];
		
		// Vars for last modified times
		$path_last_modified = 0;
		$resized_last_modified = 0;
		
		// Serve the placeholder if it doesn't exist
		if (!file_exists($this->pathinfo['path'])) {
		    $this->pathinfo['extension'] = 'jpg';
			$this->pathinfo['path'] = PKGPATH.'cmf/modules/image/assets/placeholder.jpg';
			$this->pathinfo['filename'] = 'placeholder.jpg';
			$this->mime_type = 'image/jpeg';
			$path_cache = APPPATH.'cache/image/';
    		$this->resized = $path_cache.'placeholder_'.$append.'.jpg';
		}
		
		// The resized file exists. Check the date against the source and return the resized path if it can be cached
		if (file_exists($this->resized)) {
		    $path_last_modified = filemtime($this->pathinfo['path']);
		    $resized_last_modified = $this->last_mod = filemtime($this->resized);
		    
		    if ($path_last_modified <= $resized_last_modified) {
		        return $this->resized;
	        }
		    
		}
		
		// Create the cache directory if it doesn't exist
		if (!is_dir($path_cache)) {
            mkdir($path_cache, 0775, true);
        }
		
		return null;
		
	}
	
	public function preset($preset_name)
	{
	    $preset = \Config::get('image.presets.'.$preset_name, null);
		if (is_null($preset)) throw new \InvalidArgumentException("Could not load preset $preset, you sure it exists?");
		
		// Get the extension if one has been specified
		$output_ext = \Arr::get($preset, 'filetype', null);
	    
	    // Init the image info with the filename format, and don't continue if a cache has been produced
		$output = $this->_init_image(md5(serialize($preset)), $output_ext);
		if (!is_null($output)) return $output;
		
		// Run the preset
		$bgcolor = \Arr::get($preset, 'bgcolor', '#fff');
		\Image::load($this->pathinfo['path'])
		->config('bgcolor', $bgcolor)
		->preset($preset_name)
		->save($this->resized);
		
		// Return the newly created filename
		return $this->resized;
	}

	public function coordinate_crop($cropx = null, $cropy = null, $cropw = null, $croph = null, $w = null, $h = null)
	{
		if (is_array($cropx)) {
			$w = $cropy;
			$h = $cropw;
			$cropy = $cropx['y'];
			$cropw = $cropx['width'];
			$croph = $cropx['height'];
			$cropx = $cropx['x'];
		} else {
			$cropx = intval($cropx);
			$cropy = intval($cropy); 
			$cropw = intval($cropw); 
			$croph = intval($croph); 
			$w = intval($w);
			$h = intval($h);
		}
		
		$this->mode = 'cc';
		$output = $this->_init_image($cropx."_".$cropy."_".$cropx."_".$cropw."_".$cropy."_".$croph."_".$w."x".$h);
		if (!is_null($output)) return $output;
		
		$bgcolor = '#'.\Input::param('bgcolor', 'fff');
		
		\Image::load($this->pathinfo['path'])
		->config('bgcolor', $bgcolor)
  		->crop($cropx, $cropy, $cropx + $cropw, $cropy + $croph)
  		->resize($w, $h, false)
  		->save($this->resized);
  		
		// Return the newly created filename
		return $this->resized;
	}
	
	public function grid_crop($grid = 'c', $w = null, $h = null)
	{
		$w = intval($w);
		$h = intval($h);
		$this->mode = 'gc';
		
		$output = $this->_init_image($grid."_".$w."x".$h);
		if (!is_null($output)) return $output;
		
		$bgcolor = '#'.$this->param('bgcolor', 'fff');
		$image_size = getimagesize($this->path);
		$image = \Image::load($this->pathinfo['path']);

		$org_width = $image_size[0];
		$org_height = $image_size[1];
		
		$org_ratio = $org_height / $org_width;
		$new_ratio = $h / $w;
		
		if ($org_ratio != $new_ratio) {
			
			//different ratio, work out maximum crop before resizing.
			if($new_ratio > $org_ratio) {
				//height should be biggest in the new ratio
				$modifier = $org_height / $h;
				
			} else {
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
		
		// Return the newly created filename
		return $this->resized;
	}
	
	public function w_h($mode, $w, $h, $bgcolor = '#FFF', $bw = false)
	{
		// Translate the mode to something more readable
		$mode = $this->mode = isset($this->modes[strval($mode)]) ? $this->modes[strval($mode)] : strval($mode);
		
		// Init the image info with the filename format, and output if a cache has been produced
		$output = $this->_init_image($w.'x'.$h.'_'.$mode.($bw ? '_bw' : ''));
		if (!is_null($output)) return $output;
		
		// Load the image and apply the filters
		switch ($this->mode)
		{
		    case 'cr':
		        
		        $img = \Image::load($this->pathinfo['path'])
		        ->config('bgcolor', $bgcolor)
        		->crop_resize($w, $h);
        		
		    break;
		    case 'fr':
		    
		        $img = \Image::load($this->pathinfo['path']);
		        $sizes = $img->sizes();
		        if ($sizes->width > $w || $sizes->height > $h) {
		            $img->config('bgcolor', $bgcolor)
            		->resize($w, $h);
		        }
		        
		    break;
		    case 'pr':
		        
		        $img = \Image::load($this->pathinfo['path'])
		        ->config('bgcolor', $bgcolor)
        		->resize($w, $h, true, true);
        		
		    break;
		    default:
		        
		        $img = \Image::load($this->pathinfo['path'])
		        ->config('bgcolor', $bgcolor)
        		->resize($w, $h, true, false);
        		
		    break;
		}
		
		if ($bw === true) $img->grayscale();
		
		// Save it
		$img->save($this->resized);
		
		// Return the newly created filename
		return $this->resized;
	}
	
	public function serve()
	{
	    if ($this->last_mod === 0) $this->last_mod = filemtime($this->resized);
		$header_modified_since = strtotime(Input::server('HTTP_IF_MODIFIED_SINCE', 0));
		ob_clean();

		$status = 200;

		// Set the response headers for cache etc
		$headers = array('Cache-Control' => 'public',
                      'Last-Modified' => gmdate('D, d M Y H:i:s', $this->last_mod).' GMT',
                      'Content-Disposition' => 'inline; filename='.$this->pathinfo['filename'].'.'.$this->pathinfo['extension'],
                      'Content-Type' => $this->mime_type);
		
		// Return 304 not modified if the file hasn't changed
		if ($header_modified_since >= $this->last_mod) {
			$status = 304;
		} else {
			// Serve up the image
			$body = file_get_contents($this->resized);
		}
		
		return \Response::forge($body, $status, $headers);
	}
	
}