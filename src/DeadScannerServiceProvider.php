<?php

namespace Mrkindy\Deadscanner;

use Mrkindy\Deadscanner\Console\Commands\DeadClasses;
use Mrkindy\Deadscanner\Console\Commands\DeadMethods;

use Illuminate\Support\ServiceProvider;

class DeadScannerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DeadClasses::class,
                DeadMethods::class,
            ]);
        }
    }
}