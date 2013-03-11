<?php

namespace CMF\Doctrine;

use Doctrine\DBAL\Logging\SQLLogger;

class QueryLogger implements SQLLogger {
	
	public $queries = array();

	public function startQuery($sql, array $params = null, array $types = null)
	{
		// Store select queries for later use
		if (substr($sql, 0, 6) == 'SELECT') {
			
			if ($params) {
				// Attempt to replace placeholders so that we can log a final SQL query for profiler's EXPLAIN statement
				// (this is not perfect-- getPlaceholderPositions has some flaws-- but it should generally work with ORM-generated queries)
				
				$is_positional = is_numeric(key($params));
				list($sql, $params, $types) = \Doctrine\DBAL\SQLParserUtils::expandListParameters($sql, $params, $types);
				if (empty($types))
					$types = array();
				$placeholders = \Doctrine\DBAL\SQLParserUtils::getPlaceholderPositions($sql, $is_positional);
				
				if ($is_positional)
					$map = array_flip($placeholders);
				else
				{
					$map = array();
					foreach ($placeholders as $name=>$positions)
					{
						foreach ($positions as $pos)
							$map[$pos] = $name;
					}
				}
				
				ksort($map);
				$src_pos = 0;
				$final_sql = '';
				$first_param_index = key($params);
				foreach ($map as $pos=>$replace_name)
				{
					$final_sql .= substr($sql, $src_pos, $pos-$src_pos);
					
					if ($sql[$pos] == ':')
					{
						$src_pos = $pos + strlen($replace_name);
						$index = trim($replace_name, ':');
					}
					else // '?' positional placeholder
					{
						$src_pos = $pos + 1;
						$index = $replace_name + $first_param_index;
					}
					
					$final_sql .= \DoctrineFuel::manager()->getConnection()->quote( $params[ $index ], \Arr::get($types, $index) );
				}
				
				$final_sql .= substr($sql, $src_pos);
				
				$this->queries[] = $final_sql;
				
			} else {
				
				$this->queries[] = $sql;
				
			}
			
		}
	}

	public function stopQuery()
	{
		
	}
}