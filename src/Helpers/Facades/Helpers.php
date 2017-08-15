<?php

namespace southcoastweb\Helpers\Facades;

use Illuminate\Support\Facades\Facade as BaseFacade;

class Helpers extends BaseFacade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'mpdf.wrapper'; }
}
