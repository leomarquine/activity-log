<?php

namespace Marquine\Chronos\Concerns;

use Marquine\Chronos\Diff\Diff;

trait InteractsWithActivities
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

    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillable()
    {
        $this->fillable = array_merge(
            $this->fillable, ['event', 'before', 'after']
        );

        return $this->fillable;
    }
}
