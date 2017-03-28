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
}
