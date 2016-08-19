<?php

namespace Admin;

use Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\Event\PostFlushEventArgs,
    Doctrine\Common\EventSubscriber;

class Extension_Languagecanonicalexporterlistener implements EventSubscriber
{
    private $jsonObject;
    private $toProcessPostFush;
    private $toProcessPostFushDelete;

	public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'postFlush'
        );
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $this->toProcessPostFush = array();
        $this->toProcessPostFushDelete = array();

        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        // Process the aliases after the other stuff
        $x = 0;
        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            if(!empty($entity->settings) && !isset($entity->settings['original_id']) && $entity->settings['original_id'] > 0){
                $this->toProcessPostFush[$x] = new \stdClass();
                $this->toProcessPostFush[$x]->id  = $entity->id;
                $this->toProcessPostFush[$x]->class  = get_class($entity);
                $x++;
            }
        }

        foreach ($uow->getScheduledEntityUpdates() AS $entity) {
            $metadata = $em->getClassMetadata(get_class($entity));
            //if change of Url
            if ($metadata->name == 'CMF\\Model\\URL') {
                $this->toProcessPostFush[$x] = new \stdClass();
                $this->toProcessPostFush[$x]->id  = $entity->item_id;
                $this->toProcessPostFush[$x]->class  = $entity->type;
                $x++;
            }
        }
        $y = 0;
        foreach ($uow->getScheduledEntityDeletions() AS $entity) {
            $metadata = $em->getClassMetadata(get_class($entity));
            //if change of Url
            if ($metadata->name == 'CMF\\Model\\URL') {
                $this->toProcessPostFushDelete[$x] = new \stdClass();
                $this->toProcessPostFushDelete[$x]->id  = $entity->item_id;
                $this->toProcessPostFushDelete[$x]->class  = $entity->type;
                $y++;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        foreach($this->toProcessPostFush as $anItem)
        {
            $className = $anItem->class;
            $item = $className::find($anItem->id);
            $this->processItem($item);
        }

        foreach($this->toProcessPostFushDelete as $anItem)
        {
            $className = $anItem->class;
            $item = $className::find($anItem->id);
            $this->processItem($item,true);
        }

        $this->exportLanguageCanonical();
    }


    protected function processItem(&$entity,$delete = false){
        $tableName = \Admin::getTableForClass(get_class($entity));
        if(empty($this->jsonObject))
        {
            $this->jsonObject = new \stdClass();
            $this->jsonObject->data = new \stdClass();
        }
        if(empty($this->jsonObject->data->{$tableName}))
            $this->jsonObject->data->{$tableName} = array();
        $object = $entity->jsonLanguageDataObject($delete);
        if(!in_array($object,$this->jsonObject->data->{$tableName}))
            $this->jsonObject->data->{$tableName}[] = $object;
    }

    private function exportLanguageCanonical()
    {
        $url = \Config::get('main_site_url')."/api/_lang-canonicals_";
        $curl = \Request::forge($url, 'curl');
        $curl->set_method('post');
        $curl->set_header('Content-Type', 'application/json');
        $curl->set_params(json_encode($this->jsonObject));
        $curl->execute();
    }
}