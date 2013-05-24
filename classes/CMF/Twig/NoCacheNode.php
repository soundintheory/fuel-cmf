<?php

namespace CMF\Twig;

use Twig_Node,
    Twig_Compiler,
    Twig_NodeInterface;

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a nocache node.
 */
class NoCacheNode extends Twig_Node
{
    protected static $counter = 1;
    protected $cache_name;
    protected $filename;
    
    public function __construct(Twig_NodeInterface $body, $lineno, $filename, $tag = null, $name = null)
    {
        $this->filename = $filename;
        $this->cache_name = ($name !== null) ? $name : 'nc'.(static::$counter++);
        parent::__construct(array('body' => $body), array(), $lineno, $tag);
    }
    
    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        $env = $compiler->getEnvironment();
        $cachefile_parts = explode('.', $env->getCacheFilename($this->filename));
        array_pop($cachefile_parts);
        $cachefile = implode('.', $cachefile_parts).'_'.$this->cache_name.'.php';
        $dir = dirname($cachefile);
        
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException(sprintf("Unable to create the cache directory (%s).", $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new RuntimeException(sprintf("Unable to write in the cache directory (%s).", $dir));
        }
        
        $subcompiler = new Twig_Compiler($env);
        $subcompiler->write('echo "<!-- nocache_'.$this->cache_name.' \''.$cachefile.'\' -->\n";');
        foreach ($this->nodes as $node) {
            $node->compile($subcompiler);
        }
        $subcompiler->write('echo "<!-- endnocache_'.$this->cache_name.' -->\n";');
        
        // Write the subcompiled code to a file
        @file_put_contents($cachefile, "<?php\n\n".$subcompiler->getSource());
        
        // Include that file
        $compiler->write("include('$cachefile');");
    }
}
