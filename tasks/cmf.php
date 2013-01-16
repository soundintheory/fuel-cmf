<?php

namespace Fuel\Tasks;

use Doctrine\ORM\Tools\SchemaTool,
	Fuel\Core\Migrate,
	Oil\Generate,
	Gedmo\Mapping\Annotation as Gedmo;

class Cmf
{
	
	/**
	 * Copies all the files to start a project, does an initial migration and will offer to create a super user
	 * 
	 * @return void
	 */
	public function install()
	{
	    // Copy files here...
	    
	    \Cli::write("\tSyncing the database...");
	    $this->sync();
	    
	    if (\Cli::prompt('Would you like to create a super user now?', array('y','n')) == 'y')
	    {
			$this->createSuperUser();
		} 
	}
	
	/**
	 * Creates a super user
	 * 
	 * @return void
	 */
	public function createSuperUser()
	{
	    $em = \DoctrineFuel::manager();
		$user = new \Admin\Model_User();
		
		$email = \Cli::prompt('Enter an email address', 'cmf@soundintheory.co.uk');
		$user->set('email', $email);
		$username = \Cli::prompt('Enter a user name', 'admin');
		$user->set('username', $username);
		$password = \Cli::prompt('Enter a password');
		$user->set('password', $password);
		$user->set('super_user', true);
		
		$role = \CMF\Model\Role::findBy(array("name = 'admin'"));
		if (count($role) == 0)
		{
		    $role = new \CMF\Model\Role();
		    $role->set('name', 'admin');
		    $role->set('description', 'users of this admin site');
		    $em->persist($role);
		}
		
		$user->add('roles', $role);
		
		if (!$user->validate())
		{
		    \Cli::write('There was something wrong with the info you entered. Try again!', 'red');
		    $this->createSuperUser();
		}
		
		$em->persist($user);
		$em->flush();
	}
	
	/**
	 * Checks for differences in mapping information compared to the database, and creates a migration if there are any
	 * 
	 * @param  string $name Name of module or package, unless this is just an 'app' migration
	 * @param  string $type Type of migration - 'app', 'module' or 'package'
	 * @return void
	 */
	public function sync($name = 'default', $type = 'app')
	{
		Migrate::_init();
		
		$files = glob(APPPATH."migrations/*_*.php");
		$last_file = end($files);
		
		if ($last_file) {
			
			// Try and find the last file migration in the DB
			$last_name = basename($last_file, ".php");
			$migration_table = \Config::get('migrations.table', 'migration');
			$last_migration = \DB::query("SELECT migration FROM $migration_table WHERE type = '$type' AND name = '$name' AND migration = '$last_name'")->execute();
			
			if (count($last_migration) === 0)
			{
				if (\Cli::prompt('You have previous migrations to run - you must run these before generating a new one. Continue?', array('y','n')) == 'y')
				{
					\Cli::write("\tRunning previous migrations...", 'green');
					Migrate::latest($name, $type);
				}
				else
				{
					return;
				}
			}
			
		}
		
		$diff = $this->getDiff();
		if (array_key_exists('error', $diff))
		{
			\Cli::write($diff['error'], 'red');
			return;
		}
		
		$this->generate($diff['up'], $diff['down']);
		
		if (\Cli::prompt('Would you like to run the new migration now?', array('y','n')) == 'y')
		{
			Migrate::latest($name, $type);
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
	protected function generate($up = '', $down = '', $name = null)
	{
		// Get the migration name
		empty($name) and $name = 'Migration'.date('YmdHis');
		
		// Check if a migration with this name already exists
		if (($duplicates = glob(APPPATH."migrations/*_{$name}*")) === false)
		{
			throw new Exception("Unable to read existing migrations. Do you have an 'open_basedir' defined?");
		}

		if (count($duplicates) > 0)
		{
			// Don't override a file
			if (\Cli::option('s', \Cli::option('skip')) === true)
			{
				return;
			}
			
			// Tear up the file path and name to get the last duplicate
			$file_name = pathinfo(end($duplicates), PATHINFO_FILENAME);
			
			// Override the (most recent) migration with the same name by using its number
			if (\Cli::option('f', \Cli::option('force')) === true)
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

		$number = isset($number) ? $number : $this->findMigrationNumber();
		$filepath = APPPATH . 'migrations/'.$number.'_' . strtolower($name) . '.php';

		Generate::create($filepath, $migration, 'migration');
		Generate::build();
		
	}
	
	/**
	 * Helper function that uses Doctrine's SchemaTool to generate the difference between mapping and database both ways. Ignores table names specified in 'doctrine.ignore_tables' config setting
	 * 
	 * @return array	Associative array containing the 'up' and 'down' values
	 */
	protected function getDiff()
    {
        $em = \DoctrineFuel::manager();
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
		$ignored_tables = \Config::get('doctrine.ignore_tables', array());
		foreach ($ignored_tables as $ignored_table)
		{
			// Only ignore the table if it isn't defined by entities
			if ($fromSchema->hasTable($ignored_table) && !$toSchema->hasTable($ignored_table)) 
			{
				$fromSchema->dropTable($ignored_table);
			}
		}
		
        $up = $this->buildCodeFromSql($fromSchema->getMigrateToSql($toSchema, $platform));
        $down = $this->buildCodeFromSql($fromSchema->getMigrateFromSql($toSchema, $platform));
        
        if ( ! $up && ! $down)
        {
			return array( 'error' => "\tNo changes detected in your mapping information." );
		}
		
		return array( 'up' => $up, 'down' => $down );
    }
	
	/**
	 * Returns the last migration number
	 * 
	 * @return string	The migration number as a string, padded with a leading zero if less than 10
	 */
	protected function findMigrationNumber()
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
	protected function buildCodeFromSql(array $sql)
    {
        $code = array();
        foreach ($sql as $query)
        {
			$code[] = "\t\t\\DB::query(\"$query\")->execute();".PHP_EOL;
        }
        return implode("\n", $code);
    }

}
