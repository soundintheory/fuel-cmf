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

class SortableListener implements EventSubscriber
{
    protected $relocations;
    
    /**
     * Specifies the list of events to listen to
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'onFlush'
        );
    }
    
    public function onFlush(OnFlushEventArgs $args)
    {
        $this->relocations = array();
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        
        /*
        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            $this->process($entity, $em, $uow);
        }
        */
        
        foreach ($uow->getScheduledEntityUpdates() AS $entity) {
            if (!property_exists($entity, 'pos')) continue;
            $this->processUpdate($entity, $em, $uow);
        }
        
        $this->processRelocations($em, $uow);
        
    }
    
    protected function processUpdate($entity, $em, $uow)
    {
        $class = get_class($entity);
        
        $process_enabled = $class::sortProcess();
        if ($process_enabled !== true) return;
        
        if (!$class::sortable()) return;
        
        $changeset = $uow->getEntityChangeSet($entity);
        $changed = false;
        $group_by = is_callable($class.'::sortGroup') ? $class::sortGroup() : null;
        $has_group = !is_null($group_by) && property_exists($class, $group_by);
        $changed = ($has_group && array_key_exists($group_by, $changeset)) || array_key_exists('pos', $changeset);
        
        if (!$changed) return;
        
        $new_pos = intval($changeset['pos'][1]);
        $group_value = ($has_group) ? $entity->get($group_by) : '__ungrouped__';
        if ($has_group) $group_value = $entity->sortGroupId($group_value);
        
        $class_relocations = isset($this->relocations[$class]) ? $this->relocations[$class] : array( '__settings__' => array( 'has_group' => $has_group, 'group_by' => $group_by ), 'groups' => array() );
        $group_relocations = isset($class_relocations['groups'][$group_value]) ? $class_relocations['groups'][$group_value] : array( 'value' => $group_value, 'items' => array() );
        
        // Make sure our new position doesn't clash with any other stored ones
        while (isset($group_relocations[$new_pos])) {
            $new_pos++;
        }
        
        if ($new_pos !== $entity->pos) {
            $metadata = $em->getClassMetadata($class);
            $entity->set('pos', $new_pos);
            $uow->recomputeSingleEntityChangeSet($metadata, $entity);
        }
        
        $group_relocations['items'][$new_pos] = $entity->id;
        $class_relocations['groups'][$group_value] = $group_relocations;
        $this->relocations[$class] = $class_relocations;
        
    }
    
    protected function processRelocations($em, $uow)
    {
        // Set all the positions!
        
        foreach ($this->relocations as $class => $class_relocations) {
            
            $metadata = $em->getClassMetadata($class);
            $group_by = $class_relocations['__settings__']['group_by'];
            $has_group = $class_relocations['__settings__']['has_group'];
            $is_assocation = $metadata->hasAssociation($group_by);
            
            if ($has_group) {
                
                $qb = $class::select('partial item.{id,pos}');
                
                if ($is_assocation) {
                    $qb->leftJoin('item.'.$group_by, $group_by)->addSelect($group_by);
                }
                
                // TODO: Should maybe filter to only the necessary groups?
                // $values = array();
                // foreach ($class_relocations['groups'] as $group_value => $group_relocations) {
                //     array_push($values, $group_relocations['value']);
                // }
                // $this->addQueryFilters($qb, $group_by, $values);
                
                $items = $qb->orderBy('item.pos', 'ASC')->addOrderBy('item.id', 'ASC')->getQuery()->getResult();
                $group_relocations = $class_relocations['groups'];
                $ids = array();
                foreach ($group_relocations as $group) {
                    $ids = array_merge($ids, array_values($group['items']));
                }
                
                foreach ($items as $item) {
                    
                    if (in_array($item->id, $ids)) continue;
                    
                    $identifier = $item->sortGroupId($item->get($group_by));
                    
                    if (!isset($group_relocations[$identifier])) continue;
                    
                    if (!isset($group_relocations[$identifier]['i'])) {
                        $group_relocations[$identifier]['i'] = 0;
                        $group_relocations[$identifier]['delta'] = 0;
                        $group_relocations[$identifier]['num_updated'] = 0;
                    }
                    
                    $relocations = $group_relocations[$identifier]['items'];
                    $i = $group_relocations[$identifier]['i'];
                    $delta = $group_relocations[$identifier]['delta'];
                    $num_updated = $group_relocations[$identifier]['num_updated'];
                    $pos = $i + $delta;
                    
                    while (isset($relocations[$pos])) {
                        $delta++;
                        $pos = $i + $delta;
                    }
                    
                    if ($pos !== $item->pos) {
                        // Execute a DQL statement to update this item
                        $dql = "UPDATE $class item SET item.pos = $pos WHERE item.id = ".$item->id;
                        $num_updated += $em->createQuery($dql)->execute();
                    }
                    
                    $i++;
                    $group_relocations[$identifier]['i'] = $i;
                    $group_relocations[$identifier]['delta'] = $delta;
                    $group_relocations[$identifier]['num_updated'] = $num_updated;
                    
                }
                
            } else {
                
                $qb = $class::select('item.id, item.pos');
                $items = $qb->orderBy('item.pos', 'ASC')->addOrderBy('item.id', 'ASC')->getQuery()->getArrayResult();
                $relocations = $class_relocations['groups']['__ungrouped__']['items'];
                $delta = 0;
                $i = 0;
                $ids = array_values($relocations);
                $num_updated = 0;
                
                foreach ($items as $item) {
                    
                    if (in_array($item['id'], $ids)) continue;
                    
                    $pos = $i + $delta;
                    
                    while (isset($relocations[$pos])) {
                        $delta++;
                        $pos = $i + $delta;
                    }
                    
                    if ($pos !== $item['pos']) {
                        // Execute a DQL statement to update this item
                        $dql = "UPDATE $class item SET item.pos = $pos WHERE item.id = ".$item['id'];
                        $num_updated += $em->createQuery($dql)->execute();
                    }
                    
                    $i++;
                    
                }
                
            }
            
        }
        
    }
    
    /**
     * Given a value and a QueryBuilder, 
     * @param [type] $qb    [description]
     * @param [type] $value [description]
     */
    protected function addQueryFilters($qb, $field_name, $values)
    {
        
    }
    
}
