<?php

namespace CMF\View;

class Base extends \ViewModel
{
    
    public function placeholder($text, $name, $template = '', $data = array())
    {
        $pattern = '/\\{\\{ '.$name.' \\}\\}/sUi';
        preg_match_all($pattern, $text, $hits);
        $tags = $hits[0];
        if (count($tags) === 0) return $text;
        
        if (empty($template))
        {
            return str_replace("[[$name]]", '', $text);
        }
        else
        {
            $parts = preg_split($pattern, $text);
            $offset = 1;
            
            for ($i = 0; $i < count($tags); $i++) {
                
                array_splice($parts, $offset, 0, strval(\View::forge($template, \Arr::merge($data, array( 'placeholder_num' => $i )), false)));
                $offset += 2;
                
            }
            
            return implode('', $parts);
        }
    }
    
    protected function pageTree()
    {
        $nodes = \Model_Page::select('page.title, page.menu_title, page.lvl, page.lft, page.rgt, url.url, url.slug', 'page')
        ->leftJoin('page.url', 'url')
        ->where('page.lvl > 0')
        ->andWhere('page.visible = true')
        ->orderBy('page.root, page.lft', 'ASC')
        ->getQuery()->getArrayResult();
        
        $uri = rtrim(\Input::uri(), '/');
        if (empty($uri)) { $uri = '/'; }
        else { $uri .= '/'; }
        
        $nodes = \DoctrineFuel::manager()->getRepository('Model_Page')->buildTree($nodes, array());
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
}
