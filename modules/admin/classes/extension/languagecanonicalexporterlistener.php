<?php

namespace Admin;

use Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\Common\EventSubscriber;

class Extension_Languagecanonicalexporterlistener implements EventSubscriber
{
    private $jsonObject;

	public function getSubscribedEvents()
    {
        return array(
            'onFlush'
        );
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        // Process the aliases after the other stuff
        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            if(!empty($entity->settings) && !isset($entity->settings['original_id']) && $entity->settings['original_id'] > 0)
                $this->processItem($entity, $em);
        }

        foreach ($uow->getScheduledEntityUpdates() AS $entity) {
            $metadata = $em->getClassMetadata(get_class($entity));
            //if change of Url
            if ($metadata->name == 'CMF\\Model\\URL') {
                $this->processUrlItem($entity, $em);
            }
        }

        $this->exportLanguageCanonical();
    }

    protected function processUrlItem(&$entity, &$em){
        $modelClass = $entity->type;
        $model = $modelClass::find($entity->item_id);
        $this->processItem($model,$em);
    }

    protected function processItem(&$entity, &$em){
        $tableName = \Admin::getTableForClass(get_class($entity));
        if(empty($this->jsonObject))
        {
            $this->jsonObject = new \stdClass();
            $this->jsonObject->data = new \stdClass();
        }
        if(empty( $this->jsonObject->data->$tableName))
            $this->jsonObject->data->$tableName = array();
        $object = $entity->jsonLanguageDataObject();
        if(!in_array($object,$this->jsonObject->data->{$tableName}))
            $this->jsonObject->data->{$tableName}[] = $object;
    }

    private function exportLanguageCanonical()
    {
        $urlParsed = parse_url($this->settings['imported_from']);
        $url = $urlParsed['scheme']."://".$urlParsed['host'].(isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '')."/api/_lang-canonicals_";
        $curl = Request::forge($url, 'curl');
        $curl->set_method('post');
        $curl->set_header('Content-Type', 'application/json');
        $curl->set_params($this->jsonObject);
    }
}