<?php

use Marquine\Chronos\Chronos;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;
use Illuminate\Database\Capsule\Manager as Capsule;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    protected $chronos;

    public function setUp()
    {
        $events = new Dispatcher(new Container);

        $config = new Config([
            'chronos' => require __DIR__.'/../config/chronos.php'
        ]);

        $config->set('chronos.loggable', [User::class => []]);

        $this->chronos = new Chronos($events, $config);

        $this->setUpDatabase($events);
        $this->migrateTables();
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
use Marquine\Chronos\Concerns\HasActivities;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model {
    use SoftDeletes, HasActivities;

    protected $fillable = ['email', 'name'];
}

function config() {
    return Marquine\Chronos\Activity::class;
}

function auth() {
    return new class {
        public function id() {}
    };
}
