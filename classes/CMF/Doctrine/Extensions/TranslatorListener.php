<?php

namespace CMF\Doctrine\Extensions;

use Doctrine\Common\EventArgs,
    Doctrine\Common\EventSubscriber,
    Doctrine\ORM\ORMInvalidArgumentException,
    Gedmo\Tool\Wrapper\AbstractWrapper,
    Gedmo\Mapping\MappedEventSubscriber,
    CMF\Doctrine\Extensions\Translatable;


/**
 * The translation listener handles the generation and
 * loading of translations for entities which implements
 * the Translatable interface.
 *
 * This behavior can impact the performance of your application
 * since it does an additional query for each field to translate.
 *
 * Nevertheless the annotation metadata is properly cached and
 * it is not a big overhead to lookup all entity annotations since
 * the caching is activated for metadata
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatorListener implements EventSubscriber
{
    public $toProcess;
    public $entityChangesets;
    public $childLanguages = null;

    private $inProgress = false;
    private $isInPostFlush = false;

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'postFlush',
            'onFlush',
        );
    }

    /**
     * Gets the entities scheduled for insert / update / delete and flags them for processing
     */
    public function onFlush(EventArgs $eventArgs)
    {
        // Check we aren't already running this
        if ($this->inProgress) return;
        $this->inProgress = true;

        // Reset the processing arrays
        $this->entityChangesets = array();
        $this->toProcess = array();

        // Set up child languages, stop if there are none
        $this->initLanguages();
        if (!count($this->childLanguages)) return;
        $uow = \D::manager()->getUnitOfWork();

        // Log any entities needing to be processed
        foreach ($uow->getScheduledEntityInsertions() as $object) {
            $this->addEntityForProcessing($object);
        }
        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->addEntityForProcessing($object);
        }
    }

    /**
     * Processes the entities that were marked during onFlush
     */
    public function postFlush(EventArgs $args)
    {
        // Check we aren't already running this
        if($this->isInPostFlush) return;
        $this->isInPostFlush = true;

        // Loop through and process entities
        if (count($this->toProcess)) {
            foreach ($this->toProcess as $hash => $entity) {
                $this->processEntityByHash($hash);
            }
        }

        // Reset processing data
        $this->entityChangesets = array();
        $this->toProcess = array();
        $this->isInPostFlush = false;
        $this->inProgress = false;
    }

    /**
     * Processes the entity with the given an object hash
     */
    private function processEntityByHash($hash)
    {
        if (!count($this->childLanguages)) return;
        if (!isset($this->toProcess[$hash]) || !isset($this->entityChangesets[$hash])) return;

        $entity = $this->toProcess[$hash];
        $entity_class = $entity->metadata()->name;
        $entity_id = $entity->id;
        $entityChangeset = $this->entityChangesets[$hash];
        $translatableFields = \CMF\Admin::getTranslatable($entity->metadata()->name);
        $initial = array();

        // Populate the data to translate from
        foreach ($translatableFields as $fieldName) {
            $initial[$fieldName] = $entity->get($fieldName);
        }

        // Get translations for each child language
        foreach ($this->childLanguages as $language)
        {
            // Detach the model and reload it in the target language
            \D::manager()->detach($entity);
            Translatable::setLang($language->code);
            $entity = $entity_class::find($entity_id);
            $fields = $entityChangeset;

            // Find non-translated fields
            if ($entity) {
                foreach ($translatableFields as $field) {
                    if (!$entity->hasTranslation($field) && !empty($initial[$field]) && !in_array($field, $fields)) {
                        $fields[] = $field;
                    }
                }
            }

            // Perform the translation while we're in the target language
            if (count($fields)) {
                $this->translateFields($entity, $initial, $fields, $language->update_from, $language);
            }

            // Set the old language back again
            Translatable::setLang($language->update_from->code);
        }

        unset($this->toProcess[$hash]);
        unset($this->entityChangesets[$hash]);
    }

    /**
     * Translate a list of fields for an entity
     */
    private function translateFields($entity, $data, $fields, $from, $to)
    {
        $apiKey = \Config::get('cmf.languages.google_translate.api_key');
        if (!$apiKey) return;
        $url = \Config::get('cmf.languages.google_translate.base_url');
        $query = 'key='.$apiKey;

        $used_fields = array();
        $big_fields = array();

        // Build the query string
        foreach ($fields as $field) {
            $value = preg_replace('/\s+/m', ' ', $data[$field]);
            if (strlen($value) < 500) {
                $query .= '&q=' . urlencode($value);
                $used_fields[] = $field;
            } else {
                $big_fields[$field] = $value;
            }
        }
        $query .= '&source=' . $from->code . '&target=' . $to->code;

        // Execute the request
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: GET'));
        curl_setopt($handle, CURLOPT_POSTFIELDS, $query);
        $response = curl_exec($handle);

        // Handle errors
        if (FALSE === $response) {
            logger('error', @curl_errno($handle)." : ".@curl_error($handle));
            return;
        }

        curl_close($handle);

        // Decode the response and set each field back to the entity
        $responseDecoded = json_decode($response, true);
        if (isset($responseDecoded['data'])) {
            
            foreach($used_fields as $pos => $field) {
                $entity->set($field , $responseDecoded['data']['translations'][$pos]['translatedText']);
            }

            // Now translate the big fields
            foreach ($big_fields as $field => $big_value) {
                $translated = $this->translateString($big_value, $from, $to);
                if ($translated) {
                    $entity->set($field, $translated);
                }
            }

            \D::manager()->persist($entity);
            \D::manager()->flush($entity);

        } else {
            // An error response has been returned
            logger('error', 'An error was returned from the translate API: '.print_r($responseDecoded, true));
        }
        
        usleep(500000);
    }

    /**
     * Translate a single string
     */
    private function translateString($string, $from, $to)
    {
        if (strlen(trim(strip_tags($string))) > 10000) return null;

        $apiKey = \Config::get('cmf.languages.google_translate.api_key');
        if (!$apiKey) return null;

        // Build the url
        $url = \Config::get('cmf.languages.google_translate.base_url');
        $query = 'key='.$apiKey.'&q='.urlencode($string).'&source=' . $from->code . '&target=' . $to->code;

        // Execute the request
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: GET'));
        curl_setopt($handle, CURLOPT_POSTFIELDS, $query);
        $response = curl_exec($handle);

        // Handle errors
        if (FALSE === $response) {
            logger('error', @curl_errno($handle)." : ".@curl_error($handle));
            return null;
        }

        curl_close($handle);

        // Decode the response and set each field back to the entity
        $responseDecoded = json_decode($response, true);
        if (isset($responseDecoded['data'])) {
            try {
                return $responseDecoded['data']['translations'][0]['translatedText'];
            } catch (\Exception $e) {
                // Nothing
            }
        } else {
            // An error response has been returned
            logger('error', 'An error was returned from the translate API: '.print_r($responseDecoded, true));
        }

        return null;
    }

    /**
     * Translate a single string
     */
    private function translateArray($array, $from, $to)
    {
        $apiKey = \Config::get('cmf.languages.google_translate.api_key');
        if (!$apiKey) return null;

        // Build the url
        $url = \Config::get('cmf.languages.google_translate.base_url');
        $query = 'key='.$apiKey.'&source=' . $from . '&target=' . $to;

        foreach ($array as $item) {
            $query .= '&q='.urlencode($item);
        }

        // Execute the request
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: GET'));
        curl_setopt($handle, CURLOPT_POSTFIELDS, $query);
        $response = curl_exec($handle);

        // Handle errors
        if (FALSE === $response) {
            logger('error', @curl_errno($handle)." : ".@curl_error($handle));
            return null;
        }

        curl_close($handle);

        // Decode the response and set each field back to the entity
        $responseDecoded = json_decode($response, true);
        $result = null;
        if (isset($responseDecoded['data'])) {
            try {

                $translated = \Arr::pluck($responseDecoded['data']['translations'], 'translatedText');
                if (count($translated) === count($array)) {
                    $result = array_combine(array_keys($array), array_values($translated));
                }
            } catch (\Exception $e) {
                // Nothing
            }
        } else {
            // An error response has been returned
            logger('error', 'An error was returned from the translate API: '.print_r($responseDecoded, true));
        }

        return $result;
    }

    /**
     * Adds an entity for processing, but only if it has changed translatable fields
     */
    protected function addEntityForProcessing($entity)
    {
        // Only look at relevant models
        if (!$this->canProcess($entity)) return;

        $hash = spl_object_hash($entity);
        $changed = $this->getChangeset($entity);

        $this->entityChangesets[$hash] = $changed;
        $this->toProcess[$hash] = $entity;
    }

    /**
     * Get the auto translatable fields which have changed for an entity
     */
    protected function getChangeset($entity)
    {
        $entity_class = $entity->metadata()->name;
        $translatableFields = \CMF\Admin::getTranslatable($entity_class);
        $excludedFields = $entity_class::excludeAutoTranslations();

        if (\Input::param('force_translate', false) !== false) {

            return array_values(array_diff(array_values($translatableFields), $excludedFields));

        } else {
            $changeset = \D::manager()->getUnitOfWork()->getEntityChangeSet($entity);
            if (is_array($changeset)) $changeset = array_keys($changeset);
            else $changeset = array();
        }

        return array_diff(array_values(array_intersect($translatableFields, $changeset)), $excludedFields);
    }

    /**
     * Set up the array of "child" languages which need to be updated by others
     */
    protected function initLanguages()
    {
        // Only need to do this once!
        if ($this->childLanguages !== null) return;

        $this->childLanguages = \CMF\Model\Language::select('item', 'item', 'item.code')
            ->where('update_from IS NOT NULL')
            ->andWhere('update_from.code = :code')
            ->andWhere('item.visible = true')
            ->leftJoin('item.update_from', 'update_from')
            ->setParameter('code', \CMF::lang())
            ->getQuery()->getResult();
    }

    protected function canProcess($model)
    {
        if (!($model instanceof \CMF\Model\Base)) return false;

        // Check it's not excluded
        $exclude = \Config::get('cmf.languages.exclude_auto_translate', array());
        return !in_array($model->metadata()->name, $exclude);
    }

    /**
     * Auto translate common terms
     */
    public function onSaveTerms($file, $lang, $language)
    {
        if (in_array($file.($language ?: ''), \Lang::$auto_translated)) {
            return;
        }

        // Try and find child languages
        $childLanguages = \CMF\Model\Language::select('item')
            ->where('update_from IS NOT NULL')
            ->andWhere('update_from.code = :code')
            ->andWhere('item.visible = true')
            ->leftJoin('item.update_from', 'update_from')
            ->setParameter('code', $language)
            ->getQuery()->getResult();

        // Run if child languages are found
        if (!count($childLanguages)) return;

        foreach ($childLanguages as $childLanguage)
        {
            // Translate the terms for each language
            $terms = $this->translateArray($lang, $language, $childLanguage->code);
            if (is_array($terms)) {
                try {
                    \Lang::save($file, $terms, $childLanguage->code);
                    \Lang::$auto_translated[] = $file.($childLanguage->code ?: '');
                } catch (\Exception $e) {  }
            }
        }
    }
}

