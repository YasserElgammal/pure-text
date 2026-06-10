<?php

namespace YasserElgammal\PureText\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class TextFiltered
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $originalText,
        public readonly string $filteredText,
        public readonly ?Model $model = null,
        public readonly ?string $attribute = null,
    ) {}
}
