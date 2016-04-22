<?php

namespace CMF;

use \League\Flysystem;

class Storage
{
	protected static $_adapters = array();

	/**
	 * Get the filesystem (flysystem) adapter to use for storage
	 */
	public static function adapter($path = null)
	{
		if (empty($path)) $path = DOCROOT;
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
	 * Get the base URL for the CDN
	 */
	public static function getCDNAssetUrl($path)
	{
		$info = parse_url($path);
		$q = !empty($info['query']) ? '?'.$info['query'] : '';

		if ($filesystem = static::getCDNAdapter())
		{
			switch (get_class($filesystem->getAdapter())) {
				case 'League\\Flysystem\\AwsS3v3\\AwsS3Adapter':
					$client = $filesystem->getAdapter()->getClient();
					return $client->getObjectUrl($filesystem->getAdapter()->getBucket(), ltrim(@$info['path'] ?: '', '/')).$q;
				break;
			}

		}
		return $path;
	}

	/**
	 * Sync files to the DB table
	 */
	public static function syncFiles()
	{
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

		    			$path = DOCROOT.$field_value['src'];
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

		    				if ($cdn && $field_type == 'file')
		    				{
		    					// Sync straight-up files directly to the CDN
		    					//$staticFiles[] = $field_value['src'];
		    				}
		    			}
		    		}
		    	}
		    }
		}

		if ($cdn && \Config::get('cmf.cdn.sync.enabled'))
		{
			if ($syncPaths = \Config::get('cmf.cdn.sync.paths'))
			{
				if (!is_array($syncPaths)) $syncPaths = array();
				$exclude = \Config::get('cmf.cdn.sync.exclude', array());
				if (!is_array($exclude)) $exclude = array();

				foreach ($syncPaths as $syncPath) {
					try {

						$syncFiles = $localAdaper->listContents($syncPath, true);
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

					} catch (\Exception $e) {}
				}
			}
		}

		foreach ($staticFiles as $staticFile)
		{
			// Write the file to CDN if it doesn't exist there
			if (!$cdn->has($staticFile)) {
				$cdn->write($staticFile, $localAdaper->read($staticFile), array( 'visibility' => 'public' ));
			} else if ($cdn->getTimestamp($staticFile) < $localAdaper->getTimestamp($staticFile)) {
				$cdn->delete($staticFile);
				$cdn->write($staticFile, $localAdaper->read($staticFile), array( 'visibility' => 'public' ));
			}
		}
	}

	/**
	 * Sync files for a single model
	 */
	public static function syncFilesFor($model)
	{
		$metadata = $model->metadata();
		$fields = $metadata->fieldMappings;
		
		foreach ($fields as $field_name => $field)
		{
		    if ($field['type'] != 'image' && $field['type'] != 'file') continue;

		    $fileId = null;

		    $field_value = $model->$field_name;
		    if (is_array($field_value) && !empty($field_value['src']))
		    {
		    	$path = DOCROOT.$field_value['src'];
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
		    			// Just upload files straight to the CDN

		    		}

		    	}
		    } else {
		    	// Field is blank. We should probably remove any remote copies of the file to save on disk space
		    }

		}
	}
}