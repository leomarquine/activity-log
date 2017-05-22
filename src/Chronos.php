<?php

namespace Marquine\Chronos;

use Illuminate\Events\Dispatcher;
use Illuminate\Config\Repository;

class Chronos
{
    /**
     * The config instance.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Indicates if activity recording is paused.
     *
     * @var bool
     */
    protected $paused = false;

    /**
     * Create a new Chronos instance.
     *
     * @param  \Illuminate\Config\Repository  $config
     * @return void
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Pause recording.
     *
     * @return void
     */
    public function pause()
    {
        $this->paused = true;
    }

    /**
     * Continue recording.
     *
     * @return void
     */
    public function continue()
    {
        $this->paused = false;
    }

    /**
     * Indicates if activity recording is paused.
     *
     * @return bool
     */
    public function paused()
    {
        return $this->paused;
    }

    /**
     * Get the activity model name.
     *
     * @return string
     */
    public function model()
    {
        return $this->config('model');
    }

    /**
     * Record activity for the "created" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    public function created($instance)
    {
        $after = $this->recordableAttributes(get_class($instance), $instance->getAttributes());

        $instance->recordActivity('created', null, $after);
    }

    /**
     * Record activity for the "updated" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    public function updated($instance)
    {
        $before = $this->recordableAttributes(get_class($instance), $instance->getOriginal());

        $after = $this->recordableAttributes(get_class($instance), $instance->getAttributes());

        $instance->recordActivity('updated', $before, $after);
    }

    /**
     * Record activity for the "deleted" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    public function deleted($instance)
    {
        $before = $this->recordableAttributes(get_class($instance), $instance->getAttributes());

        $instance->recordActivity('deleted', $before, null);
    }

    /**
     * Record activity for the "restored" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    public function restored($instance)
    {
        $after = $this->recordableAttributes(get_class($instance), $instance->getAttributes());

        $instance->recordActivity('restored', null, $after);
    }

    /**
     * Filter model's recordable attributes.
     *
     * @param  string  $model
     * @param  array  $attributes
     * @return array
     */
    protected function recordableAttributes($model, $attributes)
    {
        $except = array_flip($this->config('ignore', $model));

        return array_diff_key($attributes, $except);
    }

    /**
     * Register model event listeners.
     *
     * @param  string  $model
     * @return void
     */
    public function registerEventListeners($model)
    {
        $activities = $this->config('activities', $model);

        foreach ($activities as $activity) {
            call_user_func("$model::$activity", function ($instance) use ($activity) {
                $this->{$activity}($instance);
            });
        }
    }

    /**
     * Get configuration option.
     *
     * @param  string  $option
     * @param  string|null  $model
     * @return mixed
     */
    protected function config($option, $model = null)
    {
        return $this->config["chronos.loggable.$model.$option"]
               ?: $this->config["chronos.$option"];
    }
}
