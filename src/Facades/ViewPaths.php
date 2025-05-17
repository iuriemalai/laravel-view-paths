<?php

namespace IurieMalai\ViewPaths\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \IurieMalai\ViewPaths\ViewPaths
 */
class ViewPaths extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \IurieMalai\ViewPaths\ViewPaths::class;
    }
}
