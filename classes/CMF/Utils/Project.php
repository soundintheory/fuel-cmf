<?php

namespace CMF\Utils;

use Doctrine\ORM\Tools\SchemaTool,
    Fuel\Core\Migrate,
    Oil\Generate,
    PWGen,
    mysqli;

/**
 * Project workflow utilities
 *
 * @package CMF
 */
class Project
{
	
	/**
	 * Checks for differences in mapping information compared to the database, and creates a migration if there are any
	 * 
	 * @param  string $name Name of module or package, unless this is just an 'app' migration
	 * @param  string $type Type of migration - 'app', 'module' or 'package'
	 * @return void
	 */
	public static function sync($name = 'default', $type = 'app', $run = false, $reset = false)
	{
		try {
    	    set_time_limit(0);
    	    ignore_user_abort(true);
    	    ini_set('memory_limit', '256M');
    	} catch (\Exception $e) {
    	    // Nothing!
    	}
		
		\Config::load("db", true, true);

		\D::$clear_cache = true;
		$em = \D::manager();
		
		$files = glob(APPPATH."migrations/*_*.php");
		if ($reset) {
			
			$files[] = APPPATH.'config/development/migrations.php';
			$files[] = APPPATH.'config/production/migrations.php';
			$files[] = APPPATH.'config/staging/migrations.php';
			
			foreach ($files as $file) {
				try {
					\File::delete($file);
				} catch (\Exception $e) {}
			}
			
			$files = array();
			\Config::load('migrations', true, true);
		}
		
		// This will create the migrations table if necessary
		Migrate::_init();
		
		$last_file = end($files);
		if ($last_file) {
			
			// Try and find the last file migration in the DB
			$last_name = basename($last_file, ".php");
			$migration_table = \Config::get('migrations.table', 'migration');
			$last_migration = \DB::query("SELECT migration FROM $migration_table WHERE type = '$type' AND name = '$name' AND migration = '$last_name'")->execute();
			
			if (count($last_migration) === 0) {
				if (\Fuel::$is_cli && \Cli::prompt('You have previous migrations to run - you must run these before generating a new one. Continue?', array('y','n')) == 'y') {
					\Cli::write("\tRunning previous migrations...", 'green');
					Migrate::latest($name, $type);
				} else {
					return array( 'error' => 'There are previous migrations to run - please do this first before syncing.' );
				}
			}
			
		}
		
		$diff = static::getDiff();
		
		if (array_key_exists('error', $diff)) {
			
			if (\Fuel::$is_cli) {
				\Cli::write($diff['error'], 'red');
				return;
			} else {
				return $diff;
			}
			
		}
		
		static::generateMigration($diff['up'], $diff['down']);
		
		if ((\Fuel::$is_cli && ($run === true || \Cli::prompt('Would you like to run the new migration now?', array('y','n')) == 'y')) || (!\Fuel::$is_cli && $run)) {
			Migrate::latest($name, $type);
			static::createAllStaticInstances();
		}
		
		return array( 'success' => 'The sync was completed and all migrations performed successfully' );
	}
	
	/**
	 * Checks the settings model (if any) and attempts to populate it with some defaults
	 */
	public static function ensureMinimumSettings()
	{
		// Find the settings model
		$em = \D::manager();
		$driver = $em->getConfiguration()->getMetadataDriverImpl();
		
		// Loop through all the model metadata and check for image fields
		foreach ($driver->getAllClassNames() as $class) {
			
			$metadata = $em->getClassMetadata($class);
			$settings = null;
			if (is_subclass_of($class, 'CMF\\Model\\Settings') && !$metadata->isMappedSuperclass && $class::_static()) {
				$settings = $class::instance();
				break;
			}
			
		}
		
		// Fail silently if we didn't find any settings
		if (is_null($settings) || empty($settings)) return;
		
		// Site title
		if (!isset($settings->site_title)) {
			$settings->set('site_title', \Config::get('cmf.admin.title', 'Website'));
		}
		
		// Start page
		if (!isset($settings->start_page)) {
			
			$url = \DB::query("SELECT id FROM urls WHERE url = '/' LIMIT 1")->execute()->get('id');
			$start_page = !is_null($url) ? intval($url) : null;
			
			// If we can't find a root URL, just select the first one we can find (if any)
			if (is_null($start_page)) {
				try {
					$url = \DB::query('SELECT id FROM urls LIMIT 1')->execute()->get('id');
					$start_page = !is_null($page) ? intval($page) : null;
				} catch (\Exception $e) {}
			}
			
			$settings->set('start_page', $start_page);
		}
		
		$em->persist($settings);
		$em->flush();
	}
	
	/**
	 * Populates the 'tables > classes' and 'classes > tables' maps.
	 * @return void
	 */
	public static function createAllStaticInstances()
	{
		$em = \D::manager();
		$driver = $em->getConfiguration()->getMetadataDriverImpl();
		
		// Loop through all Doctrine's class names, get metadata for each and populate the maps
		foreach ($driver->getAllClassNames() as $class)
		{
			$metadata = $em->getClassMetadata($class);
			
			if (!$metadata->isMappedSuperclass && is_subclass_of($class, 'CMF\\Model\\Base')) {
				if ($class::_static() === true) {
					$class::instance();
				}
			}
		}
		
	}
	
	/**
	 * Writes a migration class to the filesystem
	 * 
	 * @param  string $up   PHP code to migrate up
	 * @param  string $down PHP code to migrate down
	 * @param  string $name The name of the migration, usually involving a timestamp
	 * @return void
	 */
	protected static function generateMigration($up = '', $down = '', $name = null)
	{
		// Get the migration name
		empty($name) and $name = 'Migration'.date('YmdHis');
		
		// Check if a migration with this name already exists
		if (($duplicates = glob(APPPATH."migrations/*_{$name}*")) === false) {
			throw new \Exception("Unable to read existing migrations. Do you have an 'open_basedir' defined?");
		}

		if (count($duplicates) > 0) {
			// Don't override a file
			if (\Fuel::$is_cli && \Cli::option('s', \Cli::option('skip')) === true) {
				return;
			}
			
			// Tear up the file path and name to get the last duplicate
			$file_name = pathinfo(end($duplicates), PATHINFO_FILENAME);
			
			// Override the (most recent) migration with the same name by using its number
			if (\Fuel::$is_cli && \Cli::option('f', \Cli::option('force')) === true)
			{
				list($number) = explode('_', $file_name);
			}
			// Name clashes but this is done by hand. Assume they know what they're doing and just increment the file
			else
			{
				// Increment the name of this
				$name = \Str::increment(substr($file_name, 4), 2);
			}
		}

		$name = ucfirst(strtolower($name));

		$migration = <<<MIGRATION
<?php

namespace Fuel\Migrations;

class {$name}
{
	public function up()
	{
{$up}
	}

	public function down()
	{
{$down}
	}
}
MIGRATION;

		$number = isset($number) ? $number : static::findMigrationNumber();
		$filepath = APPPATH . 'migrations/'.$number.'_' . strtolower($name) . '.php';
		
		Generate::create($filepath, $migration, 'migration');
		Generate::build();
	}
	
	/**
	 * Helper function that uses Doctrine's SchemaTool to generate the difference between mapping and database both ways. Ignores table names specified in 'doctrine.ignore_tables' config setting
	 * 
	 * @return array	Associative array containing the 'up' and 'down' values
	 */
	protected static function getDiff()
    {
        $em = \D::manager();
        $conn = $em->getConnection();
        $platform = $conn->getDatabasePlatform();
        $metadata = $em->getMetadataFactory()->getAllMetadata();
		$result = array();
		
        if (empty($metadata))
        {
            return array( 'error' => "\tNo mapping information to process." );
        }
        
        $tool = new SchemaTool($em);
        $fromSchema = $conn->getSchemaManager()->createSchema();
        $toSchema = $tool->getSchemaFromMetadata($metadata);
		
		// Ignore tables...
		$ignored_tables = \Config::get('db.doctrine2.ignore_tables', array());
		
		// Check if languages are enabled
		$languages = \Config::get('cmf.languages.enabled', false);
		if (!$languages) {
			$ignored_tables[] = 'languages';
			$ignored_tables[] = 'lang';
			$ignored_tables[] = 'ext_translations';
		}
		
		// Construct an array of wildcard-checking lambda functions
		$wildcard_checks = array();
		foreach ($ignored_tables as $num => $ignored_table) {
			if (($pos = strpos($ignored_table, '*')) !== false) {
				
				$search_str = str_replace('*', '', $ignored_table);
				
				if ($pos === 0) {
					// Check at the end of the string
					$wildcard_checks[] = function($str) use($search_str) {
						return strpos($str, $search_str) === (strlen($str) - strlen($search_str));
					};
				} else {
					// Check at the beginning of the string
					$wildcard_checks[] = function($str) use($search_str) {
						return strpos($str, $search_str) === 0;
					};
				}
				
				unset($ignored_tables[$num]);
			}
		}
		
		// Go through all the table names and check for wildcard matches
		$table_names = array_merge($fromSchema->getTableNames(), $toSchema->getTableNames());
		foreach ($table_names as $table_name) {
			
			// Remove the schema name from the table name
			$table_name = str_replace(array($fromSchema->getName().".", $toSchema->getName()."."), '', $table_name); 
			
			if (in_array($table_name, $ignored_tables)) continue;
			
			foreach ($wildcard_checks as $check) {
				if ($check($table_name) === true) {
					$ignored_tables[] = $table_name;
					break;
				}
			}
			
		}
		
		// Strip the ignored tables from the schemas
		foreach ($ignored_tables as $ignored_table) {
			if ($fromSchema->hasTable($ignored_table)) $fromSchema->dropTable($ignored_table);
			if ($toSchema->hasTable($ignored_table)) $toSchema->dropTable($ignored_table);
		}
		
        $up = static::buildCodeFromSql($fromSchema->getMigrateToSql($toSchema, $platform));
        $down = static::buildCodeFromSql($fromSchema->getMigrateFromSql($toSchema, $platform));
        
        // TODO: Create a more generic way of implementing data fixtures for each class
        if ($languages && ($toSchema->hasTable('languages') && !$fromSchema->hasTable('languages'))) {
        	
        	$up .= "\n\t\t// Creating the first language";
        	$up .= "\n\t\t\$lang = new \CMF\Model\Language();";
        	$up .= "\n\t\t\$lang->set('code', \Lang::get_lang());";
        	$up .= "\n\t\t\D::manager()->persist(\$lang);";
        	$up .= "\n\t\t\D::manager()->flush();";
        	
        }
        
        if ( ! $up && ! $down) {
			return array( 'error' => "\tNo changes detected in your mapping information.\n" );
		}
		
		return array( 'up' => $up, 'down' => $down );
    }
	
	/**
	 * Returns the last migration number
	 * 
	 * @return string	The migration number as a string, padded with a leading zero if less than 10
	 */
	protected static function findMigrationNumber()
	{
		$glob = glob(APPPATH .'migrations/*_*.php');
		list($last) = explode('_', basename(end($glob)));
		
		return str_pad($last + 1, 3, '0', STR_PAD_LEFT);
	}
	
	/**
	 * Writes out the provided SQL queries as php code that will perform them
	 * 
	 * @param  array  $sql Array of SQL commands
	 * @return string      The PHP code
	 */
	protected static function buildCodeFromSql(array $sql)
    {
        $code = array();
        foreach ($sql as $query)
        {
			$code[] = "\t\t\\DB::query(\"$query\")->execute();".PHP_EOL;
        }
        return implode("\n", $code);
    }
    
    /**
     * Checks whether the configured database exists for this project
     * @return boolean
     */
    public static function databaseExists()
    {
    	try {
    		// Just a simple test that will throw an error if the database can't be connected to
    		$test = \DB::quote('some thing');
    	} catch (\Exception $e) {
    		return false;
    	}
    	
    	return true;
    }
    
    /**
     * Checks whether there is a super user in the database
     * @return boolean
     */
    public static function hasSuperUser()
    {
    	\Module::load('admin');
    	return count(\Admin\Model_User::select('item.id')->where('item.super_user = true')->getQuery()->getArrayResult()) > 0;
    }
    
    public static function backupDatabase($name = null, $gzip = false)
    {
    	try {
    	    set_time_limit(0);
    	    ignore_user_abort(true);
    	    ini_set('memory_limit', '512M');
    	} catch (\Exception $e) {
    	    // Nothing!
    	}
    	
    	$time = -microtime(true);
    	
    	// Check the db backup directory exists
    	$dir = realpath(APPPATH.'../..').'/db/backups';
    	if (!($exists = is_dir($dir))) {
    	    $exists = @mkdir($dir, 0775, true);
    	}
    	
    	if (!$exists) {
    		throw new \Exception("The directory '$dir' does not exist or is not writable. SQL dump failed");
    	}
    	
    	// Get the connection config out of Doctrine
    	$config = \D::manager()->getConnection()->getParams();
    	$conn = null;
    	
    	if (isset($config['port'])) {
    	    $conn = new mysqli(
    	        \Arr::get($config, 'host', 'localhost'),
    	        \Arr::get($config, 'username', 'root'),
    	        \Arr::get($config, 'password', 'root'),
    	        \Arr::get($config, 'dbname', 'null'),
    	        \Arr::get($config, 'port', 3306)
    	    );
    	} else {
    	    $conn = new mysqli(
    	        \Arr::get($config, 'host', 'localhost'),
    	        \Arr::get($config, 'username', 'root'),
    	        \Arr::get($config, 'password', 'root'),
    	        \Arr::get($config, 'dbname', 'null')
    	    );
    	}
    	
    	// Save the dump
    	if (is_null($name) || empty($name)) $name = $config['dbname'];
    	$file = $dir.'/'.$name.'_'.date('Y-m-d-H-i') . '.sql'.($gzip ? '.gz' : '');
    	$dump = new MySQLDump($conn);
    	$dump->save($file);
    	
    	return array(
    		'success' => true,
    		'dbname' => $config['dbname'],
    		'file' => $file,
    		'time' => number_format($time+microtime(true), 2)
    	);
    }
    
    /**
     * Creates a super user
     * @return array
     */
    public static function createSuperUser($email = 'cmf@soundintheory.co.uk', $username = 'admin', $password = null)
    {
    	// Load up the admin module and it's classes, otherwise we won't get
    	// access to the admin user class
    	\Module::load('admin');
        
        if (\Fuel::$is_cli) {
            $email = \Cli::prompt('Enter an email address', $email);
            $username = \Cli::prompt('Enter a user name', $username);
            $first = true;
            
            while ($first || (strlen($password) > 0 && strlen($password) < 6)) {
                $password = \Cli::prompt('Enter a password (leave blank to generate one)');
                if (strlen($password) > 0 && strlen($password) < 6) {
                    \Cli::error('The password must be 6 characters or more!');
                }
                $first = false;
            }
            
            $confirm_password = '';
            if (empty($password)) {
            	
            	// The user left the password field blank, so we are generating one
            	$gen = new PWGen(3, false, false, false, false, false, false);
            	$password = $confirm_password = $gen->generate().'-'.$gen->generate().'-'.$gen->generate();
            	
            } else {
            	
            	// If the user entered a password, we need them to confirm it
            	while ($confirm_password != $password) {
            	    $confirm_password = \Cli::prompt('Confirm password');
            	    if ($confirm_password != $password) \Cli::error('The passwords do not match!');
            	}
            	
            }
        }
        
        // Check if the user exists
        $em = \D::manager();
        $user = \Admin\Model_User::select('item')->where("item.username = '$username'")->getQuery()->getResult();
        $exists = count($user) > 0;
        
        if ($exists) {
        	$user = $user[0];
        } else {
        	$user = new \Admin\Model_User();
        }
        
        // Populate the user
        $user->set('email', $email);
        $user->set('username', $username);
        $user->set('password', $password);
        $user->set('confirm_password', $confirm_password);
        $user->set('super_user', true);
        
        // Create the admin role
        $role = \CMF\Model\Role::findBy(array("name = 'admin'"))->getQuery()->getResult();
        if (count($role) == 0) {
            $role = new \CMF\Model\Role();
            $role->set('name', 'admin');
            $role->set('description', 'users of this admin site');
            $em->persist($role);
        } else {
        	$role = $role[0];
        }
        $user->add('roles', $role);
        
        // Validate the newly created user
        if (!$user->validate()) {
            
            if (\Fuel::$is_cli) {
                \Cli::write('There was something wrong with the info you entered. Try again!', 'red');
                static::createSuperUser();
            } else {
                return array( 'errors' => $user->errors );
            }
            
        }
        
        $em->persist($user);
        $em->flush();
        
        \Cli::write($exists ? "\n\tExisting super user updated:" : "\n\tNew super user created:", 'light_gray');
        \Cli::write("\tusername:    ".$username."\n\tpassword:    ".$password."\n", 'light_cyan');
    }
	
}