<?php

use Marquine\Chronos\Chronos;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;
use Illuminate\Database\Capsule\Manager as Capsule;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    protected $app;

    protected $chronos;

    public function setUp()
    {
        $events = new Dispatcher(new Container);

        $auth = Mockery::mock('Illuminate\Contracts\Auth\Factory');
        $auth->shouldReceive('check')->atLeast()->once()->andReturn(false);

        $config = new Config([
            'chronos' => require __DIR__.'/../src/Marquine/Chronos/config/chronos.php'
        ]);

        $config->set('chronos.loggable', [User::class => []]);

        $this->app = [
            'events' => $events,
            'auth' => $auth,
            'config' => $config,
        ];

        $this->chronos = new Chronos($this->app);

        $this->setUpDatabase($events);
        $this->migrateTables();
    }

    public function tearDown()
    {
        Mockery::close();
    }

    protected function setUpDatabase($events)
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $capsule->setEventDispatcher($events);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
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
            $table->string('loggable_type');
            $table->integer('loggable_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('event');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });
    }
}


use Marquine\Chronos\Activities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model {
    use SoftDeletes, Activities;

    protected $fillable = ['email', 'name'];
}

function config() {
    return Marquine\Chronos\Activity::class;
}
