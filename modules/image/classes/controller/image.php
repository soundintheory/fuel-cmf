<?php

namespace Image;

use Fuel\Core\Input;
use League\Glide;

class Controller_Image extends \Controller {
    
	protected $server;
	protected $path;

	/**
	 * Standard resize / crop resize
	 */
	public function action_resize($mode = null, $w = null, $h = null, $path = null)
	{
		$this->outputImage($this->getImagePath(4), array(
			'w' => intval($w),
			'h' => intval($h),
			'fit' => \CMF\Image::getCropMode($mode, 'crop')
		));
	}
	
	/**
	 * Full crop with coordinates
	 */
	public function action_crop($cropx = null, $cropy = null, $cropw = null, $croph = null, $w = null, $h = null, $path = null)
	{
	    // empty crop size will default to 'crop' resize
	    if (empty(intval($cropx)) && empty(intval($cropy))) {
            $this->outputImage($this->getImagePath(7), array(
                'w' => intval($w),
                'h' => intval($h),
                'fit' => 'crop'
            ));
            return;
        }

		$this->outputImage($this->getImagePath(7), array(
			'w' => intval($w),
			'h' => intval($h),
			'crop' => implode(',', array(intval($cropw), intval($croph), intval($cropx), intval($cropy))),
			'fit' => 'crop'
		));
	}

	/**
	 * Crop to a grid position of the image (top left... etc)
	 */
	public function action_grid_crop($position = 'c', $w = null, $h = null, $path = null)
	{
		$this->outputImage($this->getImagePath(4), array(
			'w' => intval($w),
			'h' => intval($h),
			'fit' => \CMF\Image::getCropMode($position, 'crop-center')
		));
	}

	protected function outputImage($path, $params)
	{
		try {
			// Output the image using glide server
			ob_clean();
			\CMF\Image::server()->outputImage($path, $this->getImageParams($params));
		} catch (\Exception $e) {
			// Output a placeholder - exception could mean file doesn't exist, or some other environment issue
			if (!\CMF\Image::server()->getSource()->has('assets/images/placeholder.png')) {
				\CMF\Image::server()->setSource(\CMF\Storage::adapter(CMFPATH.'modules/image/public'));
			}
			\CMF\Image::server()->outputImage('assets/images/placeholder.png', array(
				'w' => intval($params['w']),
				'h' => intval($params['h']),
				'bg' => 'efefef',
				'fit' => 'fill'
			));
		}
	}

	/**
	 * Get image params for this request
	 */
	protected function getImageParams($override)
	{
		$output = array(
			'q' => \Config::get('image.quality', 80)
		);

        if ($bg = \Input::param('bg', \Input::param('00000000')))
            $output['bg'] = $bg;

		if ($format = \Input::param('fm', \Input::param('format')))
			$output['fm'] = $format;

		$output = \Arr::merge($output, $override);
		return \Arr::merge($output, \Input::get());
	}

	/**
	 * Get the current base path, stopping at the given segment number
	 */
	protected function getImagePath($startSegment)
	{
		$segments = \Uri::segments();
		$startSegment = min($startSegment, count($segments));
		return implode('/', array_slice($segments, $startSegment)).'.'.\Input::extension();
	}

	/**
	 * Increase memory and time limit for any image requests
	 */
	public function before()
	{
		try {
			set_time_limit(0);
			ini_set('memory_limit', '512M');
		} catch (\Exception $e) {}
	}
}