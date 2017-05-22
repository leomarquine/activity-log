<?php

use Marquine\Chronos\Activity;

use Marquine\Chronos\Facades\Chronos;

class ChronosTest extends TestCase
{
    protected function createUser()
    {
        return User::create(['name' => 'Jane Doe', 'email' => 'janedoe@example.com']);
    }

    /** @test */
    function records_activity_when_creating_an_eloquent_model()
    {
        $this->createUser();

        $activity = Activity::first();

        $this->assertEquals('User', $activity->model_type);
        $this->assertEquals(1, $activity->model_id);
        $this->assertEquals('created', $activity->event);
        $this->assertNull($activity->before);
        $this->assertEquals(['name' => 'Jane Doe', 'email' => 'janedoe@example.com'], $activity->after);
    }

    /** @test */
    function pause_and_continue_log_registration()
    {
        $this->createUser();
        Chronos::pause();
        $this->createUser();
        Chronos::continue();
        $this->createUser();

        $this->assertEquals(2, Activity::count());
    }

    /** @test */
    function records_activity_when_updating_an_eloquent_model()
    {
        Chronos::pause();
        $user = $this->createUser();
        Chronos::continue();
        $user->update(['email' => 'janedoe@email.com']);

        $activity = Activity::first();

        $this->assertEquals('User', $activity->model_type);
        $this->assertEquals(1, $activity->model_id);
        $this->assertEquals('updated', $activity->event);
        $this->assertEquals(['name' => 'Jane Doe', 'email' => 'janedoe@example.com'], $activity->before);
        $this->assertEquals(['name' => 'Jane Doe', 'email' => 'janedoe@email.com'], $activity->after);
    }

    /** @test */
    function records_activity_when_deleting_an_eloquent_model()
    {
        Chronos::pause();
        $user = $this->createUser();
        Chronos::continue();
        $user->delete();

        $activity = Activity::first();

        $this->assertEquals('User', $activity->model_type);
        $this->assertEquals(1, $activity->model_id);
        $this->assertEquals('deleted', $activity->event);
        $this->assertEquals(['name' => 'Jane Doe', 'email' => 'janedoe@example.com'], $activity->before);
        $this->assertNull($activity->after);
    }

    /** @test */
    function records_activity_when_restoring_an_eloquent_model()
    {
        Chronos::pause();
        $user = $this->createUser();
        $user->delete();
        Chronos::continue();
        $user->restore();

        $activity = Activity::first();

        $this->assertEquals('User', $activity->model_type);
        $this->assertEquals(1, $activity->model_id);
        $this->assertEquals('restored', $activity->event);
        $this->assertNull($activity->before);
        $this->assertEquals(['name' => 'Jane Doe', 'email' => 'janedoe@example.com'], $activity->after);
    }


    /** @test */
    function merge_model_specific_configuration()
    {
        $this->app['config']->set('chronos.merge', [
            User::class => [
                'ignore' => ['email'],
            ],
        ]);

        $this->createUser();

        $activity = Activity::first();

        $this->assertEquals(['name' => 'Jane Doe'], $activity->after);
    }

    /** @test */
    function override_model_specific_configuration()
    {
        $this->app['config']->set('chronos.override', [
            User::class => [
                'ignore' => ['email'],
            ],
        ]);

        $this->createUser();

        $activity = Activity::first();

        $this->assertArrayHasKey('name', $activity->after);
        $this->assertArrayHasKey('created_at', $activity->after);
        $this->assertArrayHasKey('updated_at', $activity->after);
        $this->assertArrayNotHasKey('email', $activity->after);
    }
}
