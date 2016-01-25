<?php

class View_Base extends \CMF\View\Base
{
    /*
    public function latestNews($num = 4)
    {
        return \Model_News::select('item.title, item.content, item.date, item.image, url.url')
        ->leftJoin('item.url', 'url')
        ->orderBy('item.date', 'DESC')
        ->setMaxResults($num)
        ->getQuery()->getArrayResult();
        
    }
    */
    
    public function before()
    {
        parent::before();
        
        // Generates the page tree for navs... comment out if not necessary
        $this->pageTree();
    }
    
}