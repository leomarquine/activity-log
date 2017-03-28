<?php

use Marquine\Chronos\Activity;

class ChronosTest extends TestCase
{
    /** @test */
    function log_created_event()
    {
        User::create(['name' => 'Jane Doe', 'email' => 'janedoe@example.com']);

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
        User::create(['name' => 'Jane Doe', 'email' => 'janedoe@example.com']);
        $this->chronos->pause();
        User::create(['name' => 'User', 'email' => 'user@example.com']);
        $this->chronos->continue();
        User::create(['name' => 'John Doe', 'email' => 'johndoe@example.com']);

        $this->assertEquals(2, Activity::count());
    }
}
