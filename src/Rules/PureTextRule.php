<?php

namespace YasserElgammal\PureText\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use YasserElgammal\PureText\Contracts\TextFilterInterface;

class PureTextRule implements ValidationRule
{
    protected TextFilterInterface $filterService;

    /**
     * @var array<int, string>
     */
    protected array $whitelist;

    /**
     * @param array<int, string> $whitelist Additional allowed words for this validation rule.
     */
    public function __construct(array $whitelist = [])
    {
        $this->filterService = app(TextFilterInterface::class);
        $this->whitelist = $whitelist;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        $service = !empty($this->whitelist)
            ? $this->filterService->withWhitelist($this->whitelist)
            : $this->filterService;

        if ($service->containsBadWords($value)) {
            $fail(__('The :attribute contains prohibited words.'));
        }
    }
}
