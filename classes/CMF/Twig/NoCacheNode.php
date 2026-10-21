<?php

namespace CMF\Twig;

use Twig\Node\Node,
    Twig\Compiler,
    Twig\Cache\FilesystemCache;

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a nocache node. The body is compiled into its own file next to the
 * compiled template, which CMF\Cache includes when serving a cached page.
 */
class NoCacheNode extends Node
{
    protected static $counter = 1;
    protected $cache_name;
    protected $template_name;

    public function __construct(Node $body, $lineno, $template_name, $name = null)
    {
        $this->template_name = $template_name;
        $this->cache_name = ($name !== null) ? $name : 'nc'.(static::$counter++);
        parent::__construct(array('body' => $body), array(), $lineno);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler)
    {
        $env = $compiler->getEnvironment();
        $cache = $env->getCache(false);
        if (is_string($cache)) $cache = new FilesystemCache($cache);
        if (!$cache instanceof FilesystemCache) {
            throw new \RuntimeException('The nocache tag requires a filesystem template cache.');
        }

        $cachefile_parts = explode('.', $cache->generateKey($this->template_name, $env->getTemplateClass($this->template_name)));
        array_pop($cachefile_parts);
        $cachefile = implode('.', $cachefile_parts).'_'.$this->cache_name.'.php';
        $dir = dirname($cachefile);

        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf("Unable to create the cache directory (%s).", $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new \RuntimeException(sprintf("Unable to write in the cache directory (%s).", $dir));
        }

        $subcompiler = new Compiler($env);
        $subcompiler->write('echo "<!-- nocache_'.$this->cache_name.' \''.$cachefile.'\' -->\n";');
        $subcompiler->subcompile($this->getNode('body'));
        $subcompiler->write('echo "<!-- endnocache_'.$this->cache_name.' -->\n";');

        // Write the subcompiled code to a file
        @file_put_contents($cachefile, "<?php\n\n".$subcompiler->getSource());

        // Include that file
        $compiler->write("include('$cachefile');\n");
    }
}
