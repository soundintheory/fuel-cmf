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
    protected $savedUrls = array();
    protected $moduleUrls = null;
    public static $disableProcessing = false;
    
    protected function init()
    {
        if ($this->moduleUrls === null) {
            $this->moduleUrls = array_flip(\Config::get('cmf.module_urls', array()));
        }
    }
    
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
        if (static::$disableProcessing) return;

        $this->init();
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $entity = $args->getEntity();

        $this->processNew($entity, $em, $uow);
    }
    
    public function postFlush(PostFlushEventArgs $args)
    {
        if (static::$disableProcessing) return;

        $this->init();
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
        if (static::$disableProcessing) return;

        $this->init();
        $this->toFlush = array();
        
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        
        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            $this->process($entity, $em, $uow);
        }
        
		foreach ($uow->getScheduledEntityUpdates() AS $entity) {
			$this->process($entity, $em, $uow);
		}

        // Process the aliases after the other stuff
        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            $metadata = $em->getClassMetadata(get_class($entity));

            if ($metadata->name == 'CMF\\Model\\URL') {
                $this->processUrlItem($entity, $em, $uow);
            }
        }
        
        foreach ($uow->getScheduledEntityUpdates() AS $entity) {
            $metadata = $em->getClassMetadata(get_class($entity));

            if ($metadata->name == 'CMF\\Model\\URL') {
                $this->processUrlItem($entity, $em, $uow);
            }
        }
    }

    protected function processUrlItem($entity, $em, $uow)
    {
        if ($entity->processed) return;

        // First copy any settings if this item is an alias
        $alias = $entity->alias;
        if ($alias instanceof \CMF\Model\URL) {

            $entity->set('url', $alias->url);
            $entity->set('type', $alias->type);
            $entity->set('prefix', $alias->prefix);
            $entity->set('slug', $alias->slug);
            $entity->set('item_id', $alias->item_id);

            $metadata = $em->getClassMetadata('CMF\\Model\\URL');
            $changeset = $uow->getEntityChangeSet($entity);
            
            if (!empty($changeset)) {
                $uow->recomputeSingleEntityChangeSet($metadata, $entity);
            } else {
                $uow->computeChangeSet($metadata, $entity);
            }

        }

        $entity->processed = true;

        // Now check whether this item has aliases attached to it
        $aliases = $entity->aliases;
        if (!is_null($aliases) && !empty($aliases) && count($aliases) > 0) {
            foreach ($aliases as $alias) {
                $this->processUrlItem($alias, $em, $uow);
            }
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
        if (!($entity instanceof \CMF\Model\Base)) return;
        
    	$metadata = $em->getClassMetadata(get_class($entity));
        $entity_class = $metadata->name;
        if ($entity_class::urlProcess() !== true) return;

        // Ignore URL entities themselves
        if ($metadata->name == 'CMF\\Model\\URL') return;

        $entity_namespace = trim(\CMF::slug(str_replace('\\', '/', \Inflector::get_namespace($entity_class))), '/');
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
            
            $settings = $entity->settings();
            $url_settings = isset($settings[$url_field]) ? $settings[$url_field] : array();
            $url_item = $entity->get($url_field);
            if ($new_url = is_null($url_item)) $url_item = new \CMF\Model\URL();

            // Don't run if this is an alias...
            $alias = $url_item->alias;
            if (!is_null($alias) && !empty($alias)) {
                return;
            }

            // Don't run if this is an external link...
            if ($url_item->isExternal()) {
                return;
            }
            
            $prefix = $this->getPrefix($entity);
            $slug = '';
            
            if (isset($url_settings['keep_updated']) && !$url_settings['keep_updated']) {
                $slug = \CMF::slug($url_item->slug);
            } else {
                $slug = $entity->urlSlug();
            }
            
            $url = $prefix.$slug;
            if ($url != '/') $url = rtrim($url, '/');
            $current_url = $url_item->url;
            $entity_id = $entity->id;
            $url_id = $url_item->get('id');
            
            // Check for duplicates, only if this is an already existing item
            if (!empty($entity_id) && !is_null($entity_id)) {
                
                // Set data from the entity if the prefix is null
                if (is_null($prefix)) {
                    $prefix = $this->getPrefix($entity);
                    $url = $prefix.$slug;
                }
                
                // Set data from the entity if the slug is null
                if (is_null($slug)) {
                    $slug = $entity->urlSlug();
                    $url = $prefix.$slug;
                }
                
                // Set it to the item's ID if empty
                if (is_null($slug)) {
                    $slug = $entity_id."";
                    $url = $prefix.$slug;
                }
                
                $slug_orig = $slug;
                $unique = $this->checkUnique($url, $entity_id, $url_id);
                $counter = 2;
                
                while (!$unique) {
                    $slug = $slug_orig.'-'.$counter;
                    $url = $prefix.$slug;
                    $unique = $this->checkUnique($url, $entity_id, $url_id);
                    $counter++;
                }
                
                // Add it to the list of saved URLs
                $this->savedUrls[$url] = $entity_id;
            }
            
            $url_item->set('item_id', $entity->get('id'));
            $url_item->set('prefix', $prefix);
            $url_item->set('slug', $slug);
            $url_item->set('url', $url);
            $url_item->set('type', $metadata->name);
            $entity->set($url_field, $url_item);
            $em->persist($url_item);

            // Skip this if the url hasn't changed
            if (!$new_url && $current_url == $url) return;
            
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
    
    protected function checkUnique($url, $item_id, $url_id = null)
    {
        if (isset($this->savedUrls[$url])) {
            if ($this->savedUrls[$url] === $item_id) {
                return true;
            } else {
                return false;
            }
        }
        
        if (\DB::query("SELECT url FROM urls WHERE url = '$url' AND item_id <> $item_id".(!empty($url_id) ? " AND id <> $url_id" : "")." AND alias_id IS NULL", \DB::SELECT)->execute()->count() > 0) {
            $this->savedUrls[$url] = $item_id;
            return false;
        }
        
        return true;
    }
    
    protected function processNew(&$entity, &$em, &$uow)
    {
        if (!($entity instanceof \CMF\Model\Base)) return;

        $metadata = $em->getClassMetadata(get_class($entity));
        $entity_class = $metadata->name;
        if ($entity_class::urlProcess() !== true) return;

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
            $url_id = $url_item->get('id');
            $entity_id = $entity->get('id');
            $prefix = $url_item->get('prefix');
            $slug = $url_item->get('slug');
            $url = $url_item->get('url');

            // Don't run if this is an alias...
            $alias = $url_item->alias;
            if (!is_null($alias) && !empty($alias)) {
                return;
            }

            // Don't run if this is an external link...
            if ($url_item->isExternal()) {
                return;
            }
            
            // Set data from the entity if the prefix is null
            if (is_null($prefix)) {
                $prefix = $this->getPrefix($entity);
                $url = $prefix.$slug;
            }
            
            // Set data from the entity if the slug is null
            if (is_null($slug)) {
                $slug = $entity->urlSlug();
                $url = $prefix.$slug;
            }
            
            // Set the slug to the item's ID if empty
            if (is_null($slug)) {
                $slug = $entity_id."";
                $url = rtrim($prefix.$slug, '/');
            }
            
            $slug_orig = $slug;
            $unique = $this->checkUnique($url, $entity_id, $url_id);
            $counter = 2;
            
            while (!$unique) {
                $slug = $slug_orig.'-'.$counter;
                $url = $prefix.$slug;
                $unique = $this->checkUnique($url, $entity_id, $url_id);
                $counter++;
            }
            
            $url_item->set('item_id', $entity_id);
            $url_item->set('slug', $slug);
            $url_item->set('url', $url);
            
            array_push($this->toFlush, $url_item);
            
        }
    }
    
    /**
     * Find the URL prefix for a given entity
     * 
     * @param  \CMF\Model\Base $entity
     * @return string
     */
    protected function getPrefix($entity)
    {
        $entity_class = get_class($entity);
        $metadata = $entity_class::metadata();
        $entity_class = $metadata->name;

        $entity_namespace = $entity_class::getModule();
        $module = \Module::exists($entity_namespace);
        $prefix = $entity->urlPrefix();
        
        if ($module !== false && $entity_namespace != '')
        {
            $module_prefix = \Arr::get($this->moduleUrls, $entity_namespace, $entity_namespace);
            if (strpos($prefix, '/'.$module_prefix) === 0) return $prefix;
            return "/$module_prefix".$prefix;
        }
        
        return $prefix;
    }
    
}
