<?php

namespace Marquine\Chronos;

use Marquine\Chronos\Diff\Diff;

trait LogActivities
{
    /**
     * Get the activity model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Get the diff for the activity.
     *
     * @return array
     */
    public function getDiffAttribute()
    {
        return Diff::make($this, config('chronos'));
    }

    /**
     * Get the casts array.
     *
     * @return array
     */
    public function getCasts()
    {
        $this->casts['before'] = 'array';
        $this->casts['after'] = 'array';

        return parent::getCasts();
    }
}
