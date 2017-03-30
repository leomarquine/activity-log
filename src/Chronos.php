<?php

namespace Marquine\Chronos;

class Chronos
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Indicates if logs should be saved.
     *
     * @var array
     */
    protected $isLogging = true;

    /**
     * Create a new Chronos instance.
     *
     * @param  \Illuminate\Foundation\Application|array  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;

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

        $this->app['events']->listen($events, function($event, $payload) {
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
        if ($before == $after) {
            return;
        }

        $activity = $this->activityModel();

        $activity->user_id = $this->getUserId();
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
        $class = '\\'.ltrim($this->config('model'), '\\');

        return new $class;
    }

    /**
     * Get the user's id if authenticated.
     *
     * @return mixed
     */
    protected function getUserId()
    {
        return $this->app['auth']->check() ? $this->app['auth']->id() : null;
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
    public function config($option, $model = null)
    {
        return $this->app['config']["chronos.loggable.$model.$option"]
               ?: $this->app['config']["chronos.$option"];
    }
}
