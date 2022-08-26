<?php

namespace ScoutElasticModel\Payloads;

use Exception;
use ScoutElasticModel\ElasticModel;

class DocumentPayload extends TypePayload
{
    /**
     * DocumentPayload constructor.
     *
     * @param  \ScoutElasticModel\ElasticModel  $model
     * @throws \Exception
     * @return void
     */
    public function __construct(ElasticModel $model)
    {
        if ($model->getKey() === null) {
            throw new Exception(sprintf(
                'The key value must be set to construct a payload for the %s instance.',
                get_class($model)
            ));
        }

        parent::__construct($model);

        $this->payload['id'] = $model->getKey();
        $this->protectedKeys[] = 'id';
    }
}
