<?php

namespace Marquine\Chronos;

use Illuminate\Foundation\AliasLoader;
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
            __DIR__.'/../config/chronos.php' => config_path('chronos.php'),
        ], 'chronos');

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

        $this->app->alias(Chronos::class, 'chronos');

        AliasLoader::getInstance()->alias('Chronos', Facades\Chronos::class);
    }
}
