<?php

use YasserElgammal\PureText\Contracts\TextFilterInterface;

if (!function_exists('pure_text')) {
    /**
     * Filter bad words from the given text.
     */
    function pure_text(string $text): string
    {
        return app(TextFilterInterface::class)->filter($text);
    }
}

if (!function_exists('has_bad_words')) {
    /**
     * Check if the given text contains any bad words.
     */
    function has_bad_words(string $text): bool
    {
        return app(TextFilterInterface::class)->containsBadWords($text);
    }
}
