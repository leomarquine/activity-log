<?php

namespace Marquine\Chronos;

use Marquine\Chronos\Facades\Chronos;

trait RecordsActivities
{
    /**
     * Boot records activities trait.
     *
     * @return void
     */
    protected static function bootRecordsActivities()
    {
        Chronos::registerEventListeners(static::class);
    }

    /**
     * Get the model's activities.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function activities()
    {
        return $this->morphMany(Chronos::model(), 'model');
    }

    /**
     * Records the activity.
     *
     * @param  string  $event
     * @param  array|null  $before
     * @param  array|null  $after
     * @return void
     */
    public function recordActivity($event, $before, $after)
    {
        if ($before == $after || Chronos::paused()) {
            return;
        }

        $this->activities()->create([
            'event' => $event,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
