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
    
    protected function pageTree($model = 'Model_Page_Base')
    {
        $nodes = $model::select('page.title, page.menu_title, page.lvl, page.lft, page.rgt, url.url, url.slug', 'page')
        ->leftJoin('page.url', 'url')
        ->where('page.lvl > 0')
        ->andWhere('page.visible = true')
        ->orderBy('page.root, page.lft', 'ASC')
        ->getQuery()->getArrayResult();
        
        $uri = '/'.trim(\Input::uri(), '/');
        if ($uri != '/') { $uri .= '/'; }
        
        $nodes = \D::manager()->getRepository($model)->buildTree($nodes, array());
        $this->processNodes($nodes, $uri, 1);
        
        return $this->level1 = $nodes;
        
    }
    
    protected function getSettings(){
        $settings = \Model_Settings::select('item')
        ->setMaxResults(1)
        ->getQuery()->getArrayResult();

        if(count($settings) > 0){
           return $settings[0];
        }

        return array();
    }

    protected function processNodes(&$nodes, $uri, $level = 1)
    {
        $hasActive = false;
        
        foreach ($nodes as &$node)
        {
            $node['active'] = false;
            if ($uri == '/') {
                $node['active'] = $node['url'] == $uri;
            } else if ($node['url'] != '/') {
                $check_url = $node['url'].'/';
                $node['active'] = strpos($uri, $check_url) === 0;
            }
            
            if ($node['active']) $hasActive = true;
            
            if (isset($node['__children']) && count($node['__children']) > 0) {
                $newlevel = $level + 1;
                $childActive = $this->processNodes($node['__children'], $uri, $newlevel);
                
                if ($node['active']) {
                    
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
    
    public function before()
    {
        $uri = \Input::uri();
        $this->uri = empty($uri) ? '/' : $uri;
        $this->view = $this;
        $this->settings = $this->getSettings();
    }
    
    public function template()
    {
        return $this->_template;
    }
    
}
