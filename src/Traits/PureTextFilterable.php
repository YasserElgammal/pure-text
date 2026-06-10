<?php

namespace YasserElgammal\PureText\Traits;

use YasserElgammal\PureText\Contracts\TextFilterInterface;
use YasserElgammal\PureText\Events\TextFiltered;

trait PureTextFilterable
{
    protected static function bootPureTextFilterable(): void
    {
        static::saving(function ($model) {
            /** @var TextFilterInterface $filterService */
            $filterService = app(TextFilterInterface::class);

            foreach ($model->filterableAttributes() as $attribute) {
                if (!isset($model->$attribute) || !is_string($model->$attribute)) {
                    continue;
                }

                $original = $model->$attribute;
                $filtered = $filterService->filter($original);

                if ($filtered !== $original) {
                    $model->$attribute = $filtered;

                    TextFiltered::dispatch($original, $filtered, $model, $attribute);
                }
            }
        });
    }

    /**
     * Define the list of attributes that need to be filtered.
     *
     * @return array<int, string>
     */
    public function filterableAttributes(): array
    {
        return property_exists($this, 'filterable') ? $this->filterable : [];
    }
}
