<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert,
	CMF\Model\Base;

/**
 * @ORM\Entity
 * @ORM\Table(name="logs")
 **/
class Log extends \Doctrine\Fuel\Model
{
    
    public function display()
    {
        return 'LOG DISPLAY';
    }
    
    /**
     * Find and format a bunch of logs between two dates
     * @return array The log data, ready for conversion into CSV or something similar
     */
    public static function report($start_date = null, $end_date = null, $filters = array(), $group_by = null, $callback = null)
    {
        $called_class = get_called_class();
        $qb = $called_class::findBy($filters);
        $has_filters = count($filters) > 0;
        
        // Filter from start date
        if ($start_date !== null) {
            
            if (is_string($start_date)) {
                $start_date = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $start_date)));
                $start_date = \DateTime::createFromFormat('Y-m-d H:i:s', $start_date);
            }
            
            if ($has_filters) {
                $qb->andWhere("item.date >= ?1")->setParameter(1, $start_date);
            } else {
                $qb->where("item.date >= ?1")->setParameter(1, $start_date);
            }
            
            $has_filters = true;
        }
        
        // Filter to end date
        if ($end_date !== null) {
            
            if (is_string($end_date)) {
                $end_date = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $end_date)));
                $end_date = \DateTime::createFromFormat('Y-m-d H:i:s', $end_date);
            }
            
            if ($has_filters) {
                $qb->andWhere("item.date < ?2")->setParameter(2, $end_date);
            } else {
                $qb->where("item.date < ?2")->setParameter(2, $end_date);
            }
            
        }
        
        // Get the log entries
        $entries = $qb->orderBy('item.date', 'ASC')->getQuery()->getArrayResult();
        $types = array_values(array_unique( array_merge( array_unique(\Arr::pluck($entries, 'item_type')), array_unique(\Arr::pluck($entries, 'user_type')) ) ));
        $actions = array_values(array_unique(\Arr::pluck($entries, 'action')));
        $types_ids = array();
        $output = array();
        
        // Normalize the group_by parameter
        if ($group_by == 'user') {
            $group_by = 'user_id';
        } else if ($group_by == 'item') {
            $group_by = 'item_id';
        }
        
        // Find the ids of the related users and items
        foreach ($entries as $entry) {
            
            // The user
            if ($entry['has_user'] = isset($entry['user_type']) && isset($entry['user_id'])) {
                
                $type = $entry['user_type'];
                $ids = isset($types_ids[$type]) ? $types_ids[$type] : array();
                
                if (!in_array($entry['user_id'], $ids)) {
                    $ids[] = $entry['user_id'];
                    $types_ids[$type] = $ids;
                }
                
            }
            
            // The item
            if ($entry['has_item'] = isset($entry['item_type']) && isset($entry['item_id'])) {
                
                $type = $entry['item_type'];
                $ids = isset($types_ids[$type]) ? $types_ids[$type] : array();
                
                if (!in_array($entry['item_id'], $ids)) {
                    $ids[] = $entry['item_id'];
                    $types_ids[$type] = $ids;
                }
                
            }
            
            // Add to the stats for this group if we're grouping
            if ($group_by !== null) {
                
                $group_value = $entry['item_type'].'_'.$entry[$group_by];
                
                if (!isset($output[$group_value])) {
                    $group = $entry;
                    foreach ($actions as $action) {
                        $group[$action.'_count'] = 0;
                    }
                } else {
                    $group = $output[$group_value];
                }
                
                $group[$entry['action'].'_count'] += 1;
                $group['last_'.$action] = $entry['date']->format('d/m/Y H:i:s');
                $output[$group_value] = $group;
                
            } else {
                
                $output[] = $entry;
                
            }
            
        }
        
        // Now construct queries for each of the types, so we can grab them all in one swipe
        foreach ($types as $type) {
            
            if (!isset($types_ids[$type]) || count($types_ids[$type]) === 0) continue;
            $ids = $types_ids[$type];
            $types_ids[$type] = $type::select('item', 'item', 'item.id')->where('item.id IN(?1)')->setParameter(1, $ids)->getQuery()->getResult();
            
        }
        
        // Put the items into the log entries
        foreach ($output as &$entry) {
            
            // The user
            if ($entry['has_user']) {
                $user_type = $entry['user_type'];
                $entry['user'] = \Arr::get($types_ids, $user_type.'.'.$entry['user_id'], null);
                $entry['user_type_label'] = $user_type::singular();
            }
            
            // The item
            if ($entry['has_item']) {
                $item_type = $entry['item_type'];
                $entry['item'] = \Arr::get($types_ids, $item_type.'.'.$entry['item_id'], null);
                $entry['item_type_label'] = $item_type::singular();
            }
            
        }
        
        if ($callback !== null) return array_values(array_filter($output, $callback));
        return $output;
        
    }
    
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     **/
    protected $id;
    
    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     **/
    protected $date;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     **/
    protected $user_id;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $user_type;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     **/
    protected $item_id;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $item_type;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $item_label;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $action;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     **/
    protected $message;
	
}