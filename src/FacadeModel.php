<?php


namespace ScoutElasticModel;


class FacadeModel extends ElasticModel
{
    public $timestamps = false; // 自动更新时间戳
    protected $dates = ['created_at', 'updated_at'];

    public $expand = [];
    public $face = 1;

    public function setFace($face)
    {
        $this->face = $face;

        return $this;
    }
    public function setExpand($key, $value)
    {
        $this->expand[$this->face][$key] = $value;

        return $this;
    }

    public function getExpand($key)
    {
        if(isset($this->expand[$this->face][$key])){
            return $this->expand[$this->face][$key];
        }
        return null;
    }

    protected $mapping = [
        'properties' => [
            'id' => [
                'type' => 'long',
            ],
        ]
    ];

    public function getSettings()
    {
        return [
            "number_of_shards"   => 5,
            "number_of_replicas" => 1,
        ];
    }

    public function setDates($dates)
    {
        $this->dates = array_merge($this->dates, $dates);
    }

    public function setFillable($fillable)
    {
        $this->fillable = $fillable;
    }

    public function setCasts($casts)
    {
        $this->casts = $casts;
    }

    public function toArray($casts = null)
    {
//        array_shift($casts);
        $json = parent::toArray();
        $this->setCasts($casts);
        foreach ($json as $key => $value) {
            if ($this->isJsonCastable($key) && !is_null($value)) {
                $value = $this->castAttributeAsJson($key, $value);
            }
            $json[$key] = $value;
        }

        return $json;
    }
}