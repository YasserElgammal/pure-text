<?php

namespace YasserElgammal\PureText\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use YasserElgammal\PureText\Services\PureTextFilterService;

class PureTextRule implements ValidationRule
{
    protected $filterService;

    public function __construct()
    {
        $this->filterService = new PureTextFilterService();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $filtered = $this->filterService->filter($value);

        if ($filtered !== $value) {
            $fail(__('The :attribute contains prohibited words.'));
        }
    }
}
