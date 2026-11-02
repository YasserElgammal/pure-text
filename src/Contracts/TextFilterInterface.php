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

    /**
     * Return a filter instance with additional whitelisted words.
     *
     * @param array<int, string> $words
     */
    public function withWhitelist(array $words): self;

    /**
     * Alias for withWhitelist.
     *
     * @param array<int, string> $words
     */
    public function whitelist(array $words): self;
}
