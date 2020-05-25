<?php

namespace Parsidev\Zibal\Facades;

use Illuminate\Support\Facades\Facade;

class Zibal extends Facade {

    protected static function getFacadeAccessor() {
        return 'zibal_parsidev';
    }
}
