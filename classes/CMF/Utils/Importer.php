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
    public static function importData($data, $model, $auto_backup = true,$lang = null)
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
                $entity = static::createOrUpdateEntity($entity, $model, $data,$lang);
                \D::manager()->flush();
            }

            static::processDeletions($model);
            \D::manager()->flush();

            // Try and repair any trees that may have been corrupted during the import
            static::repairTrees(array_keys(static::$_updatedEntities));

        } catch (\Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }

        return array(
            'success' => true,
            'message' => \Lang::get('admin.messages.import_success')
        );
    }

    /**
     * Checks each class provided and repairs the tree if it is corrupt
     */
    protected static function repairTrees($classes)
    {
        $processed = array();

        try {
            \D::manager()->clear();
        } catch (\Exception $e) {}

        foreach ($classes as $class) {
            if (is_subclass_of($class, 'CMF\\Model\\Node')) {

                $metadata = $class::metadata();
                $className = $metadata->rootEntityName;
                if (in_array($className, $processed)) continue;
                $processed[] = $className;

                try {

                    $rootNode = $className::getRootNode(true);
                    $repo = \D::manager()->getRepository($className);
                    $qb = $repo->getNodesHierarchyQueryBuilder($rootNode);
                    $treeValid = $repo->verify();

                    if ($treeValid !== true) {
                        $repo->recover();
                        \D::manager()->flush();
                        \D::manager()->clear();
                    }

                } catch (\Exception $e) {  }

            }
        }
    }

    /**
     * Given some data, tries either update an existing entry in the local DB or creates a new one
     */
    public static function createOrUpdateEntity($data, $model, &$context = null,$lang = null)
    {
        if (!is_array($data)) return null;

        $oid = intval(\Arr::get($data, 'id', \Arr::get($data, '_oid_', 0)));
        $metadata = $model::metadata();
        $tableName = $metadata->table['name'];
        $model = $metadata->name;
        $associations = array();
        $entity = null;
        $changed = true;
        $polymorphic = $metadata->isInheritanceTypeJoined() || $metadata->isInheritanceTypeSingleTable();
        $typeField = $polymorphic ? \Arr::get($metadata->discriminatorColumn, 'name') : null;

        // We need to resolve the link if this is some sort of API-ish reference to another object somewhere
        if (static::isObjectReference($data)) {
            if (is_null($data['id'])) return null;
            $data = static::resolveObjectReference($data, $context);
        }

        // Make sure the class is in line with the discriminator attribute
        if ($polymorphic && isset($data[$typeField])) {
            $model = \Arr::get($metadata->discriminatorMap, $data[$typeField], $model);
            if ($model != $metadata->name) $metadata = $model::metadata();
            unset($data[$typeField]);
        }

        // If we're using inheritance, make sure we have the base table name
        if ($polymorphic && $metadata->rootEntityName != $model) {
            $rootModel = $metadata->rootEntityName;
            $tableName = $rootModel::metadata()->table['name'];
        }

        // Get the actual entity if we've already dealt with this before
        if ($processed = ($oid && isset(static::$_updatedEntities[$model]) && isset(static::$_updatedEntities[$model][$oid]))) {
            $entity = static::$_updatedEntities[$model][$oid];
            return $entity;
        }

        // Check if we're dealing with a tree structure
        if ($isTree = is_subclass_of($model, 'CMF\\Model\\Node')) {
            if (@$data['is_root']) {
                $entity = $model::getRootNode();
                if (isset($data['children'])) unset($data['children']);
            }
            if (isset($data['root'])) unset($data['root']);
            if (isset($data['rgt'])) unset($data['rgt']);
            //if (isset($data['lft'])) unset($data['lft']);
            //if (isset($data['lvl'])) unset($data['lvl']);
        }

        // Only ever one instance of static items
        if (!$entity && $model::_static()) {
            $entity = $model::instance();
        }

        if (isset($data['id']))
        {
            // Add original ID to the settings
            if (!isset($data['settings'])) $data['settings'] = array();
            $data['settings']['original_id'] = intval($data['id']);
            $data['_oid_'] = $data['id'];
            unset($data['id']);

            if (!$entity)
            {
                // Find the ID of the existing record
                $orig = '%s:11:"original_id";i:'.$data['_oid_'].';%';
                $orig_id = \DB::query("SELECT id FROM $tableName WHERE settings LIKE :orig LIMIT 1")
                    ->bind('orig', $orig)
                    ->execute()->get('id');

                if ($orig_id) {
                    $entity = $model::find(intval($orig_id));
                }
            }
        }
        $original_url = "";
        if(isset($data['original_url']))
        {
            $original_url = $data['original_url'];
            unset($data['original_url']);
        }


        // Find out whether the remote item has been updated since the last import
        if ($entity && !empty($entity->id) && !empty($data['updated_at']))
        {
            $wasImported = is_array($entity->settings) ? (\Arr::get($entity->settings, 'original_id') !== null) : false;
            if ($wasImported)
            {
                $updatedAt = new \DateTime($data['updated_at']);
                $localUpdatedAt = new \DateTime($entity->updated_at->format('Y-m-d H:i:s'), $updatedAt->getTimezone());
                $changed = ($updatedAt > $localUpdatedAt);
            }
        }

        // Create a new one if not found
        if (!$entity) {
            $entity = new $model();
        }
        \D::manager()->persist($entity);

        // Store the entity so we can reference later if needed
        if (isset($data['_oid_'])) {
            $oid = intval($data['_oid_']);
            if (!isset(static::$_updatedEntities[$model])) static::$_updatedEntities[$model] = array();
            static::$_updatedEntities[$model][$data['_oid_']] = $entity;
        }

        // Find out which fields we can import
        $fields = static::getImportableFields($metadata->name);

        // Clean up the array before populating
        foreach ($data as $field => $value)
        {
            if (!in_array($field, $fields)) {
                unset($data[$field]);
                continue;
            }

            if ($metadata->hasAssociation($field)) {
                $associations[$field] = $value;
                unset($data[$field]);
                continue;
            }

            if (in_array($metadata->getTypeOfField($field), array('date', 'datetime'))) {
                $data[$field] = new \DateTime($value);
            }
        }

        // Populate the entity
        if ($changed) {
            if (!isset($data['settings'])) $data['settings'] = array();
            $now = new \DateTime();
            $data['settings']['imported_from'] = \Arr::get($context, 'links.self');
            $data['settings']['imported_at'] = $now->format('Y-m-d H:i:s');
            $entity->populate($data);
            $entity->changed = false;
        }

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
                    $assocValue[] = static::createOrUpdateEntity($assoc, $assocClass, $context,$lang);
                }
            } else {
                $assocValue = static::createOrUpdateEntity($value, $assocClass, $context,$lang);
            }

            if ($assocValue) {
                $entity->set($field, $assocValue);
            }
        }



        // Sometimes field values rely on associations being present, so populate again!
        if ($changed) {
            $entity->populate($data);
            $entity->changed = false;
        }

        // Download any referenced files if we haven't done so already
        if ($changed && !$processed)
            static::downloadFilesForEntity($entity, $model, \Arr::get($context, 'links.self'));

        if(!empty($lang) && !empty($entity->url) && $entity->url instanceof \CMF\Model\URL )
        {
            if (!empty($original_url)) {
                $base_url = \CMF\Model\DevSettings::instance()->parent_site;
                $settings = $entity->settings;
                if (!isset($settings['languages']))
                    $settings['languages'] = array();
                $ownLang = \Config::get('language');
                if (!empty($ownLang) && isset($settings['languages'][$ownLang]))
                    unset($settings['languages'][$ownLang]);
                $settings['languages'][$lang] = $original_url;
                $entity->set('settings', $settings);
            }
        }

        return $entity;
    }

    protected static function getImportableFields($model)
    {
        $fields = $model::importFields();
        if (empty($fields) || !is_array($fields)) {
            $metadata = $model::metadata();
            $fields = array_merge($metadata->getFieldNames(), $metadata->getAssociationNames());
        }

        $exclude = $model::importFieldsExclude();
        if (!empty($exclude) && is_array($exclude)) {
            $fields = array_diff($fields, $exclude);
        }

        if (!in_array('settings', $fields)) {
            $fields[] = 'settings';
        }

        return $fields;
    }

    /**
     * Deletes any imported local data that wasn't present in the import
     */
    protected static function processDeletions($model)
    {
        if (!isset(static::$_updatedEntities[$model]) || !is_array(static::$_updatedEntities[$model])) return;

        $metadata = $model::metadata();
        $polymorphic = $metadata->isInheritanceTypeJoined() || $metadata->isInheritanceTypeSingleTable();
        $class = $metadata->name;

        // Find all the ids of database items that have been imported
        $localIds = $class::getImportedIds();

        // Now get all the ids that have just been processed
        $processedClasses = array($metadata->name);
        $processedIds = array();
        if ($polymorphic && count($metadata->subClasses)) {
            foreach ($metadata->subClasses as $subClass) {
                if (!in_array($subClass, $processedClasses)) $processedClasses[] = $subClass;
            }
        }

        foreach ($processedClasses as $processedClass) {
            if (!isset(static::$_updatedEntities[$processedClass]) || !is_array(static::$_updatedEntities[$processedClass])) continue;
            foreach (static::$_updatedEntities[$processedClass] as $id => $entity){
                if (!in_array($entity->id, $processedIds)) $processedIds[] = $entity->id;
            }
        }

        // Find the difference between the two
        asort($localIds);
        asort($processedIds);
        $diff = array_diff($localIds, $processedIds);

        // Delete ones that weren't imported this time
        if (count($diff))
        {
            $entities = $class::select('item')->where('item.id IN(:ids)')
            ->setParameter('ids', $diff)
            ->getQuery()->getResult();

            foreach ($entities as $entity) {
                \D::manager()->remove($entity);
            }
        }
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
                $data = static::getDataFromUrl($data['href']);
                $loaded = null;
                $lang = null;
                if(is_array($data) && isset($data['body'])) {
                    $loaded = $data['body'];
                    if(isset($data['lang']))
                        $lang = $data['lang'];
                }
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
                $data = static::getDataFromUrl($data['href']);
                $loaded = null;
                if(is_array($data) && isset($data['body']))
                    $loaded = $data['body'];
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

    /**
     * Downloads any files needed by the entity
     */
    public static function downloadFilesForEntity($entity, $class, $base_url)
    {
        $class = get_class($entity);
        $metadata = $class::metadata();
        $class = $metadata->name;

        $urlInfo = parse_url($base_url);
        if (!$urlInfo) return;

        $port = \Arr::get($urlInfo, 'port', 80);
        $url = \Arr::get($urlInfo, 'scheme', 'http').'://'.\Arr::get($urlInfo, 'host', '').($port != 80 ? ":$port" : '').'/';

        foreach ($metadata->getFieldNames() as $fieldName)
        {
            if (!in_array($metadata->getTypeOfField($fieldName), array('file', 'image'))) continue;

            $value = $entity->get($fieldName);
            $src = is_array($value) ? \Arr::get($value, 'src') : null;

            if (empty($src)) continue;

            $path = preg_replace('/^uploads/', 'uploads/imported', $src);
            $fullPath = DOCROOT.$path;
            $dirPath = @pathinfo($fullPath, PATHINFO_DIRNAME);

            if ($dirPath && !is_dir($dirPath)) {
                @mkdir($dirPath, 0775, true);
            }

            $value['src'] = $path;
            $entity->set($fieldName, $value);

            // Now download the file
            if ($contents = @file_get_contents($url.$src)) {
                @file_put_contents($fullPath, $contents);
            }
        }
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
            } catch (\Exception $e) {
                $test = "";
            }
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

        // Add parameters
        $params = $model::importParameters();
        if (!empty($params) && is_array($params)) {
            $url .= '?'.http_build_query($params);
        }

        try {
            $loaded = static::getDataFromUrl($url);
        } catch (\Exception $e) {

            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }

        $data = null;
        $lang = null;
        if(is_array($loaded) && isset($loaded['body'])) {
            $data = $loaded['body'];
            if(isset($loaded['lang']))
                $lang = $loaded['lang'];
        }

        // Standardise the root level meta object
        if (!isset($data['meta'])) $data['meta'] = array();
        if (!isset($data['meta']['type'])) $data['meta']['type'] = $type_plural;

        // Standardise the root level links object
        if (!isset($data['links'])) $data['links'] = array();
        if (!isset($data['links']['self'])) $data['links']['self'] = $url;

        // Import the data
        return static::importData($data, $model, false,$lang);
    }

    protected static function getDataFromUrl($url)
    {
        $request = \Request::forge($url, 'curl');

        $api_key = \CMF\Model\DevSettings::instance()->parent_site_api_key;
        if (!empty($api_key)) $request->set_header('Authorization', 'bearer '.\CMF\Model\DevSettings::instance()->parent_site_api_key);

        $request->set_option('HEADER', true);
        try {
            $request->execute();
        } catch (\Exception $e) {
            switch ($request->response()->status) {
                case 401:
                    $message = \Lang::get('admin.errors.import.url_auth_required');
                break;
                default:
                    $message = \Lang::get('admin.errors.import.url_inaccessible');
                break;
            }

            throw new \Exception($message, $request->response()->status);
        }

        $reponse = $request->response();
        $language = $reponse->get_header('Content-Language');
        $returnValue = array();
        $returnValue['body'] = $reponse->body;
        if(!empty($language))
            $returnValue['lang'] = $language;
        return $returnValue;
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
            throw new \Exception(\Lang::get('admin.errors.import.file_format_unknown'));
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
            throw new \Exception(\Lang::get('admin.errors.import.file_parse_error'));
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