<?php

namespace CMF\Cache\Driver;

class Auto extends Simple {
	
	protected $path;
	protected $logger;
	
	public function get($url)
	{
		if (\Fuel::$profiling) {
			\Profiler::mark('CMF Cache Start (auto)');
		}
		
		$this->request = \Request::active();
		$this->path = APPPATH.'cache/pages/'.\CMF\Cache::uriCacheKey($url).'.cache';
		
		if (file_exists($this->path)) {
			$contents = unserialize(file_get_contents($this->path));
			
			// Check the files first
			$cache_modified = filemtime($this->path);
			foreach ($contents['files'] as $file) {
				$file = PROJECTROOT.$file;
				if (!file_exists($file) || filemtime($file) > $cache_modified) {
					$this->startListeners();
					return false;
				}
			}
			
			// Now check the last modified / record counts from the DB
			if (!empty($contents['sql'])) {
				
				$result = \DB::query($contents['sql'])->execute()->as_array();
				$result = $result[0];
				
				if ($result['count'] !== $contents['count'] || strtotime($result['updated_at']) > $contents['updated_at']) {
					$this->startListeners();
					return false;
				}
			}
			
			// See if the cache defines a content type
			if (isset($contents['content-type'])) $this->content_type = $contents['content-type'];
			
			// We are home and dry - the cache is completely valid.
			// Replicate any logs that were made in the original request
			\CMF\Log::addMultiple($contents['logs_made']);
			
			// Ok, now we can serve the cache, finally!!
			// We process the cached content to find and replace any areas that shouldn't be cached
			return \CMF\Cache::addNoCacheAreas($contents['nocache'], $contents['content']);
			
		}
		
		// If we've arrived here, we need to start listening for queries and assets
		$this->startListeners();
		return false;
		
	}
	
	protected function startListeners()
	{
		$connection = \D::manager()->getConnection()->getConfiguration();
		$this->logger = new \CMF\Doctrine\QueryLogger($connection->getSQLLogger());
		$connection->setSQLLogger($this->logger);
	}
	
	public function set($response)
	{
		if ($response->status !== 200) return;
		
		$this->request = \Request::active();
		$view = $this->request->response->body;
		$driver = $this;
		
		\Event::register('shutdown', function() use($driver) {
			$driver->shutdown();
		});
	}
	
	public function shutdown()
	{
		$queries = $this->logger->queries;
		$controller = $this->request->controller_instance;
		$tables = array();
		$subqueries = array();
		$sql = '';
		
		// Add the template files to be checked
		$template_loader = \View_Twig::loader();
		if (!is_null($template_loader)) {
			$templates = $template_loader->getFiles();
			$this->files = array_unique(array_merge($this->files, $templates));
		}
		
		// Add all loaded files within the app root, excluding cache and model proxies
		$this->files = array_merge($this->files, array_filter(get_included_files(), function($path) {
			return strpos($path, APPPATH) === 0 && strpos($path, APPPATH.'cache') !== 0 && strpos($path, APPPATH.'classes/proxy') !== 0;
		}));
		
		$model_classes = array_filter(get_declared_classes(), function($class) {
			return strpos($class, 'Model_') === 0;
		});
		
		// Construct an allowed list of tables for the cache queries
		$em = \D::manager();
		foreach ($model_classes as $model) {
			$meta = $em->getClassMetadata($model);
			if ($meta->isMappedSuperclass || !isset($meta->columnNames['updated_at']) || $meta->rootEntityName != $meta->name) continue;
			if (!in_array($meta->table['name'], $tables)) {
				$tables[] = $meta->table['name'];
			}
		}
		
		// Remove the app path from each file path, making them relative
		$this->files = array_map(function($path) {
			return str_replace(PROJECTROOT, '', $path);
		}, $this->files);
		
		// Construct ourselves a number of sub queries to check all the relevant records in the database
		$num = 0;
		foreach ($queries as $query) {
			
			$parser = new \PHPSQL\Parser();
			$parsed = $parser->parse($query, true);
			if (!isset($parsed['FROM']) || count($parsed['FROM']) === 0) {
				continue;
			}
			
			$aliases = array();
			foreach ($parsed['FROM'] as $part) {
				if ($part['expr_type'] == 'table' && in_array($part['table'], $tables)) {
					$aliases[] = isset($part['alias']['name']) ? $part['alias']['name'] : $part['table'];
				}
			}
			
			$from_pos = $parsed['FROM'][0]['position'];
			if (isset($parsed['ORDER']) && count($parsed['ORDER']) > 0) {
				$append = ' FROM '.substr($query, $from_pos, $parsed['ORDER'][0]['position'] - $from_pos - 10);
			} else {
				$append = ' FROM '.substr($query, $from_pos);
			}
			
			if (count($aliases) > 1) {
				$append = ', (COUNT('.implode('.id)+COUNT(',$aliases).'.id)) as count'.$append;
			} else if (count($aliases) === 1) {
				$append = ', COUNT('.$aliases[0].'.id) as count'.$append;
			} else {
				continue;
			}
			
			$subqueries[] = 'q'.$num;
			$sql .= ($num > 0 ? ',' : '').' (SELECT '.(count($aliases) > 1 ? 'GREATEST(' : '').'IFNULL(MAX('.implode('.updated_at),0), IFNULL(MAX(', $aliases).'.updated_at),0)'.(count($aliases) > 1 ? ')' : '').' AS updated_at'.$append.') q'.$num;
			$num++;
			
		}
		
		//print('queries: '.$num);
		//return;
		
		if (!empty($sql)) {
			// Complete the mega query that will check if items are updated or not...
			if (count($subqueries) > 1) {
				$sql = 'SELECT GREATEST('.implode('.updated_at, ', $subqueries).'.updated_at) AS updated_at, ('.implode('.count+', $subqueries).'.count) AS count FROM'.$sql;
			} else {
				$sql = 'SELECT q0.updated_at, q0.count FROM'.$sql;
			}
			
			// Run the query - this must be done now because we can't reliably get the correct results from what we have
			$result = \DB::query($sql)->execute()->as_array();
			$result = $result[0];
			$result['updated_at'] = strtotime($result['updated_at']);
			
		}
		
		// Add the rest of the stuff to the result
		$result['query_count'] = $num;
		$result['sql'] = $sql;
		$result['files'] = $this->files;
		$result['content'] = strval($this->request->response);
		$result['nocache'] = \CMF\Cache::getNoCacheAreas($result['content']);
		$result['logs_made'] = \CMF\Log::$logs_made;
		$result['content-type'] = 'text/html';
		
		// Store the content type header if it's set
		$headers = headers_list();
		foreach ($headers as $header) {
			if (stripos($header, 'content-type: ') === 0) {
				$result['content-type'] = substr($header, 14);
				break;
			}
		}
		
		// serialize and write it to disk
		\CMF\Cache::writeCacheFile($this->path, serialize($result));
		
	}
	
}