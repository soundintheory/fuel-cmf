<?php

namespace CMF\Utils;

use Doctrine\ORM\Tools\SchemaTool,
    Fuel\Core\Migrate,
    Oil\Generate;

/**
 * A static class through which all installation functionality can be accessed
 *
 * @package CMF
 */
class Installer
{
    public static function ensureMinimumSetup()
    {
        $first_time = \Config::get('cmf.install', true);

        if ($first_time) {
            \Config::load('cmf', true, true);
            \Config::load('db', true, true);
        }
    }

    /**
     * Sets site specific stuff for admin,
     * @param  array $settings Settings to enter into config
     * @return bool success or fail
     */
    public static function initialSetup($title = null, $identifier = null)
    {
        static::ensureMinimumSetup();

        if (\Fuel::$is_cli) {

            while (empty($title)) {
                $title = \Cli::prompt('Website Title');
                if (empty($title)) \Cli::error('You must enter a site title');
            }

            while (empty($identifier)) {
                $identifier = \Cli::prompt('Unique Identifier', str_replace('_', '', \Inflector::friendly_title($title, '_', true)));
                if (empty($identifier)) \Cli::error('You must enter a unique identifier for this site');
            }

            while (empty($template)) {
                $template = \Cli::prompt('Choose a template', str_replace('_', '','default'));
                if (!file_exists(CMFPATH.'templates/'.$template)){
                    \Cli::error('You must enter existing template');
                    unset($template);
                };
            }

        }

        static::copyAppTemplate($template);

        $config_path = APPPATH.'config/cmf.php';
        $db_config_path = APPPATH.'config/db.php';

        $config = \Config::load($config_path, false, true);
        $db_config = \Config::load($db_config_path, false, true);

        $config['install'] = false;
        $config['admin']['title'] = $title;
        $db_config['doctrine2']['cache_namespace'] = $identifier;

        if ($config_saved = \Config::save($config_path, $config)) {
            \Config::load('cmf', true, true);
        }

        if ($db_config_saved = \Config::save($db_config_path, $db_config)) {
            \Config::load('db', true, true);
        }

        static::cleanUpConfig($config_path);
        static::cleanUpConfig($db_config_path);

        return $config_saved && $db_config_saved;
    }

    /**
     * Sets up the database with the provided details and saves the config.
     * @return array
     */
    public static function createDatabase($host = 'localhost', $username = 'root', $password = 'root', $database = null)
    {
        // Get user input if we're in the CLI
        if (\Fuel::$is_cli) {
            $host = \Cli::prompt('DB Host', $host);
            $username = \Cli::prompt('Root DB User', $username);
            $password = \Cli::prompt('Root DB Password', $password);
        }

        // Connect to the MySQL instance
        $con = @mysql_connect($host, $username, $password) or $db_error = mysql_error();
        if (!$con) {
            return array(
                'error' => 'Could not make connection. '.$db_error
            );
        }

        // Get database name
        if (\Fuel::$is_cli) {
            $exists = true;
            $overwrite = false;

            while (empty($database)) {
                $database = \Cli::prompt('DB Name', \Config::get('db.doctrine2.cache_namespace', null));
                if (empty($database)) \Cli::error('You must enter a database name!');
            }

            $exists = mysql_num_rows(mysql_query("SHOW DATABASES LIKE '".$database."'", $con)) > 0;

            while ($exists && !$overwrite) {
                $overwrite = \Cli::prompt("The database '$database' already exists. Would you still like to use this?", array('y','n')) == 'y';

                if (!$overwrite) {
                    $database = null;
                    while (empty($database)) {
                        $database = \Cli::prompt('Enter an alternate DB name', \Config::get('db.doctrine2.cache_namespace', null));
                        if (empty($database)) \Cli::error('You must enter a database name!');
                    }
                }

                $exists = mysql_num_rows(mysql_query("SHOW DATABASES LIKE '".$database."'", $con)) > 0;
            }
        }

        // Try and create the database
        if (mysql_query("CREATE DATABASE IF NOT EXISTS ".$database, $con)) {
            mysql_close($con);
        } else {
            mysql_close($con);
            return array(
                'error' => 'Error creating database: '.mysql_error()
            );
        }

        // Set the config
        $development = static::setDBConfig($host, $username, $password, $database);
        $production = static::setDBConfig($host, $username, $password, $database, 'production');

        return array(
            'success' => true,
            'config' => ($development && $production)
        );
    }

    /**
     * Saves database config to disk
     * @return bool
     */
    public static function setDBConfig($host, $username, $password, $database, $env = 'development')
    {
        \Config::load("$env/db", true, true);
        \Config::set("$env/db.default.connection.dsn", "mysql:host=$host;dbname=$database");
        \Config::set("$env/db.default.connection.username", $username);
        \Config::set("$env/db.default.connection.password", $password);

        $result = \Config::save("$env/db", "$env/db");
        if ($result) {
            static::cleanUpConfig(APPPATH."config/$env/db.php");
            \Config::load("$env/db", true, true);
            \Config::load("db", true, true);
        }

        return $result;
    }

    /**
     * Cleans up generated php config
     */
    public static function cleanUpConfig($path)
    {
        $config = file_get_contents($path);
        $config = preg_replace('/[\s]+\=\>[\s]+[\n|\r]+[\t]+(array\()/', ' => $1', $config);
        $config = preg_replace('/[0-9][\s]+\=\>[\s]+/', '', $config);

        return file_put_contents($path, $config);
    }

    /**
     * This will copy the template app into the app dir overwriting what is there.
     * Cannot be undone
     * @return bool
     */
    public static function copyAppTemplate($template)
    {
        // classes
        if (is_dir(APPPATH.'classes')) \File::delete_dir(APPPATH.'classes');
        \File::copy_dir(CMFPATH.'templates/'.$template.'/app/classes', APPPATH.'classes');

        // config
        if (is_dir(APPPATH.'config')) \File::delete_dir(APPPATH.'config');
        \File::copy_dir(CMFPATH.'templates/'.$template.'/app/config', APPPATH.'config');

        // views
        if (is_dir(APPPATH.'views')) \File::delete_dir(APPPATH.'views');
        \File::copy_dir(CMFPATH.'templates/'.$template.'/app/views', APPPATH.'views');

        if(file_exists(CMFPATH.'templates/'.$template.'/assets')){
            \File::delete_dir(DOCROOT.'public/assets/');
            \File::copy_dir(CMFPATH.'templates/'.$template.'/assets', DOCROOT.'public/');
        }

        if(file_exists(CMFPATH.'templates/'.$template.'/cuts')){
            \File::delete_dir(DOCROOT.'public/cuts/');
            \File::copy_dir(CMFPATH.'templates/'.$template.'/cuts', DOCROOT.'public/');
        }

        if(file_exists(CMFPATH.'templates/'.$template.'/root')){
            \File::delete(DOCROOT.'bower.json');
            \File::delete(DOCROOT.'composer.json');
            \File::delete(DOCROOT.'Gruntfile.js');
            \File::delete(DOCROOT.'package.json');
            \File::copy(CMFPATH.'templates/'.$template.'/root/bower.json', DOCROOT);
            \File::copy(CMFPATH.'templates/'.$template.'/root/composer.json', DOCROOT);
            \File::copy(CMFPATH.'templates/'.$template.'/root/Gruntfile.js', DOCROOT);
            \File::copy(CMFPATH.'templates/'.$template.'/root/package.json', DOCROOT);
        }
        return true;
    }

}