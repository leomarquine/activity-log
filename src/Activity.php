<?php

namespace Marquine\Chronos;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasActivities, AssignsUserToActivity;
}
