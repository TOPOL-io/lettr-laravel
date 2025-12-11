<?php

namespace Lettr\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Lettr\Services\EmailService emails()
 *
 * @see \Lettr\Client
 */
class Lettr extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'lettr';
    }
}

