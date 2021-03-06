<?php

namespace CMF\Doctrine\Extensions;

use Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\Event\PostFlushEventArgs,
    Doctrine\Common\EventSubscriber;

class Canonicallistener implements EventSubscriber
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
            if (($entity instanceof \CMF\Model\URL) && !empty($entity->type) && class_exists($entity->type)) {
                $this->toProcessPostFush[$x] = new \stdClass();
                $this->toProcessPostFush[$x]->id  = $entity->item_id;
                $this->toProcessPostFush[$x]->class  = $entity->type;
                $x++;
            }
        }

        foreach ($uow->getScheduledEntityUpdates() AS $entity) {
            if (($entity instanceof \CMF\Model\URL) && !empty($entity->type) && class_exists($entity->type)) {
                $this->toProcessPostFush[$x] = new \stdClass();
                $this->toProcessPostFush[$x]->id  = $entity->item_id;
                $this->toProcessPostFush[$x]->class  = $entity->type;
                $x++;
            }
        }
        foreach ($uow->getScheduledEntityDeletions() AS $entity) {
            if (($entity instanceof \CMF\Model\URL) && !empty($entity->type) && class_exists($entity->type)) {
                $className = $entity->type;
                $item = $className::find($entity->item_id);
                $this->processItem($item,true);
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

        if(!empty($this->jsonObject))
            $this->exportLanguageCanonical();
    }


    protected function processItem(&$entity,$delete = false){
        if(!empty($entity)) {
            if (!empty($entity->settings) && isset($entity->settings['original_id']) && $entity->settings['original_id'] > 0) {
                $tableName = \Admin::getTableForClass($entity->getEntityClass());
                if (empty($this->jsonObject)) {
                    $this->jsonObject = new \stdClass();
                    $this->jsonObject->data = new \stdClass();
                }
                if (empty($this->jsonObject->data->{$tableName}))
                    $this->jsonObject->data->{$tableName} = array();
                $object = $entity->jsonLanguageDataObject($delete);
                if (!in_array($object, $this->jsonObject->data->{$tableName}))
                    $this->jsonObject->data->{$tableName}[] = $object;
            }
        }
    }

    private function exportLanguageCanonical()
    {
        $base_url = \CMF\Model\DevSettings::instance()->parent_site;
        if(!empty($base_url)) {
            $url = $base_url . "/api/_lang-canonicals_";
            $curl = \Request::forge($url, 'curl');
            if (!empty($api_key)) $curl->set_header('Authorization', 'bearer ' . \CMF\Model\DevSettings::instance()->parent_site_api_key);
            $curl->set_method('post');

            $curl->set_header('Content-Type', 'application/json');
            $curl->set_header('Content-Language', \Config::get('language'), true);

            $curl->set_params(json_encode($this->jsonObject));
            $curl->execute();
        }
    }
}