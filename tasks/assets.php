<?php

namespace Fuel\Tasks;

use CMF\Storage;

class Assets
{
    
    /**
     * Sync assets to CDN
     */
    public function sync()
    {
        \Cli::write("Syncing user files...");
        Storage::syncFileFields();

        \Cli::write("Syncing static assets...");
        Storage::syncAssets();

        \Cli::write("Done!", 'green');
    }
}
