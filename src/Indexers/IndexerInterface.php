<?php

namespace ScoutElasticModel\Indexers;

use Illuminate\Support\Collection;

interface IndexerInterface
{
    /**
     * Update documents.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @return array
     */
    public function update(Collection $models);

    /**
     * Delete documents.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @return array
     */
    public function delete(Collection $models);
}
