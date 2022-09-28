<?php

namespace ScoutElasticModel\Builders;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use ScoutElasticModel\ElasticModel;
use ScoutElasticModel\Support\Arr;

class FilterBuilder extends Builder
{
    /**
     * The condition array.
     *
     * @var array
     */
    public $wheres = [
        'must'     => [],
        'must_not' => [],
    ];

    /**
     * The with array.
     *
     * @var array|string
     */
    public $with;

    /**
     * The offset.
     *
     * @var int
     */
    public $offset;

    /**
     * The collapse parameter.
     *
     * @var string
     */
    public $collapse;

    /**
     * The select array.
     *
     * @var array
     */
    public $select = [];

    public $group = [];

    public $union = [];

    /**
     * The min_score parameter.
     *
     * @var string
     */
    public $minScore;

    /**
     * FilterBuilder constructor.
     *
     * @param \ScoutElasticModel\ElasticModel $model
     * @param callable|null $callback
     * @param bool $softDelete
     * @return void
     */
    public function __construct(ElasticModel $model, $callback = null, $softDelete = false)
    {
        $this->model = $model;
        $this->callback = $callback;

        if ($softDelete) {
            $this->wheres['must'][] = [
                'term' => [
                    '__soft_deleted' => 0,
                ],
            ];
        }
    }

    /**
     * Add a where condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html Term query
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-range-query.html Range query
     *
     * Supported operators are =, &gt;, &lt;, &gt;=, &lt;=, &lt;&gt;
     * @param string $field Field name
     * @param mixed $value Scalar value or an array
     * @return $this
     */
    public function where($field, $operator = null, $value = null)
    {
        $args = func_get_args();

        if (count($args) === 2) {
            $value = $operator;
            $operator = '=';
        }

        switch ($operator) {
            case '=':
                $this->wheres['must'][] = [
                    'term' => [
                        $field => $value,
                    ],
                ];
                break;

            case '>':
                $this->wheres['must'][] = [
                    'range' => [
                        $field => [
                            'gt' => $value,
                        ],
                    ],
                ];
                break;

            case '<':
                $this->wheres['must'][] = [
                    'range' => [
                        $field => [
                            'lt' => $value,
                        ],
                    ],
                ];
                break;

            case '>=':
                $this->wheres['must'][] = [
                    'range' => [
                        $field => [
                            'gte' => $value,
                        ],
                    ],
                ];
                break;

            case '<=':
                $this->wheres['must'][] = [
                    'range' => [
                        $field => [
                            'lte' => $value,
                        ],
                    ],
                ];
                break;

            case '!=':
            case '<>':
                $this->wheres['must_not'][] = [
                    'term' => [
                        $field => $value,
                    ],
                ];
                break;
        }

        return $this;
    }

    /**
     * Add a whereIn condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html Terms query
     *
     * @param string $field
     * @param array $value
     * @return $this
     */
    public function whereIn($field, array $value)
    {
        $this->wheres['must'][] = [
            'terms' => [
                $field => $value,
            ],
        ];

        return $this;
    }

    /**
     * Add a whereNotIn condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html Terms query
     *
     * @param string $field
     * @param array $value
     * @return $this
     */
    public function whereNotIn($field, array $value)
    {
        $this->wheres['must_not'][] = [
            'terms' => [
                $field => $value,
            ],
        ];

        return $this;
    }

    /**
     * Add a whereBetween condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-range-query.html Range query
     *
     * @param string $field
     * @param array $value
     * @return $this
     */
    public function whereBetween($field, array $value)
    {
        $this->wheres['must'][] = [
            'range' => [
                $field => [
                    'gte' => $value[0],
                    'lte' => $value[1],
                ],
            ],
        ];

        return $this;
    }

    /**
     * Add a whereNotBetween condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-range-query.html Range query
     *
     * @param string $field
     * @param array $value
     * @return $this
     */
    public function whereNotBetween($field, array $value)
    {
        $this->wheres['must_not'][] = [
            'range' => [
                $field => [
                    'gte' => $value[0],
                    'lte' => $value[1],
                ],
            ],
        ];

        return $this;
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     */
    public function whereDate($field, $value)
    {
        $this->whereBetween($field, [$value, $value . '||/d']);

        return $this;
    }

    /**
     * Add a whereExists condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-exists-query.html Exists query
     *
     * @param string $field
     * @return $this
     */
    public function whereExists($field)
    {
        $this->wheres['must'][] = [
            'exists' => [
                'field' => $field,
            ],
        ];

        return $this;
    }

    /**
     * Add a whereNotExists condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-exists-query.html Exists query
     *
     * @param string $field
     * @return $this
     */
    public function whereNotExists($field)
    {
        $this->wheres['must_not'][] = [
            'exists' => [
                'field' => $field,
            ],
        ];

        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html Match query
     *
     * @param string $field
     * @param string $value
     * @return $this
     */
    public function whereMatch($field, $value)
    {
        $this->wheres['must'][] = [
            'match' => [
                $field => $value,
            ],
        ];

        return $this;
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html Match query
     *
     * @param string $field
     * @param string $value
     * @return $this
     */
    public function whereNotMatch($field, $value)
    {
        $this->wheres['must_not'][] = [
            'match' => [
                $field => $value,
            ],
        ];

        return $this;
    }

    /**
     * Add a whereRegexp condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-regexp-query.html Regexp query
     *
     * @param string $field
     * @param string $value
     * @param string $flags
     * @return $this
     */
    public function whereRegexp($field, $value, $flags = 'ALL')
    {
        $this->wheres['must'][] = [
            'regexp' => [
                $field => [
                    'value' => $value,
                    'flags' => $flags,
                ],
            ],
        ];

        return $this;
    }

    /**
     * Add a whereGeoDistance condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-distance-query.html Geo distance query
     *
     * @param string $field
     * @param string|array $value
     * @param int|string $distance
     * @return $this
     */
    public function whereGeoDistance($field, $value, $distance)
    {
        $this->wheres['must'][] = [
            'geo_distance' => [
                'distance' => $distance,
                $field     => $value,
            ],
        ];

        return $this;
    }

    /**
     * Add a whereGeoBoundingBox condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-bounding-box-query.html Geo bounding box query
     *
     * @param string $field
     * @param array $value
     * @return $this
     */
    public function whereGeoBoundingBox($field, array $value)
    {
        $this->wheres['must'][] = [
            'geo_bounding_box' => [
                $field => $value,
            ],
        ];

        return $this;
    }

    /**
     * Add a whereGeoPolygon condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-polygon-query.html Geo polygon query
     *
     * @param string $field
     * @param array $points
     * @return $this
     */
    public function whereGeoPolygon($field, array $points)
    {
        $this->wheres['must'][] = [
            'geo_polygon' => [
                $field => [
                    'points' => $points,
                ],
            ],
        ];

        return $this;
    }

    /**
     * Add a whereGeoShape condition.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-shape-query.html Querying Geo Shapes
     *
     * @param string $field
     * @param array $shape
     * @param string $relation
     * @return $this
     */
    public function whereGeoShape($field, array $shape, $relation = 'INTERSECTS')
    {
        $this->wheres['must'][] = [
            'geo_shape' => [
                $field => [
                    'shape'    => $shape,
                    'relation' => $relation,
                ],
            ],
        ];

        return $this;
    }

    /**
     * Add a orderBy clause.
     *
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function orderBy($field, $direction = 'asc')
    {
        $this->orders[] = [
            $field => strtolower($direction) === 'asc' ? 'asc' : 'desc',
        ];

        return $this;
    }

    /**
     * Add a raw order clause.
     *
     * @param array $payload
     * @return $this
     */
    public function orderRaw(array $payload)
    {
        $this->orders[] = $payload;

        return $this;
    }


    public function groupBy($data = [], $type = 'terms', $name = 'count')
    {
        $this->group[$name][$type] = $data;

        return $this;
    }

    public function union(array $payload)
    {
        $this->union = $payload;

        return $this;
    }

    /**
     * todo
     * @param $field
     * @param null $value
     * @return $this
     */
    public function orWhere($field, $value = null)
    {
        if ($field instanceof \Closure) {
            $query = $this->model->newQuery();

            $field($query);
            $this->wheres['must'][] = [
                'bool' => [
                    'should' => $query->wheres['must'],
                ]
            ];

        } else {
            $this->wheres['should'][] = [
                'term' => [
                    $field => $value,
                ]
            ];
        }

        return $this;
    }

    /**
     * Explain the request.
     *
     * @return array
     */
    public function explain()
    {
        return $this
            ->engine()
            ->explain($this);
    }

    /**
     * Profile the request.
     *
     * @return array
     */
    public function profile()
    {
        return $this
            ->engine()
            ->profile($this);
    }

    /**
     * Build the payload.
     *
     * @return array
     */
    public function buildPayload()
    {
        return $this
            ->engine()
            ->buildSearchQueryPayloadCollection($this);
    }

    /**
     * Eager load some some relations.
     *
     * @param array|string $relations
     * @return $this
     */
    public function with($relations)
    {
        $this->with = $relations;

        return $this;
    }

    /**
     * Set the query offset.
     *
     * @param int $offset
     * @return $this
     */
    public function from($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Get the keys of search results.
     *
     * @return \Illuminate\Support\Collection
     */
    public function keys()
    {
        return $this->engine()->keys($this);
    }

    /**
     * Get the first result from the search.
     *
     * @return
     */
    public function first($columns = [])
    {
        return $this->get($columns)->first();
    }

    public function find($id, $columns = [])
    {
        $this->whereMatch($this->model->getKeyName(), $id);

        return $this->first($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function get($columns = [])
    {
        if ($columns) {
            $this->select($columns);
        }
        $collection = $this->model->newCollection($this->engine()->get($this));

        if (isset($this->with) && $collection->count() > 0) {
            $collection->load($this->with);
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function paginate($perPage = null, $pageName = 'page', $page = null)
    {
        $engine = $this->engine();

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        $results = $this->model->newCollection($engine->map(
            $this, $rawResults = $engine->paginate($this, $perPage, $page), $this->model
        ));

        $paginator = (new LengthAwarePaginator($results, $engine->getTotalCount($rawResults), $perPage, $page, [
            'path'     => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]));

        $paginator->appends('query', $this->query);
//        $paginator = parent::paginate($perPage, $pageName, $page);

        if (isset($this->with) && $paginator->total() > 0) {
            $paginator
                ->getCollection()
                ->load($this->with);
        }

        return $paginator;
    }

    /**
     * Collapse by a field.
     *
     * @param string $field
     * @return $this
     */
    public function collapse(string $field)
    {
        $this->collapse = $field;

        return $this;
    }

    /**
     * Select one or many fields.
     *
     * @param mixed $fields
     * @return $this
     */
    public function select($fields)
    {
        $this->select = array_merge(
            $this->select,
            Arr::wrap($fields)
        );

        return $this;
    }

    /**
     * Set the min_score on the filter.
     *
     * @param float $score
     * @return $this
     */
    public function minScore($score)
    {
        $this->minScore = $score;

        return $this;
    }

    /**
     * Get the count.
     *
     * @return int
     */
    public function count()
    {
        return $this
            ->engine()
            ->count($this);
    }

    /**
     * {@inheritdoc}
     */
    public function withTrashed()
    {
        $this->wheres['must'] = collect($this->wheres['must'])
            ->filter(function ($item) {
                return Arr::get($item, 'term.__soft_deleted') !== 0;
            })
            ->values()
            ->all();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onlyTrashed()
    {
        return tap($this->withTrashed(), function () {
            $this->wheres['must'][] = ['term' => ['__soft_deleted' => 1]];
        });
    }


    /**
     * Eager load the relationships for the models.
     *
     * @param array $models
     * @return array
     */
    public function eagerLoadRelations(array $models)
    {
        foreach ($this->with as $name => $constraints) {
            if (is_numeric($name)) {
                $name = $constraints;

                list($name, $constraints) = [$name, function () {
                    //
                }];
            }
            // For nested eager loads we'll skip loading them here and they will be set as an
            // eager load on the query to retrieve the relation so that they will be eager
            // loaded on that query, because that is where they get hydrated as models.
            if (strpos($name, '.') === false) {
                $models = $this->eagerLoadRelation($models, $name, $constraints);
            }
        }

        return $models;
    }

    /**
     * Eagerly load the relationship on a set of models.
     *
     * @param array $models
     * @param string $name
     * @param \Closure $constraints
     * @return array
     */
    protected function eagerLoadRelation(array $models, $name, \Closure $constraints)
    {
        list($query, $foreignKey, $ownerKey, $relate) = $this->model->getESRelation($name);

        $constraints($query);

        $dictionary = $query->whereIn($ownerKey, $this->getEagerModelKeys($models, $foreignKey))->get();

        if ($relate == 'get') {
            $dictionary = $dictionary->groupBy($ownerKey);
        } else {
            $dictionary = $dictionary->keyBy($ownerKey);
        }

        foreach ($models as $model) {
            if (isset($dictionary[$model->$foreignKey])) {
                $model->setRelation($name, $dictionary[$model->$foreignKey]);
            } else {
                $model->setRelation($name, $relate == 'get' ? [] : null);
            }
        }

        return $models;

    }

    protected function getEagerModelKeys(array $models, $foreignKey)
    {
        $keys = [];
        foreach ($models as $model) {
            if (!is_null($value = $model->{$foreignKey})) {
                $keys[] = $value;
            }
        }

        return array_values(array_unique($keys));
    }


    protected function stripTableForPluck($column)
    {
        return is_null($column) ? $column : last(preg_split('~\.| ~', $column));
    }

    public function pluck($column, $key = null)
    {
        $this->select(is_null($key) ? [$column] : [$column, $key]);
        $results = $this->get();

        // If the columns are qualified with a table or have an alias, we cannot use
        // those directly in the "pluck" operations since the results from the DB
        // are only keyed by the column itself. We'll strip the table out here.
        return $results->pluck(
            $this->stripTableForPluck($column),
            $this->stripTableForPluck($key)
        );
    }

    public function exists()
    {
        return $this->get()->isNotEmpty() ? true : false;
    }

    public function mappingExists()
    {
        return $this->engine()->exists($this);
    }

    public function update($attributes = [])
    {
        if ($this->model->getKey()) {
            return $this->engine()->update(collect([$this->model]));
        }
        $models = $this->get();
        if ($models->isNotEmpty() && $attributes) {
            $models->map(function ($item) use ($attributes) {
                $item->fill($attributes);
                if ($item->usesTimestamps()) {
                    $item->updateTimestamps();
                }
                return $item;
            });
            return $this->engine()->update($models);
        }
    }

    public function delete()
    {
        if ($this->model->getKey()) {
            return $this->engine()->delete(collect([$this->model]));
        }
        $models = $this->get();
        if ($models->isNotEmpty()) {
            return $this->engine()->delete($models);
        }
    }
}
