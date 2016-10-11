<?php

namespace Marquine\ActivityLog\Diff;

use cogpowered\FineDiff\Diff as Differ;

class Diff
{
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
     * Indicates if the output is raw.
     *
     * @var bool
     */
    protected $raw;

    /**
     * Granularity type.
     *
     * @var string
     */
    protected $granularity;

    /**
     * Create a new Diff instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $activity
     * @return void
     */
    protected function __construct($activity)
    {
        $model = new $activity->loggable_type;

        $this->raw = $this->getConfig($model, 'raw');

        $this->granularity = $this->getConfig($model, 'granularity');

        $this->before = $this->getData($model, (array) $activity->before);

        $this->after = $this->getData($model, (array) $activity->after);
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

        return $instance->{$activity->event}();
    }

    /**
     * Get the propper config value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $config
     * @return mixed
     */
    protected function getConfig($model, $config)
    {
        $property = 'diff'.ucfirst($config);

        return $model->{$property}() !== null
                    ? $model->{$property}()
                    : config("activity.diff.$config");
    }

    /**
     * Get the activity data.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $data
     * @return array
     */
    protected function getData($model, $data)
    {
        if (! $this->raw) {
            $model->unguard();

            $data = $model->fill($data)->attributesToArray();

            $model->reguard();
        }

        return $data;
    }

    /**
     * Get the diff for the "created" event.
     *
     * @return array
     */
    protected function created()
    {
        $result = [];

        foreach ($this->after as $key => $value) {
            $result[] = (object) [
                'key' => $key,
                'value' => $value,
                'type' => empty($value) ? 'equal' : 'insert',
            ];
        }

        return $result;
    }

    /**
     * Get the diff for the "updated" event.
     *
     * @return array
     */
    protected function updated()
    {
        $result = [];

        $diff = new Differ();

        $diff->setGranularity($this->granularity());

        foreach ($this->after as $key => $value) {
            if ($this->before[$key] == $this->after[$key]) {
                $result[] = (object) [
                    'key' => $key,
                    'value' => $value,
                    'type' => 'equal',
                ];

                continue;
            }

            $diff->setRenderer(new Renderers\Delete);

            $result[] = (object) [
                'key' => $key,
                'value' => $diff->render($this->before[$key], $this->after[$key]),
                'type' => 'delete',
            ];

            $diff->setRenderer(new Renderers\Insert);

            $result[] = (object) [
                'key' => $key,
                'value' => $diff->render($this->before[$key], $this->after[$key]),
                'type' => 'insert',
            ];
        }

        return $result;
    }

    /**
     * Get the diff for the "deleted" event.
     *
     * @return array
     */
    protected function deleted()
    {
        $result = [];

        foreach ($before as $key => $value) {
            $result[] = (object) [
                'key' => $key,
                'value' => $value,
                'type' => empty($value) ? 'equal' : 'delete',
            ];
        }

        return $result;
    }

    /**
     * Get the diff for the "restored" event.
     *
     * @return array
     */
    protected function restored()
    {
        return $this->created();
    }

    /**
     * Get the granularity.
     *
     * @return \cogpowered\FineDiff\Granularity\Granularity
     */
    protected function granularity()
    {
        switch ($this->granularity) {
            case 'character':
                return new \cogpowered\FineDiff\Granularity\Character;
            case 'word':
                return new \cogpowered\FineDiff\Granularity\Word;
            case 'sentence':
                return new \cogpowered\FineDiff\Granularity\Sentence;
            case 'paragraph':
                return new \cogpowered\FineDiff\Granularity\Paragraph;
            default:
                return new \cogpowered\FineDiff\Granularity\Word;
        }
    }
}
