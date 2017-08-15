<?php

namespace southcoastweb\Helpers\Facades;

use Illuminate\Support\Facades\Facade;

class Useful extends Facade
{
    protected static function getFacadeAccessor()
    {
        die('here');
        return 'useful';
    }
}
