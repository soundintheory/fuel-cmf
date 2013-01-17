<?php

namespace CMF;

use Doctrine\ORM\Tools\SchemaTool,
    Fuel\Core\Migrate,
    Oil\Generate,
    Gedmo\Mapping\Annotation as Gedmo;

/**
 * A static class through which all installation functionality can be accessed
 *
 * @package CMF
 */
class Install
{
    /**
     * This will copy the template app into the app dir overwriting what is there. Cannot be undone
     * @return bool
     */
    public static function copy_app()
    {
        \File::delete_dir(APPPATH);
        \File::copy_dir(CMFPATH . 'templates' . DS .'app', APPPATH, $area = null);
        return true;
    }
    
    /**
     * Sets up the database with the provided details and saves the config.
     * @return array [0] result [1] error message
     */
    public static function db_setup($host = null, $username = null, $password = null, $database = null)
    {
        $con = @mysql_connect($host, $username, $password) or $db_error = mysql_error();
        
        if (!$con) {
            return array(
                false,
                'error' => 'Could not make connection. '.$db_error
            );
        }
        
        if (mysql_query("CREATE DATABASE IF NOT EXISTS ".$database, $con)) {
            mysql_close($con);
        } else {
            mysql_close($con);
            return array(
                false,
                "error"=>"Error creating database: " . mysql_error()
            );
        }
        
        if(\CMF\Install::set_db_config($host, $username, $password, $database)) {
            return array(
                true,
            );
        } else {
            return array(
                false,
                "Could not save database config."
            );
        }
        
    }
    
     /**
     * Saves config with database details.
     * @return bool
     */
    public static function set_db_config($host = null, $username = null, $password = null, $database = null)
    {
        \Config::load('development/db', true);
        \Config::set('development/db.default.connection.dsn', "mysql:host=".$host.";dbname=".$database);
        \Config::set('development/db.default.connection.username', $username);
        \Config::set('development/db.default.connection.password', $password);
        \Config::set('development/db.default.connection.dbname', $database);
        
        if(\Config::save('development/db', 'development/db')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Installation Migration
     * @return bool
     */
    public function install_migration($name = 'default', $type = 'app')
    {
        Migrate::_init();
        $files = glob(APPPATH."migrations/*_*.php");
        $last_file = end($files);
        
        if ($last_file) {
            
            // Try and find the last file migration in the DB
            $last_name = basename($last_file, ".php");
            $migration_table = \Config::get('migrations.table', 'migration');
            $last_migration = \DB::query("SELECT migration FROM $migration_table WHERE type = '$type' AND name = '$name' AND migration = '$last_name'")->execute();
            
            if (count($last_migration) === 0) {
                Migrate::latest($name, $type);
            }
            
        }
        
        $diff = $this->getDiff();
        if (array_key_exists('error', $diff)) {
            return false;
        }
        
        $this->generate($diff['up'], $diff['down']);
        Migrate::latest($name, $type);
        return true;
    }
    
     /**
     * Creates super user and emails them
     * 
     * @param  string $username   
     * @param  string $password
     * @param  string $email 
     * @return bool success
     */
    public static function createSuperUser($username = 'administrator', $password = null, $email = "system.admin@soundintheory.co.uk")
    {
        if(!$password){
            // include(CMFPATH."vendor/passgen/pwgen.class.php");
            // $pwgen = new \PWGen();
            // $password = $pwgen->generate();
            // $password = "Pa$$sw0rd";
        }
        
        $em = \DoctrineFuel::manager();
        $user = new \Admin\Model_User();
        
        $user->set('email', $email);
        $user->set('username', $username);
        $user->set('password', $password);
        $user->set('super_user', true);
        
        $role = \CMF\Model\Role::findBy(array("name = 'admin'"));
        if (count($role) == 0){
            $role = new \CMF\Model\Role();
            $role->set('name', 'admin');
            $role->set('description', 'users of this admin site');
            $em->persist($role);
        } else {
          $role = $role[0];
        }
        $user->add('roles',$role);
        
        if (!$user->validate()) {
            return false;
        }
        
        $em->persist($user);
        $em->flush();
        
        // lets assume it all went ok..
        return \CMF\Install::email_user($username, $password, $email);
    }
    
    /**
     * emails the user specified
     * 
     * @param  string $username   
     * @param  string $password
     * @param  string $email 
     * @return bool success
     */
    public static function email_user($username, $password, $email)
    {
        $title = \Config::get('cmf.admin.title');
        $message = "A new super user has been created on ".$title."\n
        Username: ".$username."\n
        Password: ".$password."\n";
        
        // Create an instance
        $new_email = \Email::forge();
        
        // Set the from address
        $new_email->from('cmf@soundintheory.co.uk');
        
        // Set the to address
        $new_email->to($email);
        
        // Set a subject
        $new_email->subject("Account creation on ".$title);
        
        // And set the body.
        $new_email->body($message);
        $mail = $new_email->send();
        return ($mail);
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
        if (($duplicates = glob(APPPATH."migrations/*_{$name}*")) === false) {
            throw new Exception("Unable to read existing migrations. Do you have an 'open_basedir' defined?");
        }
        
        if (count($duplicates) > 0) {
            // Tear up the file path and name to get the last duplicate
            $file_name = pathinfo(end($duplicates), PATHINFO_FILENAME);
            $name = \Str::increment(substr($file_name, 4), 2);
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
        Generate::build(true);
    }
    
    /**
     * Helper function that uses Doctrine's SchemaTool to generate the difference between mapping and database both ways. Ignores table names specified in 'doctrine.ignore_tables' config setting
     * 
     * @return array    Associative array containing the 'up' and 'down' values
     */
    protected function getDiff()
    {
        $em = \DoctrineFuel::manager();
        $conn = $em->getConnection();
        $platform = $conn->getDatabasePlatform();
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $result = array();
        
        if (empty($metadata)) {
            return array( 'error' => "\tNo mapping information to process." );
        }
        
        $tool = new SchemaTool($em);
        $fromSchema = $conn->getSchemaManager()->createSchema();
        $toSchema = $tool->getSchemaFromMetadata($metadata);
        
        // Ignore tables...
        $ignored_tables = \Config::get('doctrine.ignore_tables', array());
        foreach ($ignored_tables as $ignored_table) {
            // Only ignore the table if it isn't defined by entities
            if ($fromSchema->hasTable($ignored_table) && !$toSchema->hasTable($ignored_table)) {
                $fromSchema->dropTable($ignored_table);
            }
        }
        
        $up = $this->buildCodeFromSql($fromSchema->getMigrateToSql($toSchema, $platform));
        $down = $this->buildCodeFromSql($fromSchema->getMigrateFromSql($toSchema, $platform));
        
        if ( ! $up && ! $down) {
            return array( 'error' => "\tNo changes detected in your mapping information." );
        }
        
        return array( 'up' => $up, 'down' => $down );
    }
    
    /**
     * Returns the last migration number
     * 
     * @return string   The migration number as a string, padded with a leading zero if less than 10
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
        foreach ($sql as $query) {
            $code[] = "\t\t\\DB::query(\"$query\")->execute();".PHP_EOL;
        }
        return implode("\n", $code);
    }
    
    /**
     * Disables Installer by setting config value
     * 
     */
    public static function disable_install()
    {
        $app_config_path = APPPATH.'config/cmf.php';
        $app_config = \Config::load($app_config_path, null, true);
        \Arr::set($app_config, 'admin.install', false);
        
        if (\Config::save($app_config_path, $app_config)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Sets site specific stuff for admin, 
     * @param  array  $settings single level array of settings to enter into config
     * @return bool success or fail 
     */
    public static function setup_admin_config($config_settings)
    {
        $app_config_path = APPPATH.'config/cmf.php';
        $app_config = \Config::load($app_config_path, false, true);
        
        foreach ($config_settings as $key => $value) {
            \Arr::set($app_config, 'admin.'.$key, $value);
        }
        
        if (\Config::save($app_config_path, $app_config)) {
            \Config::load('cmf', true, true);
            return true;
        }
        
        return false;
    }
    
}