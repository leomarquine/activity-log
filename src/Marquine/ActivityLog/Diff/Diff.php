<?php

namespace Marquine\ActivityLog\Diff;

use InvalidArgumentException;
use cogpowered\FineDiff\Diff as Differ;

class Diff
{
    /**
     * Model to make the diff.
     *
     * @var array
     */
    protected $model;

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
     *  Differ.
     *
     * @var \cogpowered\FineDiff\Diff
     */
    protected $diff;

    /**
     * Create a new Diff instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $activity
     * @return void
     */
    protected function __construct($activity)
    {
        $this->model = new $activity->loggable_type;

        $this->before = $this->data($activity->before);

        $this->after = $this->data($activity->after);

        $this->differ = new Differ($this->granularity());
    }

    /**
     * Make an activity diff.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $activity
     * @return array
     */
    public static function make($activity)
    {
        $instance = new static($activity);

        return $instance->diff();
    }

    /**
     * Get the activity data.
     *
     * @param  array  $data
     * @return array
     */
    protected function data($data)
    {
        $class = get_class($this->model);

        $model = new $class;

        if (! $model->diffRaw()) {
            $model->unguard();

            $data = $model->fill($data)->attributesToArray();

            $model->reguard();
        }

        return $data;
    }

    /**
     * Get diff keys.
     *
     * @return array
     */
    protected function keys()
    {
        if (count($this->before) > count($this->after)) {
            return array_keys($this->before);
        }

        return array_keys($this->after);
    }

    /**
     * Get the diff for the model.
     *
     * @return array
     */
    protected function diff()
    {
        $result = [];

        foreach ($this->keys() as $key) {
            if ($diff = $this->equal($key)) {
                $result[] = $diff; continue;
            }

            if ($item = $this->delete($key)) {
                $result[] = $item;
            }

            if ($item = $this->insert($key)) {
                $result[] = $item;
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
        if ($this->before[$key] !== $this->after[$key]) {
            return false;
        }

        $diff = [
            'key' => $key,
            'value' => $this->before[$key],
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
        if ($this->before[$key] === null) {
            return false;
        }

        $this->differ->setRenderer(new Renderers\Delete);

        $value = $this->after[$key]
                    ? $this->differ->render($this->before[$key], $this->after[$key])
                    : $this->before[$key];

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
        if ($this->after[$key] === null) {
            return false;
        }

        $this->differ->setRenderer(new Renderers\Insert);

        $value = $this->before[$key]
                    ? $this->differ->render($this->before[$key], $this->after[$key])
                    : $this->after[$key];

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
        $granularity = $this->model->diffGranularity();

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
}
