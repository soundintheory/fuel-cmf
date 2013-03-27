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
		if ($class_name === false) {
			return $this->customPageOr404(array($table_name), "Can't find that type!");
		}
		
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
	    
	    if (!\CMF\Auth::can('view', $class_name)) {
	    	return $this->show403("You're not allowed to view ".strtolower($class_name::plural())."!");
	    }
		
		// Here's where we catch special types, eg tree nodes. These can now be rendered using a special template
		if (is_subclass_of($class_name, 'CMF\\Model\\Node')) {
			return $this->treeView($class_name);
		}
		
		// Get permissions
		$can_create = \CMF\Auth::can('create', $class_name);
		$can_edit = \CMF\Auth::can('edit', $class_name);
		$can_delete = \CMF\Auth::can('delete', $class_name);
		$can_manage = \CMF\Auth::can(array('view', 'edit'), 'CMF\\Model\\Permission');
		
		// Get the data for the list
		$metadata = $class_name::metadata();
		$sortable = $class_name::sortable();
		$sort_group = is_callable($class_name.'::sortGroup') ? $class_name::sortGroup() : null;
		
		$excluded_ids = array();
		$fields = \Admin::getFieldSettings($class_name);
		$list_fields = $class_name::listFields();
		if (empty($list_fields)) $list_fields = array_keys($fields);
		$columns = array();
		$joins = array();
		$methods = array();
		
		// Create static items
		\Admin::createStaticInstances($metadata);
		
		// See if the list order has been set in the session. If not, try and use the model's default order
		$order = \Session::get($metadata->table['name'].".list.order", $class_name::order());
		
		// Start the query builder...
		$qb = $class_name::select('item', 'item', 'item.id');
		
		// Add any joins to the query builder (prevents the query buildup that comes from accessing lazily loaded associations)
		foreach ($list_fields as $field) {
			
			if ($field == 'id') continue;
			
			// If there is a dot notation try and locate the field
			if (strpos($field, ".") !== false) {
				$parts = explode(".", $field);
				$field_name = array_shift($parts);
				
				// If this dot notation refers to an association, we need to find the field data for the target type!
				if ($metadata->isSingleValuedAssociation($field_name)) {
					$target_class = $metadata->getAssociationTargetClass($field_name);
					$target_fields = \Admin::getFieldSettings($target_class);
					
					foreach ($target_fields as $target_field => $target_field_settings) {
						$fields[$field_name.'.'.$target_field] = $target_field_settings;
					}
				}
				
			} else {
				$field_name = $field;
			}
			
			// This could be a method on the model
			if (!isset($fields[$field])) {
				
				if (method_exists($class_name, $field_name)) {
					
					$column = array(
						'type' => 'method',
						'name' => $field_name,
						'heading' => \Inflector::humanize(\Inflector::underscore($field_name))
					);
					
					/*
					if (isset($order[$field_name])) {
		                $dir = strtolower($order[$field_name]);
		                $rev = ($dir == 'asc') ? 'desc' : 'asc';
		                $arrows = html_tag('span', array( 'class' => 'arrow-down' ), '&#x25BC;').html_tag('span', array( 'class' => 'arrow-up' ), '&#x25B2;');
		                $verbose = ($dir == 'asc') ? 'descending' : 'ascending';
		                $column['heading'] = html_tag('a', array( 'href' => "/admin/$table_name/list/order?$field_name=$rev", 'class' => 'sort-link '.$dir, 'title' => 'Sort by '.$column['heading'].' '.$verbose ), $column['heading'].' '.$arrows);
		            } else {
		                $column['heading'] = html_tag('a', array( 'href' => "/admin/$table_name/list/order?$field_name=asc", 'class' => 'sort-link', 'title' => 'Sort by '.$column['heading'].' ascending' ), $column['heading']);
		            }
		            */
		            
		            $methods[] = $field_name;
		            $columns[] = $column; 
		            				
				}
				
				continue;
			}
			
			if ($metadata->isSingleValuedAssociation($field_name) && !in_array($field_name, $joins)) {
				$qb->leftJoin('item.'.$field_name, $field_name)->addSelect($field_name);
				$joins[] = $field_name;
			} else if ($metadata->isCollectionValuedAssociation($field_name) && !in_array($field_name, $joins)) {
				$qb->leftJoin('item.'.$field_name, $field_name)->addSelect($field_name);
				$joins[] = $field_name;
			}
			
			// Get the field class and type
			$field_class = $fields[$field]['field'];
			$field_type = $field_class::type();
			$column = array( 'name' => $field, 'type' => $field_type );
			
			if (!$sortable) {
				
				$field_colons = str_replace('.', ':', $field);
				
				if (isset($order[$field_colons])) {
	                $dir = strtolower($order[$field_colons]);
	                $rev = ($dir == 'asc') ? 'desc' : 'asc';
	                $arrows = html_tag('span', array( 'class' => 'arrow-down' ), '&#x25BC;').html_tag('span', array( 'class' => 'arrow-up' ), '&#x25B2;');
	                $verbose = ($dir == 'asc') ? 'descending' : 'ascending';
	                $column['heading'] = html_tag('a', array( 'href' => "/admin/$table_name/list/order?$field_colons=$rev", 'class' => 'sort-link '.$dir, 'title' => 'Sort by '.$fields[$field]['title'].' '.$verbose ), $fields[$field]['title'].' '.$arrows);
	            } else {
	                $column['heading'] = html_tag('a', array( 'href' => "/admin/$table_name/list/order?$field_colons=asc", 'class' => 'sort-link', 'title' => 'Sort by '.$fields[$field]['title'].' ascending' ), $fields[$field]['title']);
	            }
	            
	        } else {
	        	
	        	$column['heading'] = $fields[$field]['title'];
	        	
	        }
            
            $columns[] = $column;
			
		}
		
		// Make the list drag and drop, if editing is possible
		if ($sortable && $can_edit) {
			array_unshift($columns, array( 'name' => '', 'type' => 'handle', 'heading' => '' ));
		}
		
		// Add the sortable ordering
		if ($sortable) {
			
			$has_group = !is_null($sort_group) && property_exists($class_name, $sort_group);
			
			if ($has_group) {
				
				if (!in_array($sort_group, $joins)) {
					$qb->leftJoin('item.'.$sort_group, $sort_group)->addSelect($sort_group);
					$joins[] = $sort_group;
				}
				
				if ($metadata->hasAssociation($sort_group)) {
				    $assoc_class = $metadata->getAssociationTargetClass($sort_group);
				    $assoc_field = property_exists($assoc_class, 'name') ? 'name' : (property_exists($assoc_class, 'title') ? 'title' : 'id');
				    $qb->addOrderBy("$sort_group.$assoc_field", 'ASC');
				} else {
				    $qb->addOrderBy("item.$sort_group", 'ASC');
				}
				
			}
			
			$qb->addOrderBy('item.pos', 'ASC');
			
		} else {
			
			// Add the ordering to the query builder
			foreach ($order as $field => $direction)
		    {
		    	if (in_array($field, $methods)) continue;
		    	
		    	$field_name = $field;
		    	$assoc_field = 'title';
		    	
		    	// If there is a dot notation (or colon) try and locate the field
		    	if (strpos($field, ".") !== false) {
		    		$parts = explode(".", $field);
		    		$field_name = array_shift($parts);
		    		$assoc_field = array_shift($parts);
		    	} else if (strpos($field, ":") !== false) {
		    		$parts = explode(":", $field);
		    		$field_name = array_shift($parts);
		    		$assoc_field = array_shift($parts);
		    	}
		    	
		        if ($metadata->hasAssociation($field_name)) {
		            $assoc_class = $metadata->getAssociationTargetClass($field_name);
		            if (!property_exists($assoc_class, $assoc_field)) $assoc_field = property_exists($assoc_class, 'title') ? 'title' : 'id';
		            $qb->addOrderBy("$field_name.$assoc_field", $direction);
		        } else {
		            $qb->addOrderBy("item.$field_name", $direction);
		        }
		    }
			
		}
		
		// Get the results and prepare data for the template
		$rows = $qb->getQuery()->getResult();
		$ids = array_keys($rows);
		
	    // Another pass at the ordering for methods
	    /*
		foreach ($order as $field => $direction) {
			
	    	if (!in_array($field, $methods)) continue;
	    	
	    	if ($direction == 'asc') {
	    		uasort($rows, function($a, $b) use($field) {
	    		    return strcmp(strtolower($a->$field()), strtolower($b->$field()));
	    		});
	    	} else {
	    		uasort($rows, function($a, $b) use($field) {
	    		    return strcmp(strtolower($b->$field()), strtolower($a->$field()));
	    		});
	    	}
	    	
	    }
	    */
		
		// Item-specific permissions
		$user = \CMF\Auth::current_user();
		$item_permissions = array();
		
		if (!$user->super_user) {
			
			$permissions = \CMF\Model\Permission::select('item.id, item.action, item.resource, item.item_id')
		    ->leftJoin('item.roles', 'roles')
		    ->where("item.resource = '$class_name'")
		    ->andWhere("item.item_id IN(?1)")
		    ->andWhere("roles IN (?2)")
		    ->setParameter(1, $ids)
		    ->setParameter(2, $user->roles->toArray())
		    ->getQuery()->getArrayResult();
		    
		    foreach ($permissions as $permission) {
		    	$item_actions = isset($item_permissions[$permission['item_id']]) ? $item_permissions[$permission['item_id']] : array();
		    	$item_actions[] = $permission['action'];
		    	$item_permissions[$permission['item_id']] = $item_actions;
		    }
		    
		    foreach ($item_permissions as $item_id => $item_actions) {
		    	if (in_array('none', $item_actions) || (count($item_actions) > 0 && !in_array('view', $item_actions))) {
		    		$excluded_ids[] = $item_id;
		    	}
		    }
			
		}
		
		\Admin::$current_class = $this->current_class = $class_name;
		$this->plural = $class_name::plural();
		$this->excluded_ids = $excluded_ids;
		$this->item_permissions = $item_permissions;
		$this->singular = $class_name::singular();
		$this->icon = $class_name::icon();
		$this->rows = $rows;
		$this->columns = $columns;
		$this->fields = $fields;
		$this->table_name = $metadata->table['name'];
		$this->template = 'admin/item/list.twig';
		$this->superlock = $class_name::superlock();
		$this->sortable = $sortable && $can_edit;
		$this->sort_group = $sort_group;
		
		// Permissions
		$this->can_create = $can_create && $can_edit;
		$this->can_edit = $can_edit;
		$this->can_delete = $can_delete;
		$this->can_manage = $can_manage;
		
		// Add the stuff for JS
		$this->js['table_name'] = $metadata->table['name'];
		$this->js['plural'] = $this->plural;
		$this->js['singular'] = $this->singular;
		
	}
	
	public function action_permissions($table_name, $role_id=null)
	{
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404("Can't find that type!");
		
		if ($role_id == null) {
			$first_role = \CMF\Model\Role::select('item.id')->setMaxResults(1)->getQuery()->getArrayResult();
			if (count($first_role) > 0) {
				$role_id = intval($first_role[0]['id']);
			} else {
				return $this->show404("There are no roles to manage!");
			}
		}
		
		$role_check = intval(\CMF\Model\Role::select("COUNT(item.id)")->where("item.id = $role_id")->getQuery()->getSingleScalarResult());
		if ($role_check === 0) return $this->show404("Can't find that role!");
		
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
	    
	    // Permissions
	    if (!\CMF\Auth::can(array('view', 'edit'), 'CMF\\Model\\Permission')) {
	    	return $this->show403("You're not allowed to manage permissions!");
	    } elseif (!\CMF\Auth::can('view', $class_name)) {
	    	return $this->show403("You're not allowed to manage permissions for ".strtolower($class_name::plural())."!");
	    }
	    
	    // Get the values for the list
	    $qb = $class_name::select('item', 'item', 'item.id');
	    if (is_subclass_of($class_name, 'CMF\\Model\\Node')) {
	    	$rows = $qb->where('item.is_root != true')->orderBy('item.root, item.lft', 'ASC')->getQuery()->getResult();
			$this->is_tree = true;
		} else {
		    $rows = $qb->getQuery()->getResult();
		    uasort($rows, function($a, $b) {
				return strcmp(strtolower($a->display()), strtolower($b->display()));
			});
		}
	    
	    // Get the permissions associated with this role and these items
	    $ids = array_keys($rows);
	    $permissions = \CMF\Model\Permission::select('item')
	    ->leftJoin('item.roles', 'roles')
	    ->where("item.resource = '$class_name'")
	    ->andWhere("item.item_id IN(?1)")
	    ->andWhere("roles.id = $role_id")
	    ->setParameter(1, $ids)
	    ->getQuery()->getArrayResult();
	    
	    // Transform the permissions into a form the template can understand
	    $values = array();
	    foreach ($permissions as $val) {
	        $item_id = $val['item_id'];
	        $action = $val['action'];
	        $actions = isset($values[$item_id]) ? $values[$item_id] : array();
	        if (!in_array($action, $actions)) $actions[] = $action;
	        $values[$item_id] = $actions;
	    }
	    
	    // Get the roles for the menu
	    $roles = \CMF\Model\Role::select('item', 'item', 'item.id')->getQuery()->getArrayResult();
	    
	    // Other data for the list
	    $all_actions = \CMF\Auth::all_actions();
	    $this->actions = array_filter(array_merge($all_actions, $class_name::actions()), function($var) {
	    	return $var != 'create';
	    });
	    
		$this->icon = $class_name::icon();
		$this->rows = $rows;
		$this->values = $values;
		$this->plural = $class_name::plural();
		$this->singular = $class_name::singular();
		$this->template = 'admin/item/list-permissions.twig';
		$this->roles = $roles;
		$this->role_id = $role_id;
		$this->role_name = isset($roles[$role_id]) ? $roles[$role_id]['name'] : '';
		$this->table_name = $table_name;
		
		// Add the stuff for JS
		$this->js['table_name'] = $table_name;
		$this->js['role_id'] = $role_id;
	    
	}
	
	public function action_save_permissions($table_name, $role_id)
	{
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404("Can't find that type!");
		
		$post = \Input::post();
		$ids = array_keys($post);
		
		$role = \CMF\Model\Role::select('item')->where('item.id = '.$role_id)->getQuery()->getResult();
		if (count($role) === 0) {
			return $this->show404("Can't find that role!");
		} else {
			$role = $role[0];
		}
		
		$permissions = \CMF\Model\Permission::select('item')
	    ->leftJoin('item.roles', 'roles')
	    ->where("item.resource = '$class_name'")
	    ->andWhere("item.item_id IN(?1)")
	    ->andWhere("roles.id = $role_id")
	    ->setParameter(1, $ids)
	    ->getQuery()->getResult();
	    
	    $em = \DoctrineFuel::manager();
	    
	    foreach ($permissions as $permission) {
	    	$actions = isset($post[$permission->item_id]) ? $post[$permission->item_id] : array();
	    	if ((array_key_exists('all', $actions) && intval($actions['all']) === 1)) {
	    		if ($permission->action != 'none') $em->remove($permission);
	    		$actions = array( 'all' => 1 );
	    	} elseif ((!array_key_exists($permission->action, $actions) || intval($actions[$permission->action]) === 0)) {
	    		if ($permission->action != 'none') $em->remove($permission);
	    	}
	    	$post[$permission->item_id] = $actions;
	    }
	    
	    foreach ($post as $item_id => $actions) {
	    	
	    	$passed = 0;
	    	foreach ($actions as $action => $action_value) {
	    		if ($action != 'all' && intval($action_value) === 1) {
	    			$permission = \CMF\Auth::get_permission($action, $class_name, $item_id);
	    			$role->add('permissions', $permission);
	    			$passed++;
	    		} elseif ($action == 'all' && intval($action_value) === 1) {
	    			$passed++;
	    		}
	    	}
	    	
	    	$none_permission = \CMF\Auth::get_permission('none', $class_name, $item_id);
	    	if ($passed === 0) {
	    		$role->add('permissions', $none_permission);
	    	} else {
	    		$em->remove($none_permission);
	    	}
	    	
	    }
	    
	    $result = array( 'success' => true );
	    
	    try {
	    	$em->persist($role);
	    	$em->flush();
	    } catch (\Exception $e) {
	    	$result['success'] = false;
	    }
	    
	    return \Response::forge(json_encode($result), $this->status, $this->headers);
	    
	}
	
	public function action_saveall($table_name)
	{
		$class_name = \Admin::getClassForTable($table_name);
		$plural = $class_name::plural();
		$class_name::saveAll();
		
		$default_redirect = \Uri::base(false)."admin/$table_name";
		\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-success' ), 'msg' => "All ".strtolower($plural)." were saved" ));
	    \Response::redirect(\Input::referrer($default_redirect), 'location');
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
		
		// Get permissions
		$can_create = \CMF\Auth::can('create', $class_name);
		$can_edit = \CMF\Auth::can('edit', $class_name);
		$can_delete = \CMF\Auth::can('delete', $class_name);
		$can_manage = \CMF\Auth::can(array('view', 'edit'), 'CMF\\Model\\Permission');
		
		$classes = array();
		
		if ($class_name::superclass() === false) {
			$classes[$class_name] = array(
				'plural' => $this->plural,
				'singular' => $this->singular,
				'icon' => $this->icon,
				'table_name' => $metadata->table['name'],
				'can_create' => $can_create && $can_edit,
				'can_edit' => $can_edit,
				'can_delete' => $can_delete
			);
		}
		
		foreach ($metadata->subClasses as $sub_class) {
			
			$subclass_metadata = $sub_class::metadata();
			
			$classes[$sub_class] = array(
				'static' => $sub_class::_static(),
				'superlock' => $sub_class::superlock(),
				'plural' => $sub_class::plural(),
				'singular' => $sub_class::singular(),
				'icon' => $sub_class::icon(),
				'table_name' => $subclass_metadata->table['name'],
				'can_create' => \CMF\Auth::can('create', $sub_class),
				'can_edit' => \CMF\Auth::can('edit', $sub_class),
				'can_delete' => \CMF\Auth::can('delete', $sub_class),
			);
			
		}
		
		// Item-specific permissions
		$user = \CMF\Auth::current_user();
		$item_permissions = array();
		$ids = array();
		$excluded_ids = array();
		
		$root_node = $class_name::getRootNode(true);
		$tree = $this->processTreeNodes(\DoctrineFuel::manager()->getRepository($class_name)->childrenHierarchy($root_node), $metadata, $ids);
		
		if (!$user->super_user) {
			
			$permissions = \CMF\Model\Permission::select('item.id, item.action, item.resource, item.item_id')
		    ->leftJoin('item.roles', 'roles')
		    ->where("item.resource = '$class_name'")
		    ->andWhere("item.item_id IN(?1)")
		    ->andWhere("roles IN (?2)")
		    ->setParameter(1, $ids)
		    ->setParameter(2, $user->roles->toArray())
		    ->getQuery()->getArrayResult();
		    
		    foreach ($permissions as $permission) {
		    	$item_actions = isset($item_permissions[$permission['item_id']]) ? $item_permissions[$permission['item_id']] : array();
		    	$item_actions[] = $permission['action'];
		    	$item_permissions[$permission['item_id']] = $item_actions;
		    }
		    
		    foreach ($item_permissions as $item_id => $item_actions) {
		    	if (in_array('none', $item_actions) || (count($item_actions) > 0 && !in_array('view', $item_actions))) {
		    		$excluded_ids[] = $item_id;
		    	}
		    }
		    
			$tree = $this->filterTreeNodes($tree, $excluded_ids);
			
		}
		
		// Add more context for the template
		$this->table_name = $metadata->table['name'];
		$this->template = 'admin/item/tree.twig';
		$this->superlock = $class_name::superlock();
		$this->num_nodes = count($tree);
		
		// Permissions
		$this->can_create = $can_create && $can_edit;
		$this->can_edit = $can_edit;
		$this->can_delete = $can_delete;
		$this->can_manage = $can_manage;
		
		// Add the stuff for JS
		$this->js['tree'] = $tree;
		$this->js['item_permissions'] = $item_permissions;
		$this->js['excluded_ids'] = $excluded_ids;
		$this->js['classes'] = $classes;
		$this->js['table_name'] = $metadata->table['name'];
		$this->js['plural'] = $this->plural;
		$this->js['singular'] = $this->singular;
		
		// Permissions for JS
		$this->js['can_create'] = $can_create && $can_edit;
		$this->js['can_edit'] = $can_edit;
		$this->js['can_delete'] = $can_delete;
		$this->js['can_manage'] = $can_manage;
		
	}
	
	protected function filterTreeNodes($nodes, $excluded_ids)
	{
		$nodes = array_filter($nodes, function($var) use($excluded_ids) {
			return !in_array($var['id'], $excluded_ids);
		});
		$nodes = array_values($nodes);
		
		foreach ($nodes as &$node) {
			if (isset($node['children']) && count($node['children']) > 0) {
				$node['children'] = $this->filterTreeNodes($node['children'], $excluded_ids);
			}
		}
		
		return $nodes;
	}
	
	/**
	 * Recursive madness
	 */
	protected function processTreeNodes($nodes, &$metadata, &$ids)
	{
		$num = count($nodes);
		for ($i=0; $i < $num; $i++) { 
			
			$node = $nodes[$i];
			$disc_name = $metadata->discriminatorColumn['name'];
			$disc_val = isset($node[$disc_name]) ? $node[$disc_name] : '';
			$node['label'] = $node['title'];
			$node['class'] = isset($metadata->discriminatorMap[$disc_val]) ? $metadata->discriminatorMap[$disc_val] : $metadata->name;
			$ids[] = $node['id'];
			
			if (isset($node['__children'])) {
				$children = $this->processTreeNodes($node['__children'], $metadata, $ids);
				unset($node['__children']);
				$node['children'] = $children;
			}
			
			$nodes[$i] = $node;
			
		}
		return $nodes;
	}
	
	/**
	 * Gets the options in JSON format for when populating a select2 box
	 */
	public function action_options($table_name)
	{
		$class_name = \Admin::getClassForTable($table_name);
		if ($class_name === false) return $this->show404("Can't find that type!");
		
		// Check and see if we need to filter the results...
		$filters = array();
		$params = array();
		$find = \Input::param('find', false);
		if ($find !== false) {
			$ids = explode(',', $find);
			$filters[] = 'id IN(?1)';
			$params[] = $ids;
		}
		
		$options = $class_name::options($filters, array(), null, null, $params);
		
		// Get the options and put them in a format select2 would understand
		$output = array();
		foreach ($options as $id => $option) {
			$output[] = array( 'id' => $id, 'text' => $option );
		}
		
		$this->headers = array("Content-Type: application/json");
		return \Response::forge(json_encode($output), $this->status, $this->headers);
	}
	
}