<?php

namespace ScoutElasticModel\Payloads;

use Exception;
use ScoutElasticModel\ElasticModel;

class TypePayload extends IndexPayload
{
    /**
     * The model.
     *
     * @var \ScoutElasticModel\ElasticModel
     */
    protected $model;

    /**
     * TypePayload constructor.
     *
     * @param  \ScoutElasticModel\ElasticModel  $model
     * @throws \Exception
     * @return void
     */
    public function __construct(ElasticModel $model)
    {

        $this->model = $model;

        parent::__construct($model);

        $this->payload['type'] = $model->searchableAs();
        $this->protectedKeys[] = 'type';
//        $this->payload['client']['curl'][CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
    }
}
