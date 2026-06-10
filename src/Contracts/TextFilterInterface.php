<?php

namespace YasserElgammal\PureText\Contracts;

interface TextFilterInterface
{
    /**
     * Replace bad words in the given text with the configured replacement.
     */
    public function filter(string $text): string;

    /**
     * Check if the given text contains any bad words.
     */
    public function containsBadWords(string $text): bool;

    /**
     * Extract all bad words found in the given text.
     *
     * @return array<int, string>
     */
    public function getBadWords(string $text): array;
}
