<?php

namespace CMF\Twig;

use Twig\Template,
    Twig\Source;

/**
 * Provides a template context ($this, $context) for including the compiled
 * bodies of nocache blocks
 */
class TemplateInclude extends Template
{
    public function renderInclude($file, $context = array())
    {
        ob_start();
        include $file;
        return ob_get_clean();
    }

    public function getTemplateName(): string
    {
        return 'nocache';
    }

    public function getDebugInfo(): array
    {
        return array();
    }

    public function getSourceContext(): Source
    {
        return new Source('', 'nocache');
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        return array();
    }
}
