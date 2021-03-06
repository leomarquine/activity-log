<?php

return [

    'model' => Marquine\Chronos\Activity::class,

    'table' => 'activities',

    'activities' => ['created', 'updated', 'deleted', 'restored'],

    'diff' => [
        'raw' => true,
        'granularity' => 'word',
        'hidden' => false,
    ],

    'ignore' => ['id', 'created_at', 'updated_at', 'deleted_at'],

    'merge' => [
        App\User::class => ['ignore' => ['password', 'remember_token']],
    ],

    'override' => [
        // App\Model::class => [],
    ],

];
