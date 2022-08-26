<?php

namespace ScoutElasticModel\Payloads;

use Exception;
use ScoutElasticModel\ElasticModel;
use ScoutElasticModel\Payloads\Features\HasProtectedKeys;

class IndexPayload extends RawPayload
{
    use HasProtectedKeys;

    /**
     * The protected keys.
     *
     * @var array
     */
    protected $protectedKeys = [
        'index',
    ];

    /**
     * The index configurator.
     *
     * @var \ScoutElasticModel\ElasticModel
     */
    protected $indexConfigurator;

    /**
     * IndexPayload constructor.
     *
     * @param  \ScoutElasticModel\ElasticModel  $indexConfigurator
     * @return void
     */
    public function __construct(ElasticModel $model)
    {
        $this->indexConfigurator = $model;

        $this->payload['index'] = $model->getTable()."_index";
    }

    /**
     * Use an alias.
     *
     * @param  string  $alias
     * @return $this
     * @throws \Exception
     */
    public function useAlias($alias)
    {
        $aliasGetter = 'get'.ucfirst($alias).'Alias';

        if (! method_exists($this->indexConfigurator, $aliasGetter)) {
            throw new Exception(sprintf(
                'The index configurator %s doesn\'t have getter for the %s alias.',
                get_class($this->indexConfigurator),
                $alias
            ));
        }

        $this->payload['index'] = call_user_func([$this->indexConfigurator, $aliasGetter]);

        return $this;
    }

    public function setUnions($value)
    {
        if (empty($value)) {
            return $this;
        }
        $index = is_array($this->payload['index']) ? $this->payload['index'] : [$this->payload['index']];

        $index = array_merge($index, $value);

        $this->payload['index'] = $index;

        return $this;
    }
}
