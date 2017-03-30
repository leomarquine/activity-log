<?php

namespace Marquine\Chronos\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Marquine\Chronos\Chronos
 */
class Chronos extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'chronos';
    }
}
