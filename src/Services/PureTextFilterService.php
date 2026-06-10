<?php

namespace YasserElgammal\PureText\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use YasserElgammal\PureText\Contracts\TextFilterInterface;

class PureTextFilterService implements TextFilterInterface
{
    /**
     * Cached compiled regex pattern.
     */
    protected ?string $compiledPattern = null;

    /**
     * Build the regex pattern for matching bad words.
     *
     * Supports Unicode (Arabic, etc.) by using Unicode-aware boundaries
     * instead of \b which only works with ASCII word characters.
     */
    protected function buildPattern(): ?string
    {
        if ($this->compiledPattern !== null) {
            return $this->compiledPattern;
        }

        $badWords = Config::get('badwords.words', []);

        if (empty($badWords)) {
            return $this->compiledPattern = null;
        }

        $alternatives = array_map(function (string $word): string {
            $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);

            $escapedChars = array_map(function (string $char): string {
                return preg_quote($char, '/');
            }, $chars);

            $inner = implode('[\W_]*', array_map(function (string $char): string {
                return $char . '{1,}';
            }, $escapedChars));

            // Use Unicode-aware word boundaries (fixed-length assertions):
            // (?<!\p{L}) — not preceded by a Unicode letter (supports Arabic, etc.)
            // (?!\p{L}) — not followed by a Unicode letter
            return '(?<!\p{L})' . $inner . '(?!\p{L})';
        }, $badWords);

        $this->compiledPattern = '/' . implode('|', $alternatives) . '/iu';

        return $this->compiledPattern;
    }

    /**
     * Get the replacement string based on configured strategy.
     */
    protected function getReplacement(string $match): string
    {
        $strategy = Config::get('badwords.strategy', 'fixed');
        $replacement = Config::get('badwords.replacement', '***');

        return match ($strategy) {
            'character' => str_repeat(
                Config::get('badwords.character', '*'),
                mb_strlen($match)
            ),
            'length_match' => str_repeat(
                mb_substr($replacement, 0, 1) ?: '*',
                mb_strlen($match)
            ),
            default => $replacement, // 'fixed' strategy
        };
    }

    /**
     * Replace bad words in the given text with the configured replacement.
     */
    public function filter(string $text): string
    {
        $pattern = $this->buildPattern();

        if ($pattern === null) {
            return $text;
        }

        $strategy = Config::get('badwords.strategy', 'fixed');

        if ($strategy === 'fixed') {
            $replacement = Config::get('badwords.replacement', '***');
            $result = preg_replace($pattern, $replacement, $text);
        } else {
            $result = preg_replace_callback($pattern, function (array $matches): string {
                return $this->getReplacement($matches[0]);
            }, $text);
        }

        if ($result === null) {
            Log::warning('PureText: Regex error while filtering text.', [
                'error' => preg_last_error_msg(),
                'pattern' => $pattern,
            ]);

            return $text;
        }

        return $result;
    }

    /**
     * Check if the given text contains any bad words.
     */
    public function containsBadWords(string $text): bool
    {
        $pattern = $this->buildPattern();

        if ($pattern === null) {
            return false;
        }

        return (bool) preg_match($pattern, $text);
    }

    /**
     * Extract all bad words found in the given text.
     *
     * @return array<int, string>
     */
    public function getBadWords(string $text): array
    {
        $pattern = $this->buildPattern();

        if ($pattern === null) {
            return [];
        }

        preg_match_all($pattern, $text, $matches);

        return $matches[0] ?? [];
    }

    /**
     * Reset the cached pattern (useful after config changes in tests).
     */
    public function resetPattern(): void
    {
        $this->compiledPattern = null;
    }
}
