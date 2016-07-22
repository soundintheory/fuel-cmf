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
    
    public static function getDefaults(){
        return self::$defaults;
    }

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
        $input_attributes = array( 'class' => 'input input-xxlarge' );
        $attributes = array( 'class' => 'field-type-link controls control-group'.($has_errors ? ' error' : '') );
        $href_name = $settings['mapping']['fieldName'].'[href]';
        $value['href'] = isset($value['href']) ? $value['href'] : null;
        $label_text = $settings['title'].($required ? ' *' : '');

        // Translation?
        if (\CMF::$lang_enabled && !\CMF::langIsDefault() && isset($settings['mapping']['columnName']) && $model->isTranslatable($settings['mapping']['columnName'])) {
            
            // If there is no translation
            if (!$model->hasTranslation($settings['mapping']['columnName'])) {
                $attributes['class'] .= ' no-translation';
                $input_attributes['class'] .= ' no-translation';
                $label_text = '<img class="lang-flag" src="/admin/assets/img/lang/'.\CMF::defaultLang().'.png" />&nbsp; '.$label_text;
            } else {
                $label_text = '<img class="lang-flag" src="/admin/assets/img/lang/'.\CMF::lang().'.png" />&nbsp; '.$label_text;
            }
            
        }
        
        // EXTERNAL CHECKBOX
        $external_name = $settings['mapping']['fieldName'].'[external]';
        $external_value = \Arr::get($value, 'external', false);
        $external = \Form::hidden($external_name, '0').html_tag('label', array( 'class' => 'checkbox external-checkbox' ), \Form::checkbox($external_name, '1', $external_value, array()).' custom');
        $label = \Form::label($label_text.($has_errors ? ' - '.$errors[0] : ''), $href_name, array( 'class' => 'item-label' )).$external.html_tag('div', array( 'class' => 'clear' ), '&nbsp;');
        
        if ($external_value) {
            $attributes['class'] .= ' external';
        }
        
        // EXTERNAL INPUT CONTENT
        $href_value_ext = ($external_value) ? $value['href'] : '';
        $ext_input = \Form::input($href_name, $href_value_ext, $input_attributes);
        $ext_content = html_tag('div', array( 'class' => 'external-link' ), $ext_input);

        // INTERNAL DROPDOWN CONTENT
        $options = static::getOptions($settings, $model);
        $href_value_int = ($external_value) ? '' : $value['href'];

        // Check if the value is actually an alias
        if (!empty($href_value_int))
        {
            $url_value = \CMF\Model\URL::find($href_value_int);
            if ($url_value && $alias = $url_value->alias)
            {
                $href_value_int = $alias->id;
            }
        }

        $input = \Form::select($href_name, $href_value_int, $options, $input_attributes);
        $int_content = html_tag('div', array( 'class' => 'internal-link' ), $input);

        return html_tag('div', $attributes, $label.$int_content.$ext_content).html_tag('div', array(), '');
    }

    public static function getOptions(&$settings, $model, $html = false)
    {
        $allow_empty = isset($settings['mapping']['nullable']) && $settings['mapping']['nullable'] && !(isset($settings['required']) && $settings['required']);
        
        if (static::$options !== null && is_array(static::$options)) return $allow_empty ? array( '' => '' ) + static::$options : static::$options;
        
        $options = array();
        $target_class = 'CMF\\Model\\URL';
        $filters = \Arr::get($settings, 'filters', array());
        $tree_types = array();

        $types = $target_class::select('item.type')->distinct()->where('item.item_id IS NOT NULL')->orderBy('item.type', 'ASC');

        // Allow certain types
        $allow_types = \Arr::get($settings, 'allow_types', array());
        if (count($allow_types) > 0) {
            $types->where('item.type IN(?1)')->setParameter(1, $allow_types);
        } else {

            // Exclude certain types
            $exclude_types = \Arr::get($settings, 'exclude_types', array('Model_Page_Home'));
            if (count($exclude_types) > 0) {
                $types->where('item.type NOT IN(?1)')->setParameter(1, $exclude_types);
            }

        }

        // Exclude / include modules
        $exclude_modules = \Arr::get($settings, 'exclude_modules', array());
        $allow_modules = \Arr::get($settings, 'allow_modules', array());

        $types = $types->getQuery()->getScalarResult();

        foreach ($types as $type)
        {
            $type = $type['type'];
            if (!class_exists($type)) continue;
            
            $metadata = $type::metadata();
            $root_class = $metadata->rootEntityName;
            $module = $type::getModule();
            $moduleTitle = \CMF::moduleTitle($module);

            // Exclude / include modules
            if (count($allow_modules) > 0 && !in_array($module, $allow_modules)) continue;
            else if (in_array($module, $exclude_modules)) continue;

            if (isset($root_class)) {
                $type = $root_class;
            }

            $name = $type::_static() ? $moduleTitle : $moduleTitle.' '.$type::plural();
            if (isset($options[$name])) continue;
            
            $group = \Arr::get($options, $name, array());
            $repository = \D::manager()->getRepository($type);
            $prop = property_exists('menu_title', $type) ? 'menu_title' : 'title';
            
            if (($repository instanceof \Gedmo\Tree\Entity\Repository\NestedTreeRepository) && !in_array($name, $tree_types))
            {
                $tree_types[] = $name;
                
                // Put in the tree data...
                $query = $type::select('item, url')
                ->leftJoin('item.url', 'url')
                ->where('item.lvl > 0')
                ->andWhere('url.alias is NULL');
                
                if (count($filters) > 0) {
                    foreach ($filters as $filter)
                    {
                        $query = $query->andWhere('item.'.$filter);
                    }
                }
                
                $tree = $query->orderBy('item.root, item.lft', 'ASC')->getQuery();
                // Set the query hint if multi lingual!
                if (\CMF\Doctrine\Extensions\Translatable::enabled()) {
                    $tree->setHint(
                        \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                    );
                }
                $tree = $tree->getArrayResult();
                $tree = $repository->buildTree($tree, array());
                $options[$name] = static::buildTreeOptions($tree, $prop, array());

                continue;
            }
            
            $items = $type::select("item.id, item.$prop, url.url, url.id url_id, alias.id alias_id")
            ->where('url.alias is NULL')
            ->leftJoin('item.url', 'url')
            ->leftJoin('url.alias', 'alias')
            ->orderBy("item.$prop", "ASC")->getQuery();
            
            // Set the query hint if multi lingual!
            if (\CMF\Doctrine\Extensions\Translatable::enabled()) {
                $items->setHint(
                    \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
                    'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                );
            }

            $items = $items->getArrayResult();

            if (is_array($items) && count($items) > 0) {
                
                foreach ($items as $item)
                {
                    if (empty($item['url_id'])) continue;
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
        return $allow_empty ? array( '' => '' ) + $options : $options;
    }
    
    public static function getOptionsOLD(&$settings, $model)
    {
        $allow_empty = isset($settings['mapping']['nullable']) && $settings['mapping']['nullable'] && !(isset($settings['required']) && $settings['required']);
        
        if (static::$options !== null && is_array(static::$options)) return $allow_empty ? array( '' => '' ) + static::$options : static::$options;
        
        $options = array();
        $target_class = 'CMF\\Model\\URL';
        $filters = array();
        $tree_types = array();
        $types = $target_class::select('item.type')->distinct()->where('item.item_id IS NOT NULL')->orderBy('item.type', 'ASC')->getQuery()->getScalarResult();
        
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
            $repository = \D::manager()->getRepository($type);
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
				
				$tree = $query->orderBy('item.root, item.lft', 'ASC')->getQuery();

                // Set the query hint if multi lingual!
                if (\CMF\Doctrine\Extensions\Translatable::enabled()) {
                    $tree->setHint(
                        \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                    );
                }
                $tree = $tree->getArrayResult();
				$tree = $repository->buildTree($tree, array());
				$options[$name] = static::buildTreeOptions($tree, $prop, array());
				
				continue;
            }
            
            $items = $type::select("item.id, item.$prop, url.url, url.id url_id")->leftJoin('item.url', 'url')->orderBy("item.$prop", "ASC")->getQuery();
            
            // Set the query hint if multi lingual!
            if (\CMF\Doctrine\Extensions\Translatable::enabled()) {
                $items->setHint(
                    \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
                    'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                );
            }
            $items = $items->getArrayResult();
            
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
        return $allow_empty ? array( '' => '' ) + $options : $options;
    }
    

    public static function getOptionsStatic(&$settings, $model)
    {
        $allow_empty = isset($settings['mapping']['nullable']) && $settings['mapping']['nullable'] && !(isset($settings['required']) && $settings['required']);
        
        if (static::$options !== null && is_array(static::$options)) return $allow_empty ? array( '' => '' ) + static::$options : static::$options;
        
        $options = array();
        $target_class = 'CMF\\Model\\URL';
        $filters = array();
        $tree_types = array();
        $types = $target_class::select('item.type')->distinct()->where('item.item_id IS NOT NULL')->orderBy('item.type', 'ASC')->getQuery()->getScalarResult();
        
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
            $repository = \D::manager()->getRepository($type);
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
                
                $tree = $query->orderBy('item.root, item.lft', 'ASC')->getQuery();

                // Set the query hint if multi lingual!
                if (\CMF\Doctrine\Extensions\Translatable::enabled()) {
                    $tree->setHint(
                        \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                    );
                }
                $tree = $tree->getArrayResult();
                $tree = $repository->buildTree($tree, array());
                $options[$name] = static::buildTreeOptionsStatic($tree, $prop, array());
                continue;
            }
            
            $items = $type::select("item.id, item.$prop, url.url, url.id url_id")->leftJoin('item.url', 'url')->orderBy("item.$prop", "ASC")->getQuery();

            // Set the query hint if multi lingual!
            if (\CMF\Doctrine\Extensions\Translatable::enabled()) {
                $items->setHint(
                    \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
                    'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                );
            }
            $items = $items->getArrayResult();
            
            if (is_array($items) && count($items) > 0) {
                
                foreach ($items as $item)
                {
                    $group[strval($item[$prop])] = $item['url'];
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
        return $allow_empty ? array( '' => '' ) + $options : $options;
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
    
     protected static function buildTreeOptionsStatic(&$tree, $prop, $options = array( null => '---' ), $prefix = '')
    {
        foreach ($tree as &$node)
        {
            $tmpkey = $prefix.str_repeat(' >', $node['lvl']-1).' '.$node[$prop];
            $tmpval = strval(\Arr::get($node, 'url.url', '-1'));
            $options[$tmpkey] =  $tmpval ;
            if (isset($node['__children']) && count($node['__children']) > 0) {
                $options = static::buildTreeOptionsStatic($node['__children'], $prop, $options, $node[$prop]);
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
                '/admin/assets/select2/select2.min.js',
                '/admin/assets/js/fields/link.js'
            ),
            'css' => array(
                '/admin/assets/select2/select2.css'
            )
        );
    }
	
}