<?php
/**
 * phpDocumentor
 *
 * PHP Version 5
 *
 * @copyright 2010-2013 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace CMF\Composer;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;
use Composer\Script\ScriptEvents;

class HookedPackageInstaller extends \Composer\Installer\LibraryInstaller
{
    protected $loader;
    
    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $type = $package->getType();

        $prettyName = $package->getPrettyName();
        if (strpos($prettyName, '/') !== false) {
            list($vendor, $name) = explode('/', $prettyName);
        } else {
            $vendor = '';
            $name = $prettyName;
        }

        $availableVars = compact('name', 'vendor', 'type');

        $extra = $package->getExtra();
        if (!empty($extra['installer-name'])) {
            $availableVars['name'] = $extra['installer-name'];
        }

        if ($this->composer->getPackage()) {
            $pkg_extra = $this->composer->getPackage()->getExtra();
            if (!empty($pkg_extra['installer-paths'])) {
                $customPath = $this->mapCustomInstallPaths($pkg_extra['installer-paths'], $prettyName, $type);
                if ($customPath !== false) {
                    return $this->templatePath($customPath, $availableVars);
                }
            }
        }
        
        if (empty($extra['installer-path'])) {
            return parent::getInstallPath($package);
        }

        return $this->templatePath($extra['installer-path'], $availableVars);
    }
    
    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);
        $this->initHook('post-package-install', $package, ScriptEvents::POST_AUTOLOAD_DUMP);
    }

    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->callHook('pre-package-update', $target);
        parent::update($repo, $initial, $target);
        $this->callHook('post-package-update', $target);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->callHook('pre-package-uninstall', $package);
        parent::uninstall($repo, $package);
        $this->callHook('post-package-uninstall', $package);
    }
    
    /**
     * Attaches an event to be called by composer via the main event dispatcher
     * 
     * @param  string           $hookName
     * @param  PackageInterface $package
     */
    protected function initHook($hookName, PackageInterface $package, $scriptEventName)
    {
        $extra = $package->getExtra();
        if (empty($extra[$hookName])) return;
        $command = $extra[$hookName];
        
        if ($rootPackage = $this->composer->getPackage()) {
            $scripts = $rootPackage->getScripts();
            $listeners = isset($scripts[$scriptEventName]) ? $scripts[$scriptEventName] : array();
            $listeners[] = $command;
            $scripts[$scriptEventName] = $listeners;
            $rootPackage->setScripts($scripts);
        }
    }
    
    /**
     * Attempts to call a hook directly
     * 
     * @param  string           $hookName
     * @param  PackageInterface $package
     */
    protected function callHook($hookName, PackageInterface $package)
    {
        // $composer, $package
        $this->initAutoloader();
        $extra = $package->getExtra();
        
        if (empty($extra[$hookName])) return;
        
        $command = $extra[$hookName];
        if (is_callable($command)) {
            call_user_func($command, $package, $this->composer);
        }
    }

    /**
     * Replace vars in a path
     *
     * @param  string $path
     * @param  array  $vars
     * @return string
     */
    protected function templatePath($path, array $vars = array())
    {
        if (strpos($path, '{') !== false) {
            extract($vars);
            preg_match_all('@\{\$([A-Za-z0-9_]*)\}@i', $path, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $var) {
                    $path = str_replace('{$' . $var . '}', $$var, $path);
                }
            }
        }

        return $path;
    }
    
    /**
     * Initialises the autoloader for the package
     * 
     */
    protected function initAutoloader()
    {
        if ($this->loader) {
            $this->loader->unregister();
        }
        
        $package = $this->composer->getPackage();
        $generator = $this->composer->getAutoloadGenerator();
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $packageMap = $generator->buildPackageMap($this->composer->getInstallationManager(), $package, $packages);
        $map = $generator->parseAutoloads($packageMap, $package);
        
        $this->loader = $generator->createLoader($map);
        $this->loader->register();
    }
    
    /**
     * Checks if string given references a class path and method
     *
     * @param  string  $callable
     * @return boolean
     */
    protected function isPhpScript($callable)
    {
        return false === strpos($callable, ' ') && false !== strpos($callable, '::');
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
      return (bool)('hooked-package' === $packageType);
    }
}
