<?php

namespace CMF\Doctrine;

use Doctrine\DBAL\Logging\SQLLogger;

class QueryLogger implements SQLLogger {
	
	protected $logger;
	public $queries = array();
	public $logAllQueries = false;
	
	public function __construct($logger = null)
	{
		$this->logger = $logger;
	}

	public function startQuery($sql, ?array $params = null, ?array $types = null)
	{
		if ($this->logger) $this->logger->startQuery($sql, $params, $types);
		
		// Store select queries for later use
		if ($this->logAllQueries || substr($sql, 0, 6) == 'SELECT') {
			
			if ($params) {
				// Replace placeholders so that we can log a final SQL query for profiler's EXPLAIN statement
				// (not a full SQL parser - placeholders inside string literals aren't skipped - but fine for ORM-generated queries)
				$conn = \D::manager()->getConnection();
				$quote = function($value, $type) use ($conn) {
					if (is_array($value)) return implode(', ', array_map(array($conn, 'quote'), array_map('strval', $value)));
					if ($value === null) return 'NULL';
					if (is_bool($value)) return $value ? '1' : '0';
					if ($value instanceof \DateTimeInterface) $value = $value->format('Y-m-d H:i:s');
					return is_int($value) ? (string) $value : $conn->quote((string) $value);
				};

				if (is_numeric(key($params))) {
					$values = array_values($params);
					$types = is_array($types) ? array_values($types) : array();
					$i = 0;
					$final_sql = preg_replace_callback('/\?/', function() use (&$i, $values, $types, $quote) {
						$index = $i++;
						return array_key_exists($index, $values) ? $quote($values[$index], \Arr::get($types, $index)) : '?';
					}, $sql);
				} else {
					$final_sql = preg_replace_callback('/(?<!:):([a-zA-Z_][a-zA-Z0-9_]*)/', function($m) use ($params, $types, $quote) {
						return array_key_exists($m[1], $params) ? $quote($params[$m[1]], \Arr::get((array) $types, $m[1])) : $m[0];
					}, $sql);
				}
				
				$this->queries[] = $final_sql;
				
			} else {
				
				$this->queries[] = $sql;
				
			}
			
		}
	}

	public function stopQuery()
	{
		if ($this->logger) $this->logger->stopQuery();
	}
	
	public function setLogger($logger)
	{
		$this->logger = $logger;
	}
}