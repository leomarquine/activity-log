<?php

namespace Marquine\Chronos\Diff;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use cogpowered\FineDiff\Diff as Differ;

class Diff
{
    /**
     * Logged activity.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $activity;

    /**
     * Differ.
     *
     * @var \cogpowered\FineDiff\Diff
     */
    protected $differ;

    /**
     * The data before the activity.
     *
     * @var array
     */
    protected $before;

    /**
     * The data after the activity.
     *
     * @var array
     */
    protected $after;

    /**
     * Create a new Diff instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $activity
     * @param  array  $config
     * @return void
     */
    public function __construct(Model $activity, $config)
    {
        $this->activity = $activity;

        $this->config = $config;

        $this->differ = new Differ($this->granularity());
    }

    /**
     * Make an activity diff.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $activity
     * @param  array  $config
     * @return array
     */
    public static function make($activity, $config)
    {
        $instance = new static($activity, $config);

        return $instance->diff();
    }

    /**
     * Get the activity data.
     *
     * @param  array  $data
     * @return array
     */
    protected function parseData($data)
    {
        $instance = new $this->activity->model_type;

        $data = (array) $data;

        if (! $this->config('diff.raw', $this->activity->model_type)) {
            $instance->unguard();

            $data = $instance->fill($data)->attributesToArray();

            $instance->reguard();
        }

        if (! $this->config('diff.hidden', $this->activity->model_type)) {
            $data = $this->removeHiddenAttributes($instance, $data);
        }

        return $data;
    }

    /**
     * Remove model's hidden attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $instance
     * @param  array  $data
     * @return array
     */
    protected function removeHiddenAttributes($instance, $data)
    {
        $hidden = $instance->getHidden();

        return array_diff_key($data, array_flip($hidden));
    }

    /**
     * Get the data before the activity.
     *
     * @param  string|null  $key
     * @return mixed
     */
    protected function before($key = null)
    {
        if (! $this->before) {
            $this->before = $this->parseData($this->activity->before);
        }

        if (! $key) {
            return array_keys($this->before);
        }

        if (! isset($this->before[$key]) || $this->before[$key] === '') {
            return null;
        }

        return $this->before[$key];
    }

    /**
     * Get the data after the activity.
     *
     * @param  string|null  $key
     * @return mixed
     */
    protected function after($key = null)
    {
        if (! $this->after) {
            $this->after = $this->parseData($this->activity->after);
        }

        if (! $key) {
            return array_keys($this->after);
        }

        if (! isset($this->after[$key]) || $this->after[$key] === '') {
            return null;
        }

        return $this->after[$key];
    }

    /**
     * Get diff keys.
     *
     * @return array
     */
    protected function keys()
    {
        if (count($this->before()) > count($this->after())) {
            return $this->before();
        }

        return $this->after();
    }

    /**
     * Get the diff for the model.
     *
     * @return array
     */
    public function diff()
    {
        $result = [];

        foreach ($this->keys() as $key) {
            if ($diff = $this->equal($key)) {
                $result[] = $diff; continue;
            }

            if ($diff = $this->delete($key)) {
                $result[] = $diff;
            }

            if ($diff = $this->insert($key)) {
                $result[] = $diff;
            }
        }

        return $result;
    }

    /**
     * Get equal attribute object.
     *
     * @param  string  $key
     * @return \stdClass
     */
    protected function equal($key)
    {
        if ($this->before($key) !== $this->after($key)) {
            return false;
        }

        $diff = [
            'key' => $key,
            'value' => $this->before($key),
            'type' => 'equal',
        ];

        return (object) $diff;
    }

    /**
     * Get delete attribute object.
     *
     * @param  string  $key
     * @return \stdClass
     */
    protected function delete($key)
    {
        if ($this->before($key) === null || $this->before($key) === '') {
            return false;
        }

        $this->differ->setRenderer(new Renderers\Delete);

        $value = $this->after($key)
                    ? $this->differ->render($this->before($key), $this->after($key))
                    : $this->before($key);

        $diff = [
            'key' => $key,
            'value' => $value,
            'type' => 'delete',
        ];

        return (object) $diff;
    }

    /**
     * Get insert attribute object.
     *
     * @param  string  $key
     * @return \stdClass
     */
    protected function insert($key)
    {
        if ($this->after($key) === null || $this->after($key) === '') {
            return false;
        }

        $this->differ->setRenderer(new Renderers\Insert);

        $value = $this->before($key)
                    ? $this->differ->render($this->before($key), $this->after($key))
                    : $this->after($key);

        $diff = [
            'key' => $key,
            'value' => $value,
            'type' => 'insert',
        ];

        return (object) $diff;
    }

    /**
     * Get the granularity.
     *
     * @return \cogpowered\FineDiff\Granularity\Granularity
     *
     * @throws \InvalidArgumentException
     */
    protected function granularity()
    {
        $granularity = $this->config('diff.granularity', $this->activity->model_type);

        switch ($granularity) {
            case 'character':
                return new \cogpowered\FineDiff\Granularity\Character;
            case 'word':
                return new \cogpowered\FineDiff\Granularity\Word;
            case 'sentence':
                return new \cogpowered\FineDiff\Granularity\Sentence;
            case 'paragraph':
                return new \cogpowered\FineDiff\Granularity\Paragraph;
        }

        throw new InvalidArgumentException("The '$granularity' granularity is not valid.");
    }

    /**
     * Get configuration option.
     *
     * @param  string  $option
     * @param  string  $model
     * @return mixed
     */
    protected function config($option, $model)
    {
        return Arr::get($this->config, "loggable.$model.$option")
               ?: Arr::get($this->config, $option);
    }
}
