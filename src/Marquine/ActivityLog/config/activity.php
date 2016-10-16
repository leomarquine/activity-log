<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activity Model
    |--------------------------------------------------------------------------
    |
    | The model for the activities table.
    | Needs to use the \Marquine\ActivityLog\ActivityLog Trait.
    */

    'model' => App\Activity::class,

    /*
    |--------------------------------------------------------------------------
    | Activity Diff
    |--------------------------------------------------------------------------
    |
    | To show the activity, we will calculate the difference between the state
    | of the model before and after the activity. A Raw output will display
    | data as it was saved, and will not go through mutators, casts, etc.
    | The granularity determines the smallest text portion to compare.
    |
    | Supported granularities: "character", "word", "sentence", "paragraph"
    |
    */

    'diff' => [
        'raw' => true,
        'granularity' => 'word'
        'hidden' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Log
    |--------------------------------------------------------------------------
    |
    | Next, you can define the log options. The attributes in the except
    | option will not be logged in the activities of a loggable model.
    */

    'log' => [
        'except' => [
            'created_at',
            'updated_at',
            'deleted_at',
        ],
    ],

];
