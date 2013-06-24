<?php

namespace CMF\Field\Object;

class Link extends Object {
	
	protected static $options = null;
    
    protected static $defaults = array(
        'dynamic' => false,
        'array' => false,
        'tabular' => false,
        'widget' => false,
        'widget_icon' => 'link',
        'sub_group' => false,
        'fields' => array(
            'href' => array( 'type' => 'string' ),
            'external' => array( 'type' => 'boolean', 'visible' => false )
        )
    );
    
    /** inheritdoc */
    public static function displayList($value, $edit_link, &$settings, &$model)
    {
        $link = static::processLink($value);
        return '<a href="'.$edit_link.'" class="item-link">'.$link['href'].'</a>';
    }
    
    /** @inheritdoc */
    public static function process($value, $settings, $model)
    {
        return parent::process($value, $settings, $model);
    }
    
    /** inheritdoc */
    public static function displayForm($value, &$settings, $model)
    {
        $required = isset($settings['required']) ? $settings['required'] : false;
        $errors = $model->getErrorsForField($settings['mapping']['fieldName']);
        $has_errors = count($errors) > 0;
        $input_attributes = array( 'class' => 'input-xxlarge' );
        $attributes = array( 'class' => 'field-type-link controls control-group'.($has_errors ? ' error' : '') );
        $href_name = $settings['mapping']['fieldName'].'[href]';
        $value['href'] = isset($value['href']) ? $value['href'] : null;
        
        // EXTERNAL CHECKBOX
        $external_name = $settings['mapping']['fieldName'].'[external]';
        $external_value = \Arr::get($value, 'external', false);
        $external = \Form::hidden($external_name, '0').html_tag('label', array( 'class' => 'checkbox external-checkbox' ), \Form::checkbox($external_name, '1', $external_value, array()).' external');
        $label = \Form::label($settings['title'].($required ? ' *' : '').($has_errors ? ' - '.$errors[0] : ''), $href_name, array( 'class' => 'item-label' )).$external.html_tag('div', array( 'class' => 'clear' ), '&nbsp;');
        
        if ($external_value) {
            $attributes['class'] .= ' external';
        }
        
        // INTERNAL DROPDOWN CONTENT
        $options = static::getOptions($settings, $model);
        $href_value_int = ($external_value) ? '' : $value['href'];
        $input = \Form::select($href_name, $href_value_int, $options, $input_attributes);
        $int_content = html_tag('div', array( 'class' => 'internal-link' ), $input);
        
        // EXTERNAL INPUT CONTENT
        $href_value_ext = ($external_value) ? $value['href'] : '';
        $ext_input = \Form::input($href_name, $href_value_ext, $input_attributes);
        $addon = html_tag('span', array( 'class' => 'add-on' ), 'http://');
        $ext_content = html_tag('div', array( 'class' => 'external-link input-prepend' ), $addon.$ext_input);
        
        return html_tag('div', $attributes, $label.$int_content.$ext_content);
    }
    
    protected static function getOptions(&$settings, $model)
    {
      
    	if (static::$options !== null && is_array(static::$options)) {

            $options = static::$options;

            if (isset($settings['mapping']['nullable']) && $settings['mapping']['nullable'] && 
                !(isset($settings['required']) && $settings['required'])) {
                $options = array( '' => '' ) + $options;
            }
            return $options;
        }

    	$options = (isset($settings['mapping']['nullable']) && $settings['mapping']['nullable']) ? array( null => '' ) : array();
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
            return strcmp(strtolower($a), strtolower($b));
        });

        static::$options = $options;

        if (isset($settings['mapping']['nullable']) && $settings['mapping']['nullable'] && 
            !(isset($settings['required']) && $settings['required'])){
            $options = array( '' => '' ) + $options;
        }

        return $options;
    	
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
    
    public static function processLink($data)
    {
        // Get the link attribute
        if ($is_array = is_array($data)) {
            $output = isset($data['href']) ? $data['href'] : '';
        } else {
            $output = $data;
        }
        
        // Query the urls table if it's an ID
        if (is_numeric($output)) {
            $link = \CMF\Model\URL::select('item.url')->where('item.id = '.$output)->getQuery()->getScalarResult();
            $output = (count($link) > 0) ? $link[0]['url'] : null;
        }
        
        // Return the same array that was passed in...
        if ($is_array) {
            $data['href'] = $output;
            return $data;
        }
        
        return array(
            'href' => $output
        );
    }
    
    /** inheritdoc */
    public static function type($settings = array())
    {
        return 'link';
    }
    
    /** inheritdoc */
    public static function getAssets()
    {
        return array(
            'js' => array(
                '/admin/assets/js/fields/link.js'
            )
        );
    }
	
}