<?php

namespace CMF\Utils;

use Doctrine\ORM\Tools\SchemaTool,
    Fuel\Core\Migrate,
    Oil\Generate;

/**
 * Manages the importing of data
 *
 * @package CMF
 */
class Importer
{
    protected static $_updatedEntities;

    /**
     * Imports data to the specified model
     */
    public static function importData($data, $model, $auto_backup = true)
    {
        // Allow ourselves some room, this could take a while!
        try {
            set_time_limit(0);
            ini_set('memory_limit', '256M');
        } catch (\Exception $e) { }

        // Reset updated entities
        static::$_updatedEntities = array();
        
        // Normalise the data so we always have a collection
        if (is_array($data['data']) && \Arr::is_assoc($data['data'])) {
            $data['data'] = array($data['data']);
        }

        try {
            // Loop through and import each external entity
            foreach ($data['data'] as $entity)
            {
                $entity = static::createOrUpdateEntity($entity, $model, $data);
                \D::manager()->flush($entity);
            }
        } catch (\Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }

        return array(
            'success' => true,
            'message' => 'Contents of remote URL imported successfully'
        );
    }

    /**
     * Given some data, tries either update an existing entry in the local DB or creates a new one
     */
    public static function createOrUpdateEntity($data, $model, &$context = null)
    {
        // If we ever deal with a URL, make sure URL processing is disabled
        if (trim($model, '\\') == 'CMF\\Model\\URL') {
            \CMF\Doctrine\Extensions\URLListener::$disableProcessing = true;
        }

        $oid = \Arr::get($data, 'id', \Arr::get($data, '_oid_'));
        $metadata = $model::metadata();
        $associations = array();
        $entity = null;

        // Return the actual entity if we've already dealt with this before
        if ($oid && isset(static::$_updatedEntities[$model]) && isset(static::$_updatedEntities[$model][$oid])) {
            $entity = static::$_updatedEntities[$model][$oid];
        }

        // We need to resolve the link if this is some sort of API-ish reference to another object somewhere
        if (static::isObjectReference($data)) {
            $data = static::resolveObjectReference($data, $context);
        }

        if (!$entity && isset($data['id']))
        {
            // Add original ID to the settings
            if (!isset($data['settings'])) $data['settings'] = array();
            $data['settings']['original_id'] = $data['id'];
            $data['_oid_'] = $data['id'];

            // Find the ID of the existing record
            $orig = '%s:11:"original_id";i:'.$data['id'].';%';
            $orig_id = \DB::query("SELECT id FROM ".$metadata->table['name']." WHERE settings LIKE :orig LIMIT 1")
                ->bind('orig', $orig)
                ->execute()->get('id');

            if ($orig_id) {
                $entity = $model::find(intval($orig_id));
            }

            unset($data['id']);
        }

        // Create a new one if not found
        if (!$entity) {
            var_dump("Creating $model");
            $entity = new $model();
            \D::manager()->persist($entity);
        }

        // Store the entity so we can reference later if needed
        if (isset($data['_oid_'])) {
            if (!isset(static::$_updatedEntities[$model])) static::$_updatedEntities[$model] = array();
            static::$_updatedEntities[$model][$data['_oid_']] = $entity;
        }

        // Clean up the array before populating
        foreach ($data as $field => $value)
        {
            if ($metadata->hasAssociation($field)) {
                $associations[$field] = $value;
                unset($data[$field]);
                continue;
            }
        }

        // Populate the entity
        $entity->populate($data);

        // Now populate the associations
        foreach ($associations as $field => $value)
        {
            $assocValue = null;
            $assocClass = $metadata->getAssociationTargetClass($field);

            if ($metadata->isCollectionValuedAssociation($field))
            {
                if (static::isCollectionReference($value)) {
                    $value = static::resolveCollectionReference($value, $context);
                }

                $assocValue = array();
                foreach ($value as $assoc)
                {
                    $assocValue[] = static::createOrUpdateEntity($assoc, $assocClass, $context);
                }
            } else {
                $assocValue = static::createOrUpdateEntity($value, $assocClass, $context);
            }

            if ($assocValue) {
                $entity->set($field, $assocValue);
            }
        }

        // Download any referenced files
        static::downloadFilesForEntity($entity, \Arr::get($context, 'links.self'));

        return $entity;
    }

    /**
     * Attempts to resolve a "JSON API" style single object reference
     */
    protected static function resolveObjectReference($data, &$context = null)
    {
        if ($context)
        {
            // Attempt to find a 'sideloaded' object
            if (isset($context['included']) && isset($context['included'][$data['type']]) && isset($context['included'][$data['type']][$data['id']])) {
                return $context['included'][$data['type']][$data['id']];
            }

            // If the type is equal to the base type of the context, try and find it from the main 'data' array
            if (isset($context['meta']) && $data['type'] == @$context['meta']['type'])
            {
                foreach ($context['data'] as $rootEntity)
                {
                    if (@$rootEntity['id'] == $data['id']) return $rootEntity;
                }
            }
        }

        // As a last resort, we will try and load it from the 'href' location
        if (!empty($data['href'])) {
            try {
                $loaded = static::getDataFromUrl($data['href']);
                if (is_array(@$loaded['included'])) {
                    $context['included'] = \Arr::merge(\Arr::get($context, 'included', array()), $loaded['included']);
                }
                if (isset($loaded['data'])) return $loaded['data'];
            } catch (\Exception $e) {}
        }

        return null;
    }

    /**
     * Attempts to resolve a "JSON API" style collection reference
     */
    protected static function resolveCollectionReference($data, &$context = null)
    {
        $found = array();

        if ($context)
        {
            if (isset($context['included']) && isset($context['included'][$data['type']]))
            {
                foreach ($data['ids'] as $id)
                {
                    // Attempt to find a 'sideloaded' object
                    if (isset($context['included'][$data['type']][$id])) {
                        $found[$id] = $context['included'][$data['type']][$id];
                        continue;
                    }

                    // If the type is equal to the base type of the context, try and find it from the main 'data' array
                    if (isset($context['meta']) && $data['type'] == @$context['meta']['type'])
                    {
                        foreach ($context['data'] as $rootEntity)
                        {
                            if (@$rootEntity['id'] == $id) {
                                $found[$id] = $rootEntity;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // As a last resort, we will try and load any missing entities from the 'href' location
        if (count($found) < count($data['ids']) && !empty($data['href']))
        {
            try {
                $loaded = static::getDataFromUrl($data['href']);
                if (isset($loaded['data'])) {
                    foreach ($loaded['data'] as $value)
                    {
                        $id = @$value['id'];
                        if ($id && !isset($found[$id])) $found[$id] = $value;
                    }
                }
                if (is_array(@$loaded['included'])) {
                    $context['included'] = \Arr::merge(\Arr::get($context, 'included', array()), $loaded['included']);
                }
            } catch (\Exception $e) {}
        }

        // Build the output array
        $output = array();
        foreach ($data['ids'] as $id)
        {
            if (isset($found[$id])) $output[] = $found[$id];
        }

        return $output;
    }

    public static function downloadFilesForEntity($entity, $base_url)
    {
        $urlInfo = parse_url($base_url);
        if (!$urlInfo) return;

        $port = \Arr::get($urlInfo, 'port', 80);
        $url = \Arr::get($urlInfo, 'scheme', 'http').'://'.\Arr::get($urlInfo, 'host', '').($port != 80 ? ":$port" : '').'/';

        $class = get_class($entity);
        $metadata = $class::metadata();

        foreach ($metadata->getFieldNames() as $fieldName)
        {
            $fieldType = $metadata->getTypeOfField($fieldName);

            if (in_array($fieldType, array('file', 'image'))) 
            {
                //$src = 
            }
        }

        //var_dump($metadata); exit();
    }

    /**
     * Checks whether an array conforms to the "JSON API" style approach of collection reference
     */
    protected static function isCollectionReference($data)
    {
        if (isset($data['ids']) && isset($data['type']))
        {
            if (count($data) === 2) return true;
            return isset($data['href']) && count($data) === 3;
        }
        return false;
    }

    /**
     * Checks whether an array conforms to the "JSON API" style approach of object reference
     */
    protected static function isObjectReference($data)
    {
        if (isset($data['id']) && isset($data['type']))
        {
            if (count($data) === 2) return true;
            return isset($data['href']) && count($data) === 3;
        }
        return false;
    }

    /**
     * Attempts to import data from a file
     */
    public static function importFile($file, $model, $auto_backup = true)
    {
        // Try to back up the DB first
        if ($auto_backup) {
            try {
                $result = Project::backupDatabase('pre_import', true);
            } catch (\Exception $e) { }
        }

        // Get the data from the file
        try {
            $data = static::parseImportFile($file);
        } catch (\Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }

        // Import the data
        return static::importData($data, $model, false);
    }

    /**
     * Attempts to import data from a file
     */
    public static function importUrl($model, $base_url = null, $auto_backup = true)
    {
        // Try to back up the DB first
        if ($auto_backup) {
            try {
                $result = Project::backupDatabase('pre_import', true);
            } catch (\Exception $e) { }
        }

        // Base URL fallback
        if (empty($base_url)) {
            $base_url = \CMF\Model\DevSettings::instance()->parent_site;
        }

        // Can't continue if the base URL is empty
        if (empty($base_url)) {
            return array(
                'success' => false,
                'message' => 'No URL was provided for import'
            );
        }

        // Create the request
        $type = $model::importType();
        $type_plural = \Inflector::pluralize($type);
        $url = rtrim($base_url, '/').'/api/'.$type_plural.'.json';

        try {
            $data = static::getDataFromUrl($url);
        } catch (\Exception $e) {

            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }

        // Standardise the root level meta object
        if (!isset($data['meta'])) $data['meta'] = array();
        if (!isset($data['meta']['type'])) $data['meta']['type'] = $type_plural;

        // Standardise the root level links object
        if (!isset($data['links'])) $data['links'] = array();
        if (!isset($data['links']['self'])) $data['links']['self'] = $url;

        // Import the data
        return static::importData($data, $model, false);
    }

    protected static function getDataFromUrl($url)
    {
        $request = \Request::forge($url, 'curl');

        $api_key = \CMF\Model\DevSettings::instance()->parent_site_api_key;
        if (!empty($api_key)) $request->set_header('Authorization', 'bearer '.\CMF\Model\DevSettings::instance()->parent_site_api_key);

        try {
            $request->execute();
        } catch (\Exception $e) {

            switch ($request->response()->status) {
                case 401:
                    $message = 'URL requires authentication. Check your API key settings.';
                break;
                default:
                    $message = 'There was a problem accessing that URL. Please check and try again.';
                break;
            }

            throw new \Exception($message, $request->response()->status);
        }

        return $request->response()->body;
    }

    /**
     * Attempts to parse a file containing data for import
     */
    public static function parseImportFile($path)
    {
        $data = null;
        $columns = null;
        $output = array();

        // Work out some basic config settings
        \Config::load('format', true);

        // Work out the format from the extension
        $pathinfo = pathinfo($path);
        $format = strtolower($pathinfo['extension']);

        // Stop if we don't support the format
        if (!static::supportsFormat($format)) {
            throw new \Exception('The file format was not recognised');
        }

        // Work out how to parse the data
        switch ($format) {
            case 'xls':
            case 'xlsx':

                $data = \Format::forge($path, 'xls')->to_array();
                $first = array_shift($data);
                $columns = is_array($first) ? array_filter(array_map(function($key) {
                    return \Inflector::friendly_title($key, '_', true);
                }, array_values($first))) : array();
                
            break;
            default:

                $data = @file_get_contents($path);
                if (strpos($data, "\n") !== false) {
                    \Config::set('format.csv.regex_newline', "\n");
                } else if (strpos($data, "\r") !== false) {
                    \Config::set('format.csv.regex_newline', "\r");
                }
                $data = \Format::forge($data, $format)->to_array();

                // Find out some stuff...
                $first = \Arr::get($data, '0');
                $columns = is_array($first) ? array_map(function($key) {
                    return \Inflector::friendly_title($key, '_', true);
                }, array_keys($first)) : array();

            break;
        }

        if (count($columns) > 0) {
            foreach ($data as $num => $row) {
                $values = array_values($row);
                $filtered = array_filter($values);
                if (count($values) > count($columns)) {
                    $values = array_slice($values, 0, count($columns));
                } else if (count($values) < count($columns)) {
                    while (count($values) < count($columns)) {
                        $values[] = null;
                    }
                }
                if (!empty($filtered)) $output[] = array_combine($columns, $values);
            }
        } else {
            $columns = $data = $output = null;
        }

        // Stop if there's no data by this point
        if (!$data) {
            throw new \Exception('There was an unknown problem parsing the file for import. Please check the formatting.');
        }

        return array(
            'columns' => $columns,
            'data' => $output
        );
    }

    public static function supportsFormat($format)
    {
        $supportedFormats = array('csv', 'xml', 'json', 'yaml');
        if (class_exists('PHPExcel_IOFactory'))
            $supportedFormats[] = array_merge($supportedFormats, array('xslx', 'xls'));

        return in_array(strtolower($format), $supportedFormats);
    }
}