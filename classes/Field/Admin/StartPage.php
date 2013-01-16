<?php

namespace CMF\Field\Admin;

class StartPage extends \CMF\Field\Base {
	
	protected static $options = null;
    
    protected static $defaults = array(
        
    );
    
    public static function getAssets()
    {
        return array(
            'css' => array('/admin/assets/select2/select2.css'),
            'js' => array('/admin/assets/select2/select2.min.js', '/admin/assets/js/fields/select2.js')
        );
    }
    
    /** inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        return '<a href="'.$edit_link.'" class="item-link">'.strval($value).'</a>';
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $required = isset($settings['required']) ? $settings['required'] : false;
        $include_label = isset($settings['label']) ? $settings['label'] : true;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = array( 'class' => 'input-xxlarge' );
        $attributes = array( 'class' => 'field-type-startpage controls control-group'.($has_errors ? ' error' : '') );
        $id = isset($value) ? $value->id : '';
        $label = (!$include_label) ? '' : \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $settings['mapping']['fieldName']);
        
        // DROPDOWN CONTENT
        $options = static::getOptions($settings, $model);
        $input = \Form::select($settings['mapping']['fieldName'], $id, $options, $input_attributes);
        
        if (isset($settings['wrap']) && $settings['wrap'] === false) return $label.$input;
        return html_tag('div', array( 'class' => 'controls control-group'.($has_errors ? ' error' : '') ), $label.$input);
    }
    
    protected static function getOptions(&$settings, $model)
    {
    	if (static::$options !== null && is_array(static::$options)) return static::$options;
    	
    	$options = (isset($settings['mapping']['nullable']) && $settings['mapping']['nullable']) ? array( null => '---' ) : array();
        $target_class = 'CMF\\Model\\URL';
        $filters = array();
        $tree_types = array();
        $types = $target_class::select('item.type')->distinct()->orderBy('item.type', 'ASC')->getQuery()->getScalarResult();
        
        foreach ($types as $type)
        {
            $type = $type['type'];
            if (!class_exists($type)) continue;
            
            $metadata = $type::metadata();
            $root_class = $metadata->rootEntityName;
            if (isset($root_class)) {
            	$type = $root_class;
            }
            
            $name = $type::plural();
            if (isset($options[$name])) continue;
            
            $group = \Arr::get($options, $name, array());
            $repository = \DoctrineFuel::manager()->getRepository($type);
            $prop = property_exists('menu_title', $type) ? 'menu_title' : 'title';
            
            if (($repository instanceof \Gedmo\Tree\Entity\Repository\NestedTreeRepository) && !in_array($name, $tree_types))
            {
				$tree_types[] = $name;
				
				// Put in the tree data...
				$query = $type::select('item, url')
				->leftJoin('item.url', 'url')
				->where('item.lvl > 0');
				
				if (count($filters) > 0) {
				    foreach ($filters as $filter)
				    {
				        $query = $query->andWhere('item.'.$filter);
				    }
				}
				
				$tree = $query->orderBy('item.root, item.lft', 'ASC')->getQuery()->getArrayResult();
				$tree = $repository->buildTree($tree, array());
				$options[$name] = static::buildTreeOptions($tree, $prop, array());
				
				continue;
            }
            
            $items = $type::select("item.id, item.$prop, url.url, url.id url_id")->leftJoin('item.url', 'url')->orderBy("item.$prop", "ASC")->getQuery()->getArrayResult();
            
            if (is_array($items) && count($items) > 0) {
                
                foreach ($items as $item)
                {
                    $group[strval($item['url_id'])] = $item[$prop];
                }
                $options[$name] = $group;
                
            }
            
        }
        
        foreach($options as $group_name => &$group_value)
        {
            if (is_array($group_value) && !in_array($group_name, $tree_types))
            {
                uasort($group_value, function($a, $b) {
                    return strcmp(strtolower($a), strtolower($b));
                });
            }
        }
        
        uksort($options, function($a, $b) {
            $a = strtolower($a);
            $b = strtolower($b);
            
            if ($a == 'pages') {
                return -1;
            } else if ($b == 'pages') {
                return 1;
            }
            
            return strcmp($a, $b);
        });
        
        return static::$options = $options;
    	
    }
    
    protected static function buildTreeOptions(&$tree, $prop, $options = array( null => '---' ), $prefix = '')
    {
        foreach ($tree as &$node)
        {
            $options[strval(\Arr::get($node, 'url.id', '-1'))] = $prefix.str_repeat(' >', $node['lvl']-1).' '.$node[$prop];
            if (isset($node['__children']) && count($node['__children']) > 0) {
                $options = static::buildTreeOptions($node['__children'], $prop, $options, $node[$prop]);
            }
        }
        return $options;
    }
	
}