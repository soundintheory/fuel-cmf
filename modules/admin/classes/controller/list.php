<?php

namespace Admin;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

class Controller_List extends Controller_Base {
	
	/**
	 * Renders the item list (the table view)
	 * @param string $table_name
	 * @return void
	 */
	public function action_index($table_name)
	{
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404("Can't find that type!");
		
		// Redirect straight to the edit page if the item is static
		if ($class_name::_static() === true) {
	    	$static_item = $class_name::select('item')->setMaxResults(1)->getQuery()->getResult();
	    	if (count($static_item) > 0) {
	    		$static_item = $static_item[0];
	    		\Response::redirect(\Uri::base(false)."admin/$table_name/".$static_item->id."/edit", 'location');
	    	} else {
	    		\Response::redirect(\Uri::base(false)."admin/$table_name/create", 'location');
	    	}
	    }
		
		// Here's where we catch special types, eg tree nodes. These can now be rendered using a special template
		if (is_subclass_of($class_name, 'CMF\\Model\\Node')) {
			return $this->treeView($class_name);
		}
		
		// Get the data for the list
		$metadata = $class_name::metadata();
		$fields = \Admin::getFieldSettings($class_name);
		$list_fields = $class_name::listFields();
		if (empty($list_fields)) $list_fields = array_keys($fields);
		$columns = array();
		
		// Create static items
		\Admin::createStaticInstances($metadata);
		
		// See if the list order has been set in the session. If not, try and use the model's default order
		$order = \Session::get($metadata->table['name'].".list.order", $class_name::order());
		
		// Start the query builder...
		$qb = $class_name::select('item');
		
		// Add any joins to the query builder (prevents the query buildup that comes from accessing lazily loaded associations)
		foreach ($list_fields as $field) {
			
			if ($field == 'id') continue;
			
			// This could be a method on the model
			if (!isset($fields[$field])) {
				
				if (method_exists($class_name, $field)) {
					
					$columns[] = array(
						'type' => 'method',
						'name' => $field,
						'heading' => \Inflector::humanize(\Inflector::underscore($field))
					);
					
				}
				
				continue;
			}
			
			if ($metadata->isSingleValuedAssociation($field)) {
				$qb->leftJoin('item.'.$field, $field)->addSelect($field);
			} else if ($metadata->isCollectionValuedAssociation($field)) {
				$qb->leftJoin('item.'.$field, $field)->addSelect($field);
			}
			
			// Get the field class and type
			$field_class = $fields[$field]['field'];
			$field_type = $field_class::type();
			$column = array( 'name' => $field, 'type' => $field_type );
			
			if (isset($order[$field])) {
				
                $dir = strtolower($order[$field]);
                $rev = ($dir == 'asc') ? 'desc' : 'asc';
                $arrows = html_tag('span', array( 'class' => 'arrow-down' ), '&#x25BC;').html_tag('span', array( 'class' => 'arrow-up' ), '&#x25B2;');
                $verbose = ($dir == 'asc') ? 'descending' : 'ascending';
                $column['heading'] = html_tag('a', array( 'href' => "/admin/$table_name/list/order?$field=$rev", 'class' => 'sort-link '.$dir, 'title' => 'Sort by '.$field.' '.$verbose ), $fields[$field]['title'].' '.$arrows);
                
            } else {
            	
                $column['heading'] = html_tag('a', array( 'href' => "/admin/$table_name/list/order?$field=asc", 'class' => 'sort-link', 'title' => 'Sort by '.$field.' ascending' ), $fields[$field]['title']);
                
            }
            
            $columns[] = $column;
			
		}
		
		// Add the ordering to the query builder
		foreach ($order as $field => $direction)
	    {
	        if ($metadata->hasAssociation($field)) {
	            $assoc_class = $metadata->getAssociationTargetClass($field);
	            $assoc_field = property_exists($assoc_class, 'name') ? 'name' : (property_exists($assoc_class, 'title') ? 'title' : 'id');
	            $qb->addOrderBy("$field.$assoc_field", $direction);
	        } else {
	            $qb->addOrderBy("item.$field", $direction);
	        }
	    }
		
		// TODO: Add filters for paging, list filters, sorting etc.
		
		// Get the results and prepare data for the template
		\Admin::$current_class = $this->current_class = $class_name;
		$this->plural = $class_name::plural();
		$this->singular = $class_name::singular();
		$this->icon = $class_name::icon();
		$this->rows = $qb->getQuery()->getResult();
		$this->columns = $columns;
		$this->fields = $fields;
		$this->table_name = $metadata->table['name'];
		$this->template = 'admin/item/list.twig';
		$this->superlock = $class_name::superlock();
		
		// Add the stuff for JS
		$this->js['table_name'] = $metadata->table['name'];
		$this->js['plural'] = $this->plural;
		$this->js['singular'] = $this->singular;
		
		
		
	}
	
	public function action_updatetree($table_name, $id = null)
	{
	    $position = \Input::post('position');
	    $target = \Input::post('target');
	    
	    if (is_null($id) || is_null($target) || is_null($position)) {
	        $this->template = 'admin/item/404.twig';
	        $this->status = 404;
	        $this->data = array(
	           'msg' => "Not enough data to update the tree!"
	        );
	        return;
	    }
	    
	    $class_name = \Admin::getClassForTable($table_name);
	    $metadata = $class_name::metadata();
	    $em = \DoctrineFuel::manager();
	    
	    // Load up the entity with the Id
	    $entity = $class_name::find($id);
	    
	    // Return 404 if entity has't been loaded from the id
	    if (is_null($entity)) {
	        
	        $this->template = 'admin/item/404.twig';
	        $this->status = 404;
	        $this->data = array(
	           'msg' => "That ".$admin_config['singular']." Doesn't Exist!"
	        );
	        return;
	        
	    } else if (!$entity instanceof \CMF\Model\Node) {
	        
	        $this->template = 'admin/item/404.twig';
	        $this->status = 404;
	        $this->data = array(
	           'msg' => "That item is not part of a tree!"
	        );
	        return;
	        
	    }
	    
	    if (strpos(strval($target), "-root") !== false) {
	        $target = null;
	    } else {
	        $target = $class_name::find(intval($target));
	    }
	    
	    if (!is_null($target)) {
	        
	        $repository = $em->getRepository($class_name);
	        
	        switch ($position) {
                case "inside":
                    $repository->persistAsFirstChildOf($entity, $target);
                break;
                case "before":
                    $repository->persistAsPrevSiblingOf($entity, $target);
                break;
                case "after":
                    $repository->persistAsNextSiblingOf($entity, $target);
                break;
            }
            
	        $em->flush();
	        
	    }
	    
	    return \Response::forge(json_encode(array( "result" => "success", "target" => $target->display(), "position" => $position )), $this->status, $this->headers);
	    
	}
	
	public function action_order($table_name)
	{
	    \Session::set("$table_name.list.order", \Input::get());
	    \Response::redirect(\Input::referrer());
	}
	
	/**
	 * Gets called from action_index() when a model is found to extend CMF\Model|Node
	 * @param  string $class_name
	 * @return void
	 */
	public function treeView($class_name)
	{
		\Admin::$current_class = $this->current_class = $class_name;
		$metadata = $class_name::metadata();
		
		// Create static items
		\Admin::createStaticInstances($metadata);
		
		// Add some context for the template
		$this->plural = $class_name::plural();
		$this->singular = $class_name::singular();
		$this->icon = $class_name::icon();
		
		$classes = array(
			$class_name => array(
				'plural' => $this->plural,
				'singular' => $this->singular,
				'icon' => $this->icon,
				'table_name' => $metadata->table['name']
			)
		);
		
		foreach ($metadata->subClasses as $sub_class) {
			
			$subclass_metadata = $sub_class::metadata();
			
			$classes[$sub_class] = array(
				'static' => $sub_class::_static(),
				'superlock' => $sub_class::superlock(),
				'plural' => $sub_class::plural(),
				'singular' => $sub_class::singular(),
				'icon' => $sub_class::icon(),
				'table_name' => $subclass_metadata->table['name']
			);
			
		}
		
		$root_node = $class_name::getRootNode(true);
		$tree = $this->processTreeNodes(\DoctrineFuel::manager()->getRepository($class_name)->childrenHierarchy($root_node), $metadata);
		
		// Add more context for the template
		$this->table_name = $metadata->table['name'];
		$this->template = 'admin/item/tree.twig';
		$this->superlock = $class_name::superlock();
		$this->num_nodes = count($tree);
		
		// Add the stuff for JS
		$this->js['tree'] = $tree;
		$this->js['classes'] = $classes;
		$this->js['table_name'] = $metadata->table['name'];
		$this->js['plural'] = $this->plural;
		$this->js['singular'] = $this->singular;
	}
	
	/**
	 * Recursive madness
	 */
	protected function processTreeNodes($nodes, &$metadata)
	{
		$num = count($nodes);
		for ($i=0; $i < $num; $i++) { 
			
			$node = $nodes[$i];
			$disc_name = $metadata->discriminatorColumn['name'];
			$disc_val = isset($node[$disc_name]) ? $node[$disc_name] : '';
			$node['label'] = $node['title'];
			$node['class'] = isset($metadata->discriminatorMap[$disc_val]) ? $metadata->discriminatorMap[$disc_val] : $metadata->name;
			
			if (isset($node['__children'])) {
				$children = $this->processTreeNodes($node['__children'], $metadata);
				unset($node['__children']);
				$node['children'] = $children;
			}
			
			$nodes[$i] = $node;
			
		}
		return $nodes;
	}
	
}