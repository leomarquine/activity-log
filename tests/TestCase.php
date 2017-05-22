<?php

use Marquine\Chronos\Chronos;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Config\Repository as Config;
use Illuminate\Database\Capsule\Manager as Capsule;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    protected $app;

    public function setUp()
    {
        parent::setUp();

        $this->app = new Container;

        $this->app->singleton('config', Config::class);

        $this->app['config']->set('chronos', require __DIR__.'/../config/chronos.php');

        $this->app->singleton('chronos', function ($app) {
            return new Chronos($app['config']);
        });

        Facade::setFacadeApplication($this->app);

        $this->setUpDatabase();
        $this->migrateTables();
    }

    protected function setUpDatabase()
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        User::flushEventListeners();
        User::boot();
    }

    protected function migrateTables()
    {
        Capsule::schema()->create('users', function($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->softDeletes();
            $table->timestamps();
        });

        Capsule::schema()->create('activities', function($table) {
            $table->increments('id');
            $table->string('model_type');
            $table->integer('model_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('event');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });
    }
}


use Illuminate\Database\Eloquent\Model;
use Marquine\Chronos\RecordsActivities;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model {
    use SoftDeletes, RecordsActivities;

    protected $fillable = ['email', 'name'];
}

function auth() {
    return new class {
        public function id() {}
    };
}
