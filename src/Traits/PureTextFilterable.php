<?php

namespace YasserElgammal\PureText\Traits;

use YasserElgammal\PureText\Services\PureTextFilterService;


trait PureTextFilterable
{
    protected static function bootPureTextFilterable()
    {
        static::saving(function ($model) {
            foreach ($model->filterableAttributes() as $attribute) {
                if (isset($model->$attribute)) {
                    $model->$attribute = app(PureTextFilterService::class)->filter($model->$attribute);
                }
            }
        });
    }

    /**
     * Define the list of attributes that need to be filtered.
     * @return array
     */
    public function filterableAttributes()
    {
        return property_exists($this, 'filterable') ? $this->filterable : [];
    }
}
