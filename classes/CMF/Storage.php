<?php

namespace CMF;

use \League\Flysystem;

class Storage
{
	protected static $_adapters = array();

	public static function getDocumentRoot()
	{
		return \Fuel::$is_cli ? DOCROOT.'public/' : DOCROOT;
	}

	/**
	 * Get the filesystem (flysystem) adapter to use for storage
	 */
	public static function adapter($path = null)
	{
		if (empty($path)) $path = static::getDocumentRoot();
		if (!isset(static::$_adapters[$path])) {
			static::$_adapters[$path] = new Flysystem\Filesystem(
				new Flysystem\Adapter\Local($path)
			);
		}
		return static::$_adapters[$path];
	}

	/**
	 * Get the configured flysystem adapter for the CDN
	 */
	public static function getCDNAdapter()
	{
		if (!isset(static::$_adapters['cdn']))
		{
			$cdn = null;
			if (\Config::get('cmf.cdn.enabled'))
			{
				if ($adapter = \Config::get('cmf.cdn.adapter'))
				{
					$cdn = new Flysystem\Filesystem($adapter);
				}
			}

			static::$_adapters['cdn'] = $cdn;
		}
		return static::$_adapters['cdn'];
	}

	/**
	 * Get the URL for an asset on the CDN
	 */
	public static function getCDNAssetUrl($path)
	{
		$info = parse_url($path);
		$path = ltrim(isset($info['path']) ? $info['path'] : $path, '/');
		$q = !empty($info['query']) ? '?'.$info['query'] : '';
		$base = rtrim(\Config::get('cmf.cdn.base_url', ''), '/');

		// See if we've cached a URL for this asset already
		if ($cachedUrl = \DB::select('url')->from('_files')
			->where('path', $path)
			->and_where('storage', 'cdn')
			->execute()->get('url'))
			return $base.$cachedUrl;

		// Try using the configured base URL
		if (!empty($base)) return $base.'/'.$path;

		// Manually get the URL from the CDN adapter
		if ($filesystem = static::getCDNAdapter())
		{
			switch (get_class($filesystem->getAdapter())) {
				case 'League\\Flysystem\\AwsS3v3\\AwsS3Adapter':
					$client = $filesystem->getAdapter()->getClient();
					$url = $client->getObjectUrl($filesystem->getAdapter()->getBucket(), ltrim(@$info['path'] ?: '', '/')).$q;
					return $url;
			}
		}
		return $base.'/'.$path;
	}

	public static function syncAssets()
	{
		if (!\Config::get('cmf.cdn.sync.enabled')) return false;

		$cdn = static::getCDNAdapter();
		$localAdaper = static::adapter();
		$syncPaths = \Config::get('cmf.cdn.sync.paths');
		if (!$cdn || !$syncPaths || !is_array($syncPaths)) return false;

		try {
		    set_time_limit(0);
		    ini_set('memory_limit', '512M');
		} catch (\Exception $e) {
		    // Nothing!
		}

		$staticFiles = array();
		$exclude = \Config::get('cmf.cdn.sync.exclude', array());
		if (!is_array($exclude)) $exclude = array();

		foreach ($syncPaths as $syncPath)
		{
			try {
				$syncFiles = $localAdaper->listContents($syncPath, true);
			} catch (\Exception $e) { continue; }

			foreach ($syncFiles as $syncFile)
			{
				if ($syncFile['type'] != 'file' || strpos($syncFile['basename'], '.') === 0)
					continue;

				$excluded = false;
				foreach ($exclude as $excludeTest) {
					if (!empty($excludeTest) && preg_match($excludeTest, $syncFile['path'])) {
						$excluded = true;
						break;
					}
				}
				if ($excluded) continue;
				$staticFiles[] = $syncFile['path'];
			}
		}

		// Create the map between original paths and hashed ones
		$assetsMap = array();
		foreach ($staticFiles as $staticFile)
		{
			$hashedPath = static::getHashedAssetPath($staticFile);
			if ($hashedPath) {
				$assetsMap[$staticFile] = $hashedPath;
			}
		}

		// Replace references and update hashes
		$contentOverrides = static::resolveStaticReferences($assetsMap);
		foreach ($assetsMap as $originalAsset => $remoteAsset)
		{
			static::syncSingleAsset($originalAsset, $remoteAsset, @$contentOverrides[$originalAsset] ?: null);
		}
	}

	public static function resolveStaticReferences(&$assetsMap, &$contentOverrides = array(), $checkFiles = null)
	{
		if (is_null($checkFiles)) {
			$textFileTypes = array('css', 'js');
			$checkFiles = array_filter($assetsMap, function($path) use($textFileTypes) {
				return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $textFileTypes);
			});
		}
		
		$fileNames = array();
		$renamedFiles = array();

		foreach ($assetsMap as $originalPath => $remotePath) {
			$fileNames[pathinfo($originalPath, PATHINFO_BASENAME)] = pathinfo($remotePath, PATHINFO_BASENAME);
		}

		foreach ($checkFiles as $originalPath => $remotePath)
		{
			$pathInfo = pathinfo($remotePath);
			$content = isset($contentOverrides[$originalPath]) ? $contentOverrides[$originalPath] : file_get_contents(static::getDocumentRoot().$originalPath);
			$hasMatches = false;

			// Find URL references and replace them
			foreach ($fileNames as $originalRef => $remoteRef)
			{
				$pattern = '/([\(|"|\'|\s|\/|=])('.preg_quote($originalRef).')([\)|"|\'|\?|#|\s])/';
				if (preg_match($pattern, $content, $matches))
				{
					$content = preg_replace($pattern, '${1}'.$remoteRef.'${3}', $content);
					$hasMatches = true;
				}
			}
			
			if ($hasMatches) {
				$contentOverrides[$originalPath] = $content;
				$newHashedPath = static::getHashedAssetPath($originalPath, $content);
				if ($newHashedPath != $assetsMap[$originalPath]) {
					$assetsMap[$originalPath] = $newHashedPath;
					$renamedFiles[$originalPath] = $newHashedPath;
				}
			}
		}

		if (count($renamedFiles) > 0) {
			static::resolveStaticReferences($assetsMap, $contentOverrides);
		}

		return $contentOverrides;
	}

	public static function syncSingleAsset($path, $pathToUpload = null, $content = null)
	{
		$fullPath = static::getDocumentRoot().$path;
		if (!is_file($fullPath)) return false;

		if (is_null($content))
			$content = file_get_contents($fullPath);

		if (empty($pathToUpload)) {
			$hashedFilename = md5($content).'.'.(@pathinfo($fullPath, PATHINFO_EXTENSION) ?: '');
			$pathToUpload = ltrim(@pathinfo($path, PATHINFO_DIRNAME) ?: '', '/').'/'.$hashedFilename;
		}

		$remoteUrl = '/'.$pathToUpload;
		$cachedFileInfo = \Arr::get(\DB::query("SELECT * FROM `_files` WHERE `path` = :path AND `storage` = 'cdn' ORDER BY `id` DESC LIMIT 1")
			->bind('path', $path)
			->execute()->as_array(), 0);

		// Don't need to upload if the entry already exists
		if (!empty($cachedFileInfo) && $cachedFileInfo['url'] == $remoteUrl) {
			return $pathToUpload;
		}

		// Upload if it doesn't exist
		$cdn = static::getCDNAdapter();
		if (!$cdn->has($pathToUpload)) {
			if (\Fuel::$is_cli) {
				\Cli::write("uploading '$path' to CDN");
			}
			$cdn->write($pathToUpload, $content, array( 'visibility' => 'public' ));
		}

		// Update the database entry
		if (!empty($cachedFileInfo))
		{
			\DB::update('_files')->set(array(
				'url' => $remoteUrl,
				'updated_at' => date('Y-m-d H:i:s', filemtime($fullPath))
			))->where('id', '=', intval($cachedFileInfo['id']))->execute();
		}
		else
		{
			\DB::insert('_files')->set(array(
				'url' => $remoteUrl,
				'path' => $path,
				'storage' => 'cdn',
				'created_at' => date('Y-m-d H:i:s', filectime($fullPath)),
				'updated_at' => date('Y-m-d H:i:s', filemtime($fullPath)),
			))->execute();
		}

		return $remoteUrl;
	}

	public static function getHashedAssetPath($path, &$content = null)
	{
		if (is_null($content))
		{
			$fullPath = static::getDocumentRoot().$path;
			if (!is_file($fullPath)) return false;

			$content = file_get_contents($fullPath);
		}
		$hashedFilename = md5($content).'.'.(@pathinfo($path, PATHINFO_EXTENSION) ?: '');
		return ltrim(@pathinfo($path, PATHINFO_DIRNAME) ?: '', '/').'/'.$hashedFilename;
	}

	/**
	 * Sync files to the DB table
	 */
	public static function syncFileFields()
	{
		if (!\Config::get('cmf.cdn.enabled')) return;

		try {
		    set_time_limit(0);
		    ini_set('memory_limit', '512M');
		} catch (\Exception $e) {
		    // Nothing!
		}

		$em = \D::manager();
		$driver = $em->getConfiguration()->getMetadataDriverImpl();
		$tables_fields = array();
		$sql = array();
		$localAdaper = static::adapter();
		$cdn = static::getCDNAdapter();
		$useCdn = (\Config::get('cmf.cdn.sync.enabled') && !empty($cdn));
		$staticFiles = array();

		// Loop through all the model metadata and check for image fields
		foreach ($driver->getAllClassNames() as $class) {
		    
		    $metadata = $em->getClassMetadata($class);
		    $fields = $metadata->fieldMappings;
		    $file_fields = array();
		    
		    foreach ($fields as $field_name => $field)
		    {
		        if ($field['type'] == 'image' || $field['type'] == 'file')
		        	$file_fields[$field_name] = $field['type'];
		    }
		    if (count($file_fields) === 0) continue;
		    
		    $items = $class::select('item')->getQuery()->getResult();
		    foreach ($items as $num => $item)
		    {
		    	foreach ($file_fields as $field_name => $field_type)
		    	{
		    		$field_value = $item->$field_name;

		    		if (is_array($field_value) && !empty($field_value['src'])) {

		    			$path = static::getDocumentRoot().$field_value['src'];
		    			if (file_exists($path))
		    			{
		    				$files = \DB::query("SELECT * FROM `_files` WHERE `path` = :path AND `storage` = 'local' ORDER BY `id` DESC LIMIT 1")
		    					->bind('path', $field_value['src'])
		    					->execute()->as_array();

		    				if (!count($files))
		    				{
		    					// Update file entry to the database
		    					\DB::insert('_files')->set(array(
		    					    'path' => $field_value['src'],
		    					    'url' => '/'.$field_value['src'],
		    					    'storage' => 'local',
		    					    'type' => $metadata->name,
		    					    'field' => $field_name,
		    					    'created_at' => date('Y-m-d H:i:s', filectime($path)),
		    					    'updated_at' => date('Y-m-d H:i:s', filemtime($path))
		    					))->execute();
		    				}

		    				if ($useCdn && $field_type == 'file')
		    				{
		    					$cachedFiles = \DB::query("SELECT * FROM `_files` WHERE `path` = :path AND `storage` = 'cdn' ORDER BY `id` DESC LIMIT 1")
		    						->bind('path', $field_value['src'])
		    						->execute();

		    					if (!count($cachedFiles))
		    					{
		    						// Update cached file entry to the database
		    						$cachedResult = \DB::insert('_files')->set(array(
		    						    'path' => $field_value['src'],
		    						    'url' => '/'.$field_value['src'],
		    						    'storage' => 'cdn',
		    						    'type' => $metadata->name,
		    						    'field' => $field_name,
		    						    'created_at' => date('Y-m-d H:i:s', filectime($path)),
		    						    'updated_at' => date('Y-m-d H:i:s', filemtime($path))
		    						))->execute();
		    						$cachedFileId = intval(@$cachedResult[0]);
		    					} else {
		    						$cachedFileId = intval($cachedFiles->get('id'));
		    					}

		    					if (!empty($cachedFileId))
		    					{
		    						// Just upload files straight to the CDN
		    						$staticFiles[] = $field_value['src'];
		    					}
		    				}
		    			}
		    		}
		    	}
		    }
		}

		if ($useCdn)
		{
			foreach ($staticFiles as $staticFile)
			{
				try {
					// Write the file to CDN if it doesn't exist there
					if (!$cdn->has($staticFile)) {
						if (\Fuel::$is_cli) {
							\Cli::write("uploading '$staticFile' to CDN");
						}
						$cdn->write($staticFile, $localAdaper->read($staticFile), array( 'visibility' => 'public' ));
					}
				} catch (\Exception $e) {}
			}
		}
	}

	/**
	 * Sync files for a single model
	 */
	public static function syncFileFieldsFor($model)
	{
		if (!\Config::get('cmf.cdn.enabled')) return;

		$metadata = $model->metadata();
		$fields = $metadata->fieldMappings;
		$staticFiles = array();
		$localAdaper = static::adapter();
		$cdn = static::getCDNAdapter();
		
		foreach ($fields as $field_name => $field)
		{
		    if ($field['type'] != 'image' && $field['type'] != 'file') continue;

		    $fileId = null;

		    $field_value = $model->$field_name;
		    if (is_array($field_value) && !empty($field_value['src']))
		    {
		    	$path = static::getDocumentRoot().$field_value['src'];
		    	if (file_exists($path))
		    	{
		    		$files = \DB::query("SELECT * FROM `_files` WHERE `path` = :path AND `storage` = 'local' ORDER BY `id` DESC LIMIT 1")
		    			->bind('path', $field_value['src'])
		    			->execute();

		    		if (!count($files))
		    		{
		    			// Update file entry to the database
		    			$result = \DB::insert('_files')->set(array(
		    			    'path' => $field_value['src'],
		    			    'url' => '/'.$field_value['src'],
		    			    'storage' => 'local',
		    			    'type' => $metadata->name,
		    			    'field' => $field_name,
		    			    'created_at' => date('Y-m-d H:i:s', filectime($path)),
		    			    'updated_at' => date('Y-m-d H:i:s', filemtime($path))
		    			))->execute();
		    			$fileId = intval(@$result[0]);
		    		} else {
		    			$fileId = intval($files->get('id'));
		    		}

		    		if ($field['type'] == 'image' && !empty($fileId))
		    		{
		    			$cdnFiles = \DB::query("SELECT * FROM `_files` WHERE `field` = :field AND `type` = :type AND `storage` = 'cdn' GROUP BY `params` ORDER BY `id` DESC")
		    				->bind('field', $field_name)
		    				->bind('type', $metadata->name)
		    				->execute()->as_array();

		    			foreach ($cdnFiles as $cdnFile)
		    			{
		    				if (!empty($cdnFile['params']) && ($params = @unserialize($cdnFile['params'])))
		    				{
		    					$params['path'] = $params['url'] = $field_value['src'];
		    					\CMF\Image::getUrlFromParams($params);
		    					continue;
		    				}

		    				\CMF\Image::getUrlFromParams(array(
		    					'url' => '/'.ltrim($field_value['src'], '/')
		    				));
		    			}
		    		}
		    		else if ($field['type'] == 'file' && !empty($fileId))
		    		{
		    			$cachedFiles = \DB::query("SELECT * FROM `_files` WHERE `path` = :path AND `storage` = 'cdn' ORDER BY `id` DESC LIMIT 1")
		    				->bind('path', $field_value['src'])
		    				->execute();

		    			if (!count($cachedFiles))
		    			{
		    				// Update cached file entry to the database
		    				$cachedResult = \DB::insert('_files')->set(array(
		    				    'path' => $field_value['src'],
		    				    'url' => '/'.$field_value['src'],
		    				    'storage' => 'cdn',
		    				    'type' => $metadata->name,
		    				    'field' => $field_name,
		    				    'created_at' => date('Y-m-d H:i:s', filectime($path)),
		    				    'updated_at' => date('Y-m-d H:i:s', filemtime($path))
		    				))->execute();
		    				$cachedFileId = intval(@$cachedResult[0]);
		    			} else {
		    				$cachedFileId = intval($cachedFiles->get('id'));
		    			}

		    			if (\Fuel::$is_cli) {
		    				\Cli::write($field_value['src']);
		    			}

		    			if (!empty($cachedFileId))
		    			{
		    				// Just upload files straight to the CDN
		    				$staticFiles[] = $field_value['src'];
		    			}
		    		}

		    	}
		    } else {
		    	// Field is blank. We should probably remove any remote copies of the file to save on disk space
		    }

		}

		// Upload the static files
		foreach ($staticFiles as $staticFile)
		{
			try {
				// Write the file to CDN if it doesn't exist there
				if (!$cdn->has($staticFile)) {
					if (\Fuel::$is_cli) {
						\Cli::write("uploading '$staticFile' to CDN");
					}
					$cdn->write($staticFile, $localAdaper->read($staticFile), array( 'visibility' => 'public' ));
				}
			} catch (\Exception $e) {}
		}
	}
}