<?php

namespace Marquine\Chronos;

use Illuminate\Support\ServiceProvider;

class ChronosServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/activity.php' => config_path('activity.php'),
            __DIR__.'/migrations/2016_10_01_000000_create_activities_table.php' => database_path('migrations/2016_10_01_000000_create_activities_table.php'),
        ], 'activity-log');
    }
}
