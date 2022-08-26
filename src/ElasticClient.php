<?php

namespace ScoutElasticModel;

use Illuminate\Support\Facades\Facade;

class ElasticClient extends Facade
{
    /**
     * Get the facade.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'elastic.client';
    }
}
