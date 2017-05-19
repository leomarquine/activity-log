<?php

return [

    'model' => Marquine\Chronos\Activity::class,

    'table' => 'activities',

    'diff' => [
        'raw' => true,
        'granularity' => 'word',
        'hidden' => false,
    ],

    'ignore' => ['id', 'created_at', 'updated_at', 'deleted_at'],

];
