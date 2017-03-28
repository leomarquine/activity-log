<?php

use Marquine\Chronos\Activity;

class ChronosTest extends TestCase
{
    protected function createUser()
    {
        return User::create(['name' => 'Jane Doe', 'email' => 'janedoe@example.com']);
    }

    /** @test */
    function log_created_event()
    {
        $this->createUser();

        $activity = Activity::first();

        $this->assertEquals('User', $activity->loggable_type);
        $this->assertEquals(1, $activity->loggable_id);
        $this->assertEquals('created', $activity->event);
        $this->assertNull($activity->before);
        $this->assertEquals(['name' => 'Jane Doe', 'email' => 'janedoe@example.com'], $activity->after);
    }

    /** @test */
    function pause_and_continue_log_registration()
    {
        $this->createUser();
        $this->chronos->pause();
        $this->createUser();
        $this->chronos->continue();
        $this->createUser();

        $this->assertEquals(2, Activity::count());
    }

    /** @test */
    function log_updated_event()
    {
        $this->chronos->pause();
        $user = $this->createUser();
        $this->chronos->continue();
        $user->update(['email' => 'janedoe@email.com']);

        $activity = Activity::first();

        $this->assertEquals('User', $activity->loggable_type);
        $this->assertEquals(1, $activity->loggable_id);
        $this->assertEquals('updated', $activity->event);
        $this->assertEquals(['name' => 'Jane Doe', 'email' => 'janedoe@example.com'], $activity->before);
        $this->assertEquals(['name' => 'Jane Doe', 'email' => 'janedoe@email.com'], $activity->after);
    }

    /** @test */
    function log_deleted_event()
    {
        $this->chronos->pause();
        $user = $this->createUser();
        $this->chronos->continue();
        $user->delete();

        $activity = Activity::first();

        $this->assertEquals('User', $activity->loggable_type);
        $this->assertEquals(1, $activity->loggable_id);
        $this->assertEquals('deleted', $activity->event);
        $this->assertEquals(['name' => 'Jane Doe', 'email' => 'janedoe@example.com'], $activity->before);
        $this->assertNull($activity->after);
    }

    /** @test */
    function log_restored_event()
    {
        $this->chronos->pause();
        $user = $this->createUser();
        $user->delete();
        $this->chronos->continue();
        $user->restore();

        $activity = Activity::first();

        $this->assertEquals('User', $activity->loggable_type);
        $this->assertEquals(1, $activity->loggable_id);
        $this->assertEquals('restored', $activity->event);
        $this->assertNull($activity->before);
        $this->assertEquals(['name' => 'Jane Doe', 'email' => 'janedoe@example.com'], $activity->after);
    }
}
