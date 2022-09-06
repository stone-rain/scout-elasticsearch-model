<?php

namespace ScoutElasticModel;

use ScoutElasticModel\ElasticModel as Model;
use ScoutElasticModel\Builders\Builder;
use ScoutElasticModel\Builders\SearchBuilder;
use ScoutElasticModel\Indexers\IndexerInterface;
use ScoutElasticModel\Payloads\IndexPayload;
use ScoutElasticModel\Payloads\TypePayload;
use stdClass;

class ElasticBuilder
{
    /**
     * The indexer interface.
     *
     * @var \ScoutElasticModel\Indexers\IndexerInterface
     */
    protected $indexer;

    /**
     * Should the mapping be updated.
     *
     * @var bool
     */
    protected $updateMapping;

    /**
     * The updated mappings.
     *
     * @var array
     */
    protected static $updatedMappings = [];

    /**
     * ElasticEngine constructor.
     *
     * @param  \ScoutElasticModel\Indexers\IndexerInterface  $indexer
     * @param  bool  $updateMapping
     * @return void
     */
    public function __construct(IndexerInterface $indexer, $updateMapping)
    {
        $this->indexer = $indexer;

        $this->updateMapping = $updateMapping;
    }

    /**
     * {@inheritdoc}
     */
    public function update($models)
    {
        if ($this->updateMapping) {
            $self = $this;

            $models->each(function ($model) use ($self) {
                $modelClass = get_class($model);

                if (in_array($modelClass, $self::$updatedMappings)) {
                    return true;
                }

//                Artisan::call(
//                    'elastic:update-mapping',
//                    ['model' => $modelClass]
//                );
//                $this->updateMapping($model);

                $self::$updatedMappings[] = $modelClass;
            });
        }

        $this
            ->indexer
            ->update($models);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($models)
    {
        $this->indexer->delete($models);
    }


    public function updateMapping($model)
    {

        $mapping = array_merge_recursive(
            $model->getMapping()
        );

        if (empty($mapping)) {
            throw new \Exception('Nothing to update: the mapping is not specified.');
        }

        $payload = (new TypePayload($model))
            ->set('body.'.$model->searchableAs(), $mapping)
            ->set('include_type_name', 'true');
//        $payload->useAlias('write');
        ElasticClient::indices()
            ->putMapping($payload->get());
//        dd($payload->get());
    }

    /**
     * Build the payload collection.
     *
     * @param  ScoutElasticModel\Builders\Builder  $builder
     * @param  array  $options
     * @return array
     */
    public function buildSearchQueryPayloadCollection(Builder $builder, array $options = [])
    {
        $payloadCollection = collect();

        if ($builder instanceof SearchBuilder) {
            $searchRules = $builder->rules ?: $builder->model->getSearchRules();

            foreach ($searchRules as $rule) {
                $payload = new TypePayload($builder->model);

                if (is_callable($rule)) {
                    $payload->setIfNotEmpty('body.query.bool', call_user_func($rule, $builder));
                } else {
                    /** @var SearchRule $ruleEntity */
                    $ruleEntity = new $rule($builder);

                    if ($ruleEntity->isApplicable()) {
                        $payload->setIfNotEmpty('body.query.bool', $ruleEntity->buildQueryPayload());

                        if ($options['highlight'] ?? true) {
                            $payload->setIfNotEmpty('body.highlight', $ruleEntity->buildHighlightPayload());
                        }
                    } else {
                        continue;
                    }
                }

                $payloadCollection->push($payload);
            }
        } else {
            $payload = (new TypePayload($builder->model))
                ->setIfNotEmpty('body.query.bool.must.match_all', new stdClass);

            $payloadCollection->push($payload);
        }

        return $payloadCollection->map(function (TypePayload $payload) use ($builder, $options) {
            $payload
                ->setIfNotEmpty('body._source', $builder->select)
                ->setIfNotEmpty('body.collapse.field', $builder->collapse)
                ->setIfNotEmpty('body.sort', $builder->orders)
                ->setIfNotEmpty('body.explain', $options['explain'] ?? null)
                ->setIfNotEmpty('body.profile', $options['profile'] ?? null)
                ->setIfNotEmpty('body.min_score', $builder->minScore)
                ->setIfNotNull('body.from', $builder->offset)
                ->setIfNotNull('body.size', $builder->limit);

            foreach ($builder->wheres as $clause => $filters) {
                $clauseKey = 'body.query.bool.filter.bool.'.$clause;

                $clauseValue = array_merge(
                    $payload->get($clauseKey, []),
                    $filters
                );

                $payload->setIfNotEmpty($clauseKey, $clauseValue);
            }

            $payload->setIfNotEmpty('body.aggs',$builder->group);

            $payload->setUnions($builder->union);

            return $payload->get();
        });
    }

    /**
     * Perform the search.
     *
     * @param  ScoutElasticModel\Builders\Builder  $builder
     * @param  array  $options
     * @return array|mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                ElasticClient::getFacadeRoot(),
                $builder->query,
                $options
            );
        }

        $results = [];

        $this
            ->buildSearchQueryPayloadCollection($builder, $options)
            ->each(function ($payload) use (&$results) {
                $results = ElasticClient::search($payload);

                $results['_payload'] = $payload;

                if ($this->getTotalCount($results) > 0) {
                    return false;
                }
            });

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $builder
            ->from(($page - 1) * $perPage)
            ->take($perPage);

        return $this->performSearch($builder);
    }

    /**
     * Explain the search.
     *
     * @param  ScoutElasticModel\Builders\Builder  $builder
     * @return array|mixed
     */
    public function explain(Builder $builder)
    {
        return $this->performSearch($builder, [
            'explain' => true,
        ]);
    }

    /**
     * Profile the search.
     *
     * @param  ScoutElasticModel\Builders\Builder  $builder
     * @return array|mixed
     */
    public function profile(Builder $builder)
    {
        return $this->performSearch($builder, [
            'profile' => true,
        ]);
    }

    /**
     * Return the number of documents found.
     *
     * @param  ScoutElasticModel\Builders\Builder  $builder
     * @return int
     */
    public function count(Builder $builder)
    {
        $count = 0;

        $this
            ->buildSearchQueryPayloadCollection($builder, ['highlight' => false])
            ->each(function ($payload) use (&$count) {
                $result = ElasticClient::count($payload);

                $count = $result['count'];

                if ($count > 0) {
                    return false;
                }
            });

        return $count;
    }

    /**
     * Make a raw search.
     *
     * @param  Model  $model
     * @param  array  $query
     * @return mixed
     */
    public function searchRaw(Model $model, $query)
    {
        $payload = (new TypePayload($model))
            ->setIfNotEmpty('body', $query)
            ->get();

        return ElasticClient::search($payload);
    }

    /**
     * {@inheritdoc}
     */
    public function mapIds($results)
    {
        return collect($results['hits']['hits'])->pluck('_id');
    }

    /**
     * {@inheritdoc}
     */
    public function map(Builder $builder, $results, $model)
    {
        if ($this->getTotalCount($results) === 0) {
            return [];
        }

        if (!empty($builder->group)) {
            foreach ($builder->group as $key=>$val){
                return  \ScoutElasticModel\Support\Arr::get($results, 'aggregations.'.$key.'.buckets');
            }
        }

        return collect($results['hits']['hits'])
            ->map(function ($hit) use($model, $builder){
                if($builder->union){
                    $hit['_source']['_index'] = $hit['_index'];
                }
                if($model->callbackModel){
                    return $model->callbackModel->newFromBuilder($model->newFromBuilder($hit['_source'])->toArray($model->getCasts()));
                }
                return $model->newFromBuilder($hit['_source']);
            })
            ->filter()
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount($results)
    {
        return $results['hits']['total']['value'] ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function flush($model)
    {
        $query = $model::usesSoftDelete() ? $model->withTrashed() : $model->newQuery();

        $query
            ->orderBy($model->getScoutKeyName())
            ->unsearchable();
    }

    public function get(Builder $builder)
    {

        return $this->map(
            $builder, $this->search($builder), $builder->model
        );
    }

    public function exists(Builder $builder)
    {
        $payload = (new IndexPayload($builder->model));

        try {
            $return = ElasticClient::indices()->exists($payload->get());
            if(!$return){
                $this->createTargetIndex($builder->model, $payload);
                $this->updateMapping($builder->model);
            }
            return $return;
        } catch (\ErrorException $e) {
            return false;
        }

    }

    /**
     * Create a target index.
     *
     * @param $model
     * @param $payload
     * @return void
     */
    protected function createTargetIndex($model, $payload)
    {
        $payload->setIfNotEmpty('body.settings', $model->getSettings());

        ElasticClient::indices()
            ->create($payload->get());
    }
}
