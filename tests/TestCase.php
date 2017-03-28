<?php

use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $event = new Dispatcher(new Container);

        $this->setUpDatabase($event);
        $this->migrateTables();
    }

    protected function setUpDatabase($event)
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $capsule->setEventDispatcher($event);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    protected function migrateTables()
    {
        Capsule::schema()->create('users', function($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
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

class User extends \Illuminate\Database\Eloquent\Model {
    protected $fillable = ['email', 'name'];
}
