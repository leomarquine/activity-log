<?php

use Marquine\Chronos\Activity;

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
        app('chronos')->pause();
        $this->createUser();
        app('chronos')->continue();
        $this->createUser();

        $this->assertEquals(2, Activity::count());
    }

    /** @test */
    function records_activity_when_updating_an_eloquent_model()
    {
        app('chronos')->pause();
        $user = $this->createUser();
        app('chronos')->continue();
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
        app('chronos')->pause();
        $user = $this->createUser();
        app('chronos')->continue();
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
        app('chronos')->pause();
        $user = $this->createUser();
        $user->delete();
        app('chronos')->continue();
        $user->restore();

        $activity = Activity::first();

        $this->assertEquals('User', $activity->model_type);
        $this->assertEquals(1, $activity->model_id);
        $this->assertEquals('restored', $activity->event);
        $this->assertNull($activity->before);
        $this->assertEquals(['name' => 'Jane Doe', 'email' => 'janedoe@example.com'], $activity->after);
    }
}
