<?php

namespace AOPDF\Commands;

use AOPDF\AOPDF;
use Illuminate\Console\Command;

class StorageClean extends Command
{
    protected $signature = 'ao-pdf:storage-clean';

    protected $description = 'Command description';

    public function handle()
    {
        $disk = AOPDF::disk();

        //
        // TMP FILES
        //
        $locations = $disk->files('tmp');
        foreach ($locations as $location) {
            $created_at = explode('_', basename($location));
            $created_at = $created_at[0];

            if (is_numeric($created_at) && ((time() - $created_at) < (6 * 60 * 60))) {
                continue;
            }

            $disk->delete($location);
        }

        //
        // CACHE FILES
        //
        $locations = $disk->files('cache');
        foreach ($locations as $location) {
            $path = $disk->get($location);

            if ($disk->exists($path)) {
                continue;
            }

            $disk->delete($location);
        }

    }
}
