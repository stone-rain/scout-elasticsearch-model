<?php

namespace ScoutElasticModel\Indexers;


use Illuminate\Support\Collection;
use ScoutElasticModel\ElasticClient;
use ScoutElasticModel\Payloads\DocumentPayload;

class SingleIndexer implements IndexerInterface
{
    /**
     * {@inheritdoc}
     */
    public function update(Collection $models)
    {
        $models->each(function ($model) {

            $modelData = $model->toSearchableArray();

            if (empty($modelData)) {
                return true;
            }


            $payload = (new DocumentPayload($model))
                ->set('body', $modelData);


            if ($documentRefresh = config('elastic.document_refresh')) {
                $payload->set('refresh', $documentRefresh);
            }

            ElasticClient::index($payload->get());
        });
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Collection $models)
    {
        $models->each(function ($model) {
            $payload = new DocumentPayload($model);

            if ($documentRefresh = config('elastic.document_refresh')) {
                $payload->set('refresh', $documentRefresh);
            }

            $payload->set('client.ignore', 404);

            ElasticClient::delete($payload->get());
        });
    }

    public function client($model)
    {
        $payload = new DocumentPayload($model);

        ElasticClient::exists($payload->get());
    }
}
