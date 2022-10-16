<?php

namespace Spork\Development\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\Filesystem;

class DeleteDevelopmentFiles // implements ShouldQueue
{
    public function handle($event)
    {
        $feature = $event->featureList;

        if ($feature->feature !== 'development') {
            return;
        }

        $path = $feature->settings['path'];

        if (! file_exists($path)) {
            info('Template does not exist at '.$path);

            return;
        }

        $filesystem = new Filesystem;
        $filesystem->deleteDirectory($path);
    }
}
