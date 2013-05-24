<?php

namespace CMF\Twig;

use Twig_Template,
    Twig_Environment;

class TemplateInclude extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);
    }
    
    public function renderInclude($file, $context = array())
    {
        ob_start();
        include $file;
        return ob_get_clean();
    }
    
    public function getTemplateName()
    {
        return 'nocache';
    }
    
    protected function doDisplay(array $context, array $blocks = array())
    {
        
    }
    
}
