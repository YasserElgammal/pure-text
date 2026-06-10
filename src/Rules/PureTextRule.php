<?php

namespace YasserElgammal\PureText\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use YasserElgammal\PureText\Contracts\TextFilterInterface;

class PureTextRule implements ValidationRule
{
    protected TextFilterInterface $filterService;

    public function __construct()
    {
        $this->filterService = app(TextFilterInterface::class);
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        if ($this->filterService->containsBadWords($value)) {
            $fail(__('The :attribute contains prohibited words.'));
        }
    }
}
