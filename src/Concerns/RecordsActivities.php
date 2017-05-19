<?php

namespace Marquine\Chronos\Concerns;

trait RecordsActivities
{
    /**
     * Boot records activities trait.
     *
     * @return void
     */
    protected static function bootRecordsActivities()
    {
        static::created(function ($instance) {
            app('chronos')->created($instance);
        });

        static::updated(function ($instance) {
            app('chronos')->updated($instance);
        });

        static::deleted(function ($instance) {
            app('chronos')->deleted($instance);
        });

        static::restored(function ($instance) {
            app('chronos')->restored($instance);
        });
    }

    /**
     * Get the model's activities.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function activities()
    {
        return $this->morphMany(config('chronos.model'), 'model');
    }
}
