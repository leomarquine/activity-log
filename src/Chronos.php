<?php

namespace Marquine\Chronos;

use Illuminate\Events\Dispatcher;
use Illuminate\Config\Repository;

class Chronos
{
    /**
     * The events instance.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected $events;

    /**
     * The config instance.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Indicates if logs should be saved.
     *
     * @var array
     */
    protected $isLogging = true;

    /**
     * Create a new Chronos instance.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @param  \Illuminate\Config\Repository  $config
     * @return void
     */
    public function __construct(Dispatcher $events, Repository $config)
    {
        $this->events = $events;
        $this->config = $config;

        // $this->registerListeners();
    }

    /**
     * Register eloquent event listeners.
     *
     * @return void
     */
    protected function registerListeners()
    {
        $events = ['eloquent.created: *', 'eloquent.updated: *', 'eloquent.deleted: *', 'eloquent.restored: *'];

        $this->events->listen($events, function($event, $payload) {
            preg_match('/(?:\.)(\w+)(?:\:)(?:\s)(.+$)/', $event, $match);

            list($match, $method, $model) = $match;
            list($instance) = $payload;

            if ($this->shouldLog($model)) {
                $this->$method($model, $instance);
            }
        });
    }

    /**
     * Determine if the model should be logged.
     *
     * @param  string  $model
     * @return bool
     */
    protected function shouldLog($model)
    {
        if (! $this->isLogging) {
            return false;
        }

        if ($model == $this->config('model')) {
            return false;
        }

        if ($this->config("loggable.$model") === false) {
            return false;
        }

        if ($this->config('scope') == 'loggable') {
            return $this->config("loggable.$model") !== null;
        }

        return true;
    }

    /**
     * Pause logging.
     *
     * @return void
     */
    public function pause()
    {
        $this->isLogging = false;
    }

    /**
     * Continue logging.
     *
     * @return void
     */
    public function continue()
    {
        $this->isLogging = true;
    }

    /**
     * Log attributes for the "created" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    public function created($instance)
    {
        $after = $this->loggableAttributes(get_class($instance), $instance->getAttributes());

        $this->log($instance, null, $after);
    }

    /**
     * Log attributes for the "updated" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    public function updated($instance)
    {
        $before = $this->loggableAttributes(get_class($instance), $instance->getOriginal());

        $after = $this->loggableAttributes(get_class($instance), $instance->getAttributes());

        $this->log($instance, $before, $after);
    }

    /**
     * Log attributes for the "deleted" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    public function deleted($instance)
    {
        $before = $this->loggableAttributes(get_class($instance), $instance->getAttributes());

        $this->log($instance, $before, null);
    }

    /**
     * Log attributes for the "restored" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    public function restored($instance)
    {
        $after = $this->loggableAttributes(get_class($instance), $instance->getAttributes());

        $this->log($instance, null, $after);
    }

    /**
     * Filter model's loggable attributes.
     *
     * @param  string  $model
     * @param  array  $attributes
     * @return array
     */
    protected function loggableAttributes($model, $attributes)
    {
        $except = array_flip($this->config('ignore', $model));

        return array_diff_key($attributes, $except);
    }

    /**
     * Save the activity log.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @param  array|null  $before
     * @param  array|null  $after
     * @return void
     */
    public function log($instance, $before, $after)
    {
        if ($before == $after || ! $this->isLogging) {
            return;
        }

        $activity = $this->activityModel();

        $activity->model_id = $instance->getKey();
        $activity->model_type = get_class($instance);
        $activity->event = $this->guessEventName($instance);
        $activity->before = $before;
        $activity->after = $after;

        $activity->save();
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function activityModel()
    {
        $class = '\\'.ltrim($this->config('model'), '\\');

        return new $class;
    }

    /**
     * Guesses the event's name.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return string
     */
    protected function guessEventName($instance)
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[2];

        if ($caller['class'] == get_class($this)) {
            return $caller['function'];
        }

        if ($instance->wasRecentlyCreated) {
            return 'created';
        }

        if (! $instance->exists) {
            return 'deleted';
        }

        if ($instance->isDirty()) {
            return 'updated';
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
