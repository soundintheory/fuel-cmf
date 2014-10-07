<?php

namespace CMF\View;

class Base extends \ViewModel
{
    protected $_template = null;
    
    public function __construct($method, $auto_filter = null, $view = null)
    {
        // Maybe set the template...
        $template = $this->template();
        if ($template !== null) $view = $template;
        
        parent::__construct($method, $auto_filter, $view);
    }
    
    public function getView()
    {
        return $this->_view;
    }
    
    protected function pageTree($model = 'Model_Page_Base', $label = null, $active_url = null, $extra_fields = null)
    {
        $extra_fields_str = (!is_null($extra_fields) ? ', page.'.implode(', page.', $extra_fields) : '');

        $nodes = $model::select('page.id, page.title, page.menu_title, page.lvl, page.lft, page.rgt'.$extra_fields_str.', url.url, url.slug', 'page')
        ->leftJoin('page.url', 'url')
        ->where('page.lvl > 0')
        ->andWhere('page.visible = true')
        ->orderBy('page.root, page.lft', 'ASC')
        ->getQuery()->getArrayResult();

        $root_label = $label ? $label.'_level1' : 'level1';
        $crumbs_label = $label ? $label.'_crumbs' : 'crumbs';
        
        $uri = $active_url ? $active_url : \CMF::original_uri();
        $nodes = \D::manager()->getRepository($model)->buildTree($nodes, array());
        $this->$crumbs_label = array();
        $this->processNodes($nodes, $uri, 1, $label);

        $crumbs = $this->$crumbs_label;
        ksort($crumbs);
        $this->$crumbs_label = $crumbs;

        return $this->$root_label = $nodes;
        
    }
    
    protected function getSettings()
    {
        $settings = \Model_Settings::select('item')
        ->setMaxResults(1)
        ->getQuery()->getResult();

        if(count($settings) > 0){
           return $settings[0];
        }

        return array();
    }

    protected function processNodes(&$nodes, $uri, $level = 1, $label = 'level')
    {
        $hasActive = false;
        
        foreach ($nodes as &$node) {
            
            $node['active'] = $node['url'] == $uri;
            $node['parent_active'] = false;

            if (isset($node['__children']) && count($node['__children']) > 0) {

                $newlevel = $level + 1;
                $node['parent_active'] = $this->processNodes($node['__children'], $uri, $newlevel, $label);
                
                if ($node['active'] || $node['parent_active']) {

                    $levelid = $label ? $label.'_level'.$newlevel : 'level'.$newlevel;
                    $parent = $node;
                    unset($parent['__children']);
                    if ($node['parent_active'] && $node['active']) $node['active'] = false;
                    $parent = array($parent);
                    
                    $this->$levelid = array_merge($parent, $node['__children']);
                    
                }
            }

            if ($node['parent_active'] || $node['active']) {
                $node['parent_active'] = $hasActive = true;
                $crumbs_label = $label ? $label.'_crumbs' : 'crumbs';
                $crumbs = $this->$crumbs_label;
                $crumbs[$level-1] = $node;
                $this->$crumbs_label = $crumbs;
            }

        }
        
        return $hasActive;
    }

/*
    protected function processNodes(&$nodes, $uri, $level = 1)
    {
        $hasActive = false;
        $uriLen = strlen($uri);
        
        foreach ($nodes as &$node)
        {
            $node['active'] = $node['parent_active'] = false;
            if ($uri == '/') {
                $node['active'] = $node['url'] == $uri;
            } else if ($node['url'] != '/') {
                $check_url = $node['url'].'/';
                $node['parent_active'] = strpos($uri, $check_url) === 0;
                $node['active'] = $node['parent_active'] && strlen($check_url) === $uriLen;
            }
            
            if ($node['active'] || $node['parent_active']) $hasActive = true;
            
            if (isset($node['__children']) && count($node['__children']) > 0) {
                $newlevel = $level + 1;
                $childActive = $this->processNodes($node['__children'], $uri, $newlevel);
                
                if ($node['active'] || $node['parent_active']) {
                    
                    $levelid = "level$newlevel";
                    $parentNode = \Arr::merge($node, array());
                    unset($parentNode['__children']);
                    if ($childActive && isset($parentNode['active'])) unset($parentNode['active']);
                    $parentNode = array($parentNode);
                    
                    $this->$levelid = array_merge($parentNode, $node['__children']);
                    
                }
            }
        }
        
        return $hasActive;
    }
    */
    
    public function before()
    {
        $uri = trim($_SERVER['REQUEST_URI'], '/');
        $this->uri = empty($uri) ? '/' : $uri;
        $this->view = $this;
        $this->settings = $this->getSettings();
    }
    
    public function template()
    {
        return $this->_template;
    }
    
}
