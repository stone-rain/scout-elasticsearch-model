<?php


namespace ScoutElasticModel;


class FacadeModel extends ElasticModel
{
    public $timestamps = false; // 自动更新时间戳
    protected $dates = ['created_at', 'updated_at'];


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