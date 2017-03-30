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
        ], 'activity-log');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\TableCommand::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $chronos = new Chronos($this->app);

        $this->app->instance('Marquine\Chronos\Chronos', $chronos);
    }
}
