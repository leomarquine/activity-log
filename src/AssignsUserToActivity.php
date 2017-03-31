<?php

namespace Marquine\Chronos;

trait AssignsUserToActivity
{
    /**
     * Boot trait.
     *
     * @return void
     */
    protected static function bootAssignsUserToActivity()
    {
        static::creating(function ($activity) {
            $activity->user_id = auth()->id();
        });
    }
}
