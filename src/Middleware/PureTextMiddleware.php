<?php

namespace YasserElgammal\PureText\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use YasserElgammal\PureText\Contracts\TextFilterInterface;

class PureTextMiddleware
{
    public function __construct(
        protected TextFilterInterface $filterService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * Filter specified fields (or all string fields) for bad words.
     *
     * Usage in routes:
     *   ->middleware('pure-text')           // filter all string fields
     *   ->middleware('pure-text:title,body') // filter specific fields
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$fields): Response
    {
        $fieldsToFilter = !empty($fields) ? $fields : $request->keys();

        $filtered = [];

        foreach ($fieldsToFilter as $field) {
            $value = $request->input($field);

            if (is_string($value) && $value !== '') {
                $filtered[$field] = $this->filterService->filter($value);
            }
        }

        if (!empty($filtered)) {
            $request->merge($filtered);
        }

        return $next($request);
    }
}
