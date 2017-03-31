<?php

namespace Marquine\Chronos\Concerns;

trait HasActivities
{
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
