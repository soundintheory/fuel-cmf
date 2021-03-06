<?php

namespace Fuel\Tasks;

use CMF\Utils\Installer,
    CMF\Utils\Project,
    CMF\Storage;

class Cmf
{
    
    /**
     * Runs the CMF installer
     */
    public function install()
    {
        if (strtolower(\Cli::prompt('Install CMF now? WARNING: This will reset the contents of your app folder!', array('y','n'))) !== 'y') {
            return;
        }
        
        // Site title etc
        Installer::initialSetup();
        
        // Database
        if (strtolower(\Cli::prompt('Create database now?', array('y','n'))) === 'y') {
            $result = Installer::createDatabase();
            if (isset($result['error'])) {
                \Cli::error('There was an error creating the database: '.$result['error']);
                exit();
            }
        }
        
        // This stuff relies on the database being set up
        if (Project::databaseExists()) {
            
            // Sync models
            \Cli::write(""); // Just a spacer!
            Project::sync('default', 'app', true, true);
            
            // Super user
            $hasSuper = Project::hasSuperUser();
            if (!$hasSuper || strtolower(\Cli::prompt('Create super user now?', array('y','n'))) === 'y') {
                if (!$hasSuper) \Cli::write("\nCreating a super user...");
                Project::createSuperUser();
            }
            
            // Check some DB settings are in place
            Project::ensureMinimumSettings();
        }
    }

    /**
     * Sync the database to the model classes
     */
    public function import()
    {
        $tables = func_get_args();

        if (empty($tables))
            throw new \Exception("No table was specified for import");

        foreach ($tables as $table_name)
        {
            // Find class name and metadata etc
            $class_name = \Admin::getClassForTable($table_name);
            if ($class_name === false) {
                $this->writeAndLog("Import error: Class could not be found for table '$table_name'", "error");
                continue;
            }

            // Import the data
            \Cli::write("Importing $table_name...");
            try {
                $import_result = \CMF\Utils\Importer::importUrl($class_name, \Input::post('import_url'));

                if (@$import_result['success']) {
                    $this->writeAndLog("Successfully imported $table_name", "info", "green");
                } else {
                    $this->writeAndLog("Error importing $table_name: ".@$import_result['message'] ?: "Unknown error importing $table_name", "error");
                }

            } catch (\Exception $e) {
                $this->writeAndLog("Error importing $table_name: ".$e->getMessage(), "error");
            }

        }
    }

    protected function writeAndLog($message, $level = "info", $color = null)
    {
        switch ($level) {
            case "error":
                //\Cli::error($message);
            default:
                \Cli::write($message, $color);
        }
        logger($level == 'info' ? 'alert' : $level, $message);
    }
    
    /**
     * Sync the database to the model classes
     */
    public function sync()
    {
        Project::sync();
    }
    
    /**
     * Create a user with super permissions
     */
    public function createSuperUser()
    {
        Project::createSuperUser();
    }
    
    /**
     * Generates a SQL dump of the database
     */
    public function db_backup()
    {
        $result = Project::backupDatabase(\Cli::option('name'), !!\Cli::option('gzip'));
        \Cli::write("Database '".$result['dbname']."' backed up to ".$result['file'].". Time taken: ".$result['time']."s", 'green');
    }
    
    /**
     * Converts any old image fields (strings) into the new style object ones
     */
    public function convertimages()
    {
        $em = \D::manager();
        $driver = $em->getConfiguration()->getMetadataDriverImpl();
        $tables_fields = array();
        $sql = array();
        
        // Loop through all the model metadata and check for image fields
        foreach ($driver->getAllClassNames() as $class) {
            
            $metadata = $em->getClassMetadata($class);
            $fields = $metadata->fieldMappings;
            $convert = array();
            
            foreach ($fields as $field_name => $field) {
                
                if ($field['type'] == 'image') $convert[] = $field_name;
                
            }
            
            if (count($convert) > 0) {
                
                $table = $metadata->table['name'];
                $refl_fields = $metadata->reflFields;
                
                foreach ($convert as $convert_field) {
                    
                    if (isset($refl_fields[$convert_field]) && $refl_fields[$convert_field]->class != $class) {
                        $field_table = \Admin::getTableForClass($refl_fields[$convert_field]->class);
                    } else {
                        $field_table = $table;
                    }
                    
                    $table_fields = \Arr::get($tables_fields, $field_table, array());
                    if (!in_array($convert_field, $table_fields)) $table_fields[] = $convert_field;
                    $tables_fields[$field_table] = $table_fields;
                    
                }
                
            }
            
        }
        
        foreach ($tables_fields as $table => $fields) {
            
            $results = \DB::query("SELECT id, ".implode(', ', $fields)." FROM $table")->execute();
            
            foreach ($results as $result) {
                
                foreach ($fields as $field) {
                    
                    $image = @unserialize($result[$field]);
                    if ($image === false) {
                        
                        $newimage = array( 'src' => $result[$field], 'alt' => '' );
                        $newimage = \DB::quote(serialize($newimage));
                        $sql[] = "UPDATE $table SET $field = $newimage WHERE id = ".$result['id'];
                        
                    }
                    
                }
                
            }
            
        }
        
        foreach ($sql as $query) {
            
            \DB::query($query)->execute();
            
        }
        
        \Cli::write('Done!', 'green');
        
    }

}
