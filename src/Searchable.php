<?php


namespace ScoutElasticModel;


use ScoutElasticModel\Builders\FilterBuilder;
use ScoutElasticModel\Builders\SearchBuilder;

trait Searchable
{

    public function ESModel()
    {
        $model = new FacadeModel();
        $model->setTable($this->getTable());
        if (isset($this->dates))
            $model->setDates($this->dates);
        $model->setFillable($this->fillable);

        return $model;
    }

    public function updateES()
    {
        $model = $this->ESModel();
        $data = $this->toArray();
        $model->setFillable(array_keys($data));
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function delES()
    {
        $model = $this->ESModel();
        $data = $this->toArray();
        $model->setRawAttributes($data);
        $model->newQuery()->delete();
    }

    public static function search($query = '*', $callback = null)
    {
        $self = new self();
        $model = $self->ESModel();
        $model->setCasts($self->casts);
        $model->callbackModel = $self;
        if ($query === '*') {
            return new FilterBuilder($model, $callback);
        } else {
            return new SearchBuilder($model, $query, $callback);
        }
    }

    public function delete()
    {
        $this->delES();
        return parent::delete();
    }

    public function save(array $options = [])
    {
        $saved = parent::save($options);
        if ($saved) {
            $this->updateES();
        }
        return $saved;
    }

    private static $expand = [];

    public static function setExpand($ids = [])
    {
        if ($count = count($ids)) {
            $models = self::search()->whereIn('id', $ids)->take($count)->get();
            foreach ($models as $model) {
                self::$expand[$model->id] = $model;
            }
        }
    }

    public static function getExpand($id)
    {
        if (!isset(self::$expand[$id])) {
            self::$expand[$id] = self::search()->whereMatch('id', $id)->first();
        }
        return self::$expand[$id];
    }


}