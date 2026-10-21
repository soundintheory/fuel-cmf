<?php

namespace CMF\Twig;

/**
 * Filesystem loader that can report which template files it has loaded,
 * so the page cache knows which files to check for changes
 */
class FilesystemLoader extends \Twig\Loader\FilesystemLoader
{
    /**
     * Returns all the files that have been loaded
     *
     * @return array
     */
    public function getFiles()
    {
        return array_values(array_filter($this->cache));
    }
}
