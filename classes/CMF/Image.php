<?php

namespace CMF;

use Fuel\Core\Input;
use League\Flysystem\Exception;
use League\Glide;

class Image
{
	protected static $modes = array(
	    '1' => 'contain',
	    '2' => 'crop',
	    '3' => 'max',
	    '4' => 'fill',
	    'tl' => 'crop-top-left',
	    't' => 'crop-top',
	    'tr' => 'crop-top-right',
	    'l' => 'crop-left',
	    'c' => 'crop-center',
	    'r' => 'crop-right',
	    'bl' => 'crop-bottom-left',
	    'b' => 'crop-bottom',
	    'br' => 'crop-bottom-right'
	);

	protected static $_manager;
	protected static $_server;

	/**
	 * Get the filesystem (flysystem) adapter to use for storage
	 */
	public static function manager()
	{
		if (!isset(static::$_manager)) {
			static::$_manager = new \Intervention\Image\ImageManager([
				'driver' => \Config::get('image.driver', 'gd')
			]);
		}
		return static::$_manager;
	}

	/**
	 * Get the Glide server instance that powers the image API
	 */
	public static function server()
	{
		if (!isset(static::$_server))
		{
			$api = new Glide\Api\Api(\CMF\Image::manager(), array(
			    new Glide\Manipulators\Orientation(),
			    new \CMF\Glide\Manipulators\Crop(),
			    new Glide\Manipulators\Size(2000*2000),
			    new Glide\Manipulators\Brightness(),
			    new Glide\Manipulators\Contrast(),
			    new Glide\Manipulators\Gamma(),
			    new Glide\Manipulators\Sharpen(),
			    new Glide\Manipulators\Filter(),
			    new Glide\Manipulators\Blur(),
			    new Glide\Manipulators\Pixelate(),
			    new Glide\Manipulators\Watermark(\CMF\Storage::adapter()),
			    new Glide\Manipulators\Background(),
			    new Glide\Manipulators\Border(),
			    new Glide\Manipulators\Encode(),
			));

			static::$_server = new Glide\Server(
			    \CMF\Storage::adapter(),
			    \CMF\Storage::adapter(APPPATH.'cache/image'),
			    $api
			);
		}
		return static::$_server;
	}

	/**
	 * Get a url for an image
	 */
	public static function getUrl($image, $width = null, $height = null, $crop_id = 'main', $mode = 2, $params = array())
	{
		$params = static::getParams($image, $width, $height, $crop_id, $mode, $params);
		return static::getUrlFromParams($params);
	}

	/**
	 * Gets an image URL (cdn if needed) from a bunch of params
	 */
	public static function getUrlFromParams($params)
	{
		if (!is_array($params)) return null;

		$url = $params['url'];
		unset($params['url']);

		// Do some CDN magic if needed
		if (\Config::get('cmf.cdn.enabled') && $cdn = \CMF\Storage::getCDNAdapter())
		{
			$original = isset($params['path']) ? $params['path'] : ltrim($url, '/');
			$ext = '';

			// Generate a resized copy of the image locally
			if (isset($params['path'])) {
				unset($params['path']);
				try {
					$resized = $remote = static::server()->makeImage($original, $params);
					$sourceAdapter = static::server()->getCache();
					$ext = @pathinfo(DOCROOT . $original, PATHINFO_EXTENSION) ?: '';
					if (!empty($ext)) $remote .= '.' . $ext;
				} catch (\Exception $e) {
					return $url;
				}
			} else {
				$resized = $original;
				$remote = $original.'/'.pathinfo(DOCROOT.$original, PATHINFO_BASENAME);
				$sourceAdapter = static::server()->getSource();
			}

			// Try and find a cached version of this file
			$paths = array($original, $resized);
			$files = \DB::query("SELECT * FROM `_files` AS f WHERE f.path IN:paths ORDER BY f.id DESC")->bind('paths', $paths)->execute()->as_array();
			$originalInfo = null;
			$resizedInfo = null;
			foreach ($files as $file)
			{
				if (is_null($originalInfo) && @$file['storage'] == 'local') {
					$originalInfo = $file;
					if (!is_null($resizedInfo)) break;
				}
				if (is_null($resizedInfo) && @$file['storage'] == 'cdn' && $file['path'] == $resized) {
					$resizedInfo = $file;
					if (!is_null($originalInfo)) break;
				}
			}

			// If there's no file in the DB, we might need to push to the CDN
			if (is_null($resizedInfo))
			{
				// Write the file to CDN if it doesn't exist there
				if (!$cdn->has($remote))
					$cdn->write($remote, $sourceAdapter->read($resized), array( 'visibility' => 'public' ));
				$url = '/'.$remote;

				// Write a file entry to the database
				\DB::insert('_files')->set(array(
				    'path' => $resized,
				    'url' => $url,
				    'type' => !empty($originalInfo) ? $originalInfo['type'] : null,
				    'field' => !empty($originalInfo) ? $originalInfo['field'] : null,
				    'storage' => 'cdn',
				    'params' => !empty($params) ? serialize($params) : null,
				    'parent' => !empty($originalInfo) ? intval($originalInfo['id']) : null,
				    'created_at' => date('Y-m-d H:i:s'),
				    'updated_at' => date('Y-m-d H:i:s')
				))->execute();

			} else {

				// Fill in missing information to the file record if we have it now
				if (!is_null($originalInfo) && empty($resizedInfo['parent']))
				{
					\DB::update('_files')
						->set(array(
							'parent'  => intval(@$originalInfo['id']),
							'type' => @$originalInfo['type'],
							'field' => @$originalInfo['field']
						))
						->where('id', '=', $resizedInfo['id'])
						->execute();
				}

				$url = $resizedInfo['url'];
			}
			return rtrim(\Config::get('cmf.cdn.base_url', ''), '/').$url;
		}
		return $url;
	}

	/**
	 * Get an array of params that can be passed directly to an intervention image object
	 */
	public static function getParams($image, $width = null, $height = null, $crop_id = 'main', $mode = 2, $params = array())
	{
		// Normalise width and height
		if (empty($width)) $width = 0;
		if (empty($height)) $height = 0;

		// Build query string params
		if (!is_array($params)) $params = array();
		$qs = !empty($params) ? '?'.http_build_query($params) : '';

		// Proper array has been passed
		if (is_array($image))
		{
			$src = \Arr::get($image, 'src');
			$crop = \Arr::get($image, "crop.$crop_id");

			if (!empty($crop) && !empty($src))
			{
				// Has an image source and crop info - happy days!
				$crop = array(
					isset($crop['width']) ? $crop['width'] : 0,
					isset($crop['height']) ? $crop['height'] : 0,
					isset($crop['x']) ? $crop['x'] : 0,
					isset($crop['y']) ? $crop['y'] : 0
				);

				$output = \Arr::merge(array(
					'w' => $width,
					'h' => $height,
					'crop' => implode(',', $crop),
					'fit' => 'crop',
					'q' => \Config::get('image.quality', 80),
					'bg' => 'fff',
					'path' => $src
				), $params);

				$output['path'] = $src;
				$output['url'] = '/image/'.implode('/', array(
					$crop[2],
					$crop[3],
					$crop[0],
					$crop[1],
					$output['w'],
					$output['h'],
					$output['path']
				)).$qs;

				return $output;
			}
			else if (!empty($src))
			{
				// We only have the source - oh well
				$image = $src;
			}
			else
			{
				// We have nothing
				$image = null;
			}
		}

		// Simple string image path (no crop data)
		if (is_string($image))
		{
			$mode = is_int($crop_id) ? $crop_id : $mode;
			if (!is_int($mode)) $mode = 2;

			// If there's nothing special, just return the source image
			if (empty($width) && empty($height) && empty($params)) {
				return array(
					'url' => '/'.ltrim($image, '/')
				);
			}

			return \Arr::merge(array(
				'w' => $width,
				'h' => $height,
				'fit' => static::getCropMode($mode, 'crop'),
				'q' => \Config::get('image.quality', 80),
				'bg' => 'fff',
				'path' => ltrim($image, '/'),
				'url' => "/image/$mode/$width/$height/".ltrim($image, '/').$qs
			), $params);
		}

		// Nothing has been able to return an image - just return a placeholder
		return array(
			'w' => $width,
			'h' => $height,
			'bg' => 'efefef',
			'fit' => 'fill',
			'path' => 'assets/images/placeholder.png',
			'url' => "/image/4/$width/$height/assets/images/placeholder.png"
		);
	}

	/**
	 * Alias for getUrl, for backwards compatibility
	 */
	public static function getCropUrl($image, $width, $height, $crop_id = 'main', $crop_mode = 2, $params = array())
	{
		return static::getUrl($image, $width, $height, $crop_id, $crop_mode, $params);
	}

	/**
	 * Convert a CMF integer crop mode to an intervention crop mode
	 */
	public static function getCropMode($mode, $default = 'crop')
	{
		return isset(static::$modes[strval($mode)]) ? static::$modes[strval($mode)] : $default;
	}
}