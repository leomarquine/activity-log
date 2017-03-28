<?php

namespace Marquine\Chronos;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Events\Dispatcher as Event;

class Chronos
{
    /**
     * The event instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $event;

    /**
     * Configuration array.
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new Chronos instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $event
     * @param  array  $config
     * @return void
     */
    public function __construct(Event $event, $config)
    {
        $this->event = $event;
        $this->config = $config;

        $this->registerListeners();
    }

    /**
     * Register eloquent event listeners.
     *
     * @return void
     */
    protected function registerListeners()
    {
        $events = ['eloquent.created: *', 'eloquent.updated: *', 'eloquent.deleted: *', 'eloquent.restored: *'];

        $this->event->listen($events, function($event, $payload) {
            preg_match('/(?:\.)(\w+)(?:\:)(?:\s)(.+$)/', $event, $match);

            list($match, $method, $model) = $match;
            list($instance) = $payload;

            $this->$method($model, $instance);
        });
    }

    /**
     * Log attributes for the "created" event.
     *
     * @param  string  $model
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    protected function created($model, $instance)
    {
        $after = $this->loggableAttributes($model, $instance->getAttributes());

        $this->log($instance, null, $after);
    }

    /**
     * Log attributes for the "updated" event.
     *
     * @param  string  $model
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    protected function updated($model, $instance)
    {
        $before = $this->loggableAttributes($model, $instance->getOriginal());

        $after = $this->loggableAttributes($model, $instance->getAttributes());

        $this->log($instance, $before, $after);
    }

    /**
     * Log attributes for the "deleted" event.
     *
     * @param  string  $model
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    protected function deleted($model, $instance)
    {
        $before = $this->loggableAttributes($model, $instance->getAttributes());

        $this->log($instance, $before, null);
    }

    /**
     * Log attributes for the "restored" event.
     *
     * @param  string  $model
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @return void
     */
    protected function restored($model, $instance)
    {
        $after = $this->loggableAttributes($model, $instance->getAttributes());

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
        $except = array_flip($this->config['ignore']);

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
        if ($before == $after) {
            return;
        }

        $activity = $this->activityModel();

        $activity->user_id = auth()->check() ? auth()->id() : null;
        $activity->loggable_id = $instance->getKey();
        $activity->loggable_type = get_class($instance);
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
        $class = '\\'.ltrim($this->config['model'], '\\');

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
}
