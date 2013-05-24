<?php

namespace CMF\Composer;

use Composer\Composer;
use Composer\Package\PackageInterface;

/**
 * Provides hooks for automatic install during composer install
 * 
 * @package  CMF\Composer
 */
class Hooks
{
    public static function preInstall(PackageInterface $package, Composer $composer)
    {
        
    }
    
    public static function postInstall(PackageInterface $package, Composer $composer)
    {
        
    }
}
