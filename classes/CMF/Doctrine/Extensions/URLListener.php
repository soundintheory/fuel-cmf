<?php

namespace CMF\Doctrine\Extensions;

use Doctrine\Common\EventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\Event\PostFlushEventArgs,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\Common\NotifyPropertyChanged,
    Doctrine\Common\Annotations\Reader,
    Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Mapping\ClassMetadataInfo;

class URLListener implements EventSubscriber
{
    protected $toFlush = array();
    
    /**
     * Specifies the list of events to listen to
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'postPersist',
        	'postFlush',
            'onFlush'
        );
    }
    
    public function postPersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $entity = $args->getEntity();
        
        $this->processNew($entity, $em, $uow);
    }
    
    public function postFlush(PostFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        
        if (count($this->toFlush) > 0) {
            
            foreach ($this->toFlush as $num => $item) {
                
                $em->persist($item);
                
            }
            
            $em->flush();
            $this->toFlush = array();
            
        }
        
    }
    
    public function onFlush(OnFlushEventArgs $args)
    {
        $this->toFlush = array();
        
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        
        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            $this->process($entity, $em, $uow);
        }
        
		foreach ($uow->getScheduledEntityUpdates() AS $entity) {
			$this->process($entity, $em, $uow);
		}
        
    }
    
    /**
     * Takes an entity and works out whether it has a relation to CMF's URL Model. If so,
     * it updates the properties of the associated URL object.
     * 
     * @param object $entity
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ORM\UnitOfWork|null $uow
     * @return void
     */
    protected function process(&$entity, &$em, &$uow)
    {
        $entity_class = get_class($entity);
    	$metadata = $em->getClassMetadata($entity_class);
    	$url_associations = $metadata->getAssociationsByTargetClass('CMF\\Model\\URL');
        
    	if (!empty($url_associations)) {
            
            //print("processing $entity_class");
    		
            // A bit hacky, but if this is a root tree item don't bother
            if (property_exists($entity, 'is_root') && $entity->is_root === true) return;
            
    		$url_field = null;
            foreach ($url_associations as $key => $association) {
                if ($association['type'] == ClassMetadataInfo::ONE_TO_ONE && $association['orphanRemoval']) {
                    $url_field = $key;
                    break;
                }
            }
            
            if ($url_field == null) return;
            
            $settings = $entity->settings();
            $url_settings = isset($settings[$url_field]) ? $settings[$url_field] : array();
            $url_item = $entity->get($url_field);
            if ($new_url = is_null($url_item)) $url_item = new \CMF\Model\URL();
            
            $prefix = $entity->urlPrefix();
            $slug = '';
            
            if (isset($url_settings['keep_updated']) && !$url_settings['keep_updated']) {
                $slug = \CMF::slug($url_item->slug);
            } else {
                $slug = $entity->slug();
            }
            
            $url = $prefix.$slug;
            $current_url = $url_item->url;
            
            // Skip this if the url hasn't changed
            if (!$new_url && $current_url == $url) return;
            
            $url_item->set('item_id', $entity->get('id'));
            $url_item->set('prefix', $prefix);
            $url_item->set('slug', $slug);
            $url_item->set('url', $url);
            $url_item->set('type', $metadata->name);
            $entity->set($url_field, $url_item);
            $em->persist($url_item);
            
            $url_metadata = $em->getClassMetadata('CMF\\Model\\URL');
            $url_changeset = $uow->getEntityChangeSet($url_item);
            
            if (!empty($url_changeset)) {
                $uow->recomputeSingleEntityChangeSet($url_metadata, $url_item);
            } else {
                $uow->computeChangeSet($url_metadata, $url_item);
            }
            
            
            $uow->recomputeSingleEntityChangeSet($metadata, $entity);
            
            $associations = $metadata->getAssociationMappings();
            foreach ($associations as $association_name => $association) {
                // Only do it if it's the inverse side, to prevent the dreaded infinite recursion
                if (!$association['isOwningSide']) {
                    $items = $entity->$association_name;
                    
                    if (!empty($items)) {
                        foreach ($items as $item) {
                            
                            $this->process($item, $em, $uow);
                            
                        }
                    }
                }
            }
    	}
        
    }
    
    protected function processNew(&$entity, &$em, &$uow)
    {
        $metadata = $em->getClassMetadata(get_class($entity));
        $url_associations = $metadata->getAssociationsByTargetClass('CMF\\Model\\URL');
        
        if (!empty($url_associations)) {
            
            // A bit hacky, but if this is a root tree item don't bother
            if (property_exists($entity, 'is_root') && $entity->is_root === true) return;
            
            $url_field = null;
            foreach ($url_associations as $key => $association) {
                if ($association['type'] == ClassMetadataInfo::ONE_TO_ONE && $association['orphanRemoval']) {
                    $url_field = $key;
                    break;
                }
            }
            
            if ($url_field == null) return;
            
            $url_item = $entity->get($url_field);
            $url_item->set('item_id', $entity->get('id'));
            array_push($this->toFlush, $url_item);
            
        }
    }
    
}
