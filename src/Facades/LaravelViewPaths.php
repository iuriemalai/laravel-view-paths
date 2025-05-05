<?php

namespace IurieMalai\LaravelViewPaths\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \IurieMalai\LaravelViewPaths\LaravelViewPaths
 */
class LaravelViewPaths extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \IurieMalai\LaravelViewPaths\LaravelViewPaths::class;
    }
}
