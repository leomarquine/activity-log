<?php

namespace Marquine\Chronos;

use Marquine\Chronos\Diff\Diff;

trait ActivityLog
{
    /**
     * Get all of the owning loggable models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function loggable()
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
        return Diff::make($this);
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
