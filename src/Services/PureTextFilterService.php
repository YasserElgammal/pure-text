<?php

namespace YasserElgammal\PureText\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use YasserElgammal\PureText\Contracts\TextFilterInterface;

class PureTextFilterService implements TextFilterInterface
{
    /**
     * Cached compiled regex pattern for bad words.
     */
    /** @var array<int, string>|null */
    protected ?array $compiledPatterns = null;

    /**
     * Cached compiled regex pattern for whitelist.
     */
    /** @var array<int, string>|null */
    protected ?array $compiledWhitelistPatterns = null;

    /**
     * Custom runtime whitelist words.
     *
     * @var array<int, string>
     */
    protected array $customWhitelist = [];

    /**
     * Leetspeak and homoglyph character substitutions.
     *
     * @var array<string, string>
     */
    protected array $leetspeakMap = [
        'a' => '[a@4]',
        'b' => '[b8]',
        'c' => '[c\(\<]',
        'e' => '[e3]',
        'g' => '[g96]',
        'i' => '[i1!|l]',
        'l' => '[l1!|]',
        'o' => '[o0]',
        's' => '[s5\$]',
        't' => '[t7\+]',
        'z' => '[z2]',
    ];

    /**
     * Normalize an Arabic word by removing diacritics (tashkeel),
     * tatweel (kashida), and canonicalizing letter variants.
     */
    protected function normalizeArabicWord(string $word): string
    {
        // Strip Arabic diacritics (tashkeel: fatha, damma, kasra, tanween, sukun, shadda, etc.)
        $word = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $word) ?? $word;

        // Strip tatweel (kashida)
        $word = preg_replace('/\x{0640}+/u', '', $word) ?? $word;

        // Normalize Alef variants: أ, إ, آ, ٱ -> ا
        $word = preg_replace('/[أإآٱ]/u', 'ا', $word) ?? $word;

        // Normalize Taa Marbuta: ة -> ه
        $word = preg_replace('/ة/u', 'ه', $word) ?? $word;

        // Normalize Alef Maqsura & Hamza on Yaa: ى, ئ -> ي
        $word = preg_replace('/[ىئ]/u', 'ي', $word) ?? $word;

        // Normalize Waw with Hamza: ؤ -> و
        $word = preg_replace('/ؤ/u', 'و', $word) ?? $word;

        return $word;
    }

    /**
     * Get the regex pattern component for a single character,
     * taking into account Arabic normalization and leetspeak substitution.
     */
    protected function getCharPattern(
        string $char,
        bool $normalizeArabic,
        bool $detectLeetspeak,
        int $maxSeparators,
    ): string
    {
        $marks = '[\p{M}]{0,' . $maxSeparators . '}';

        if ($normalizeArabic) {
            if ($char === 'ا') {
                return '(?:[اأإآٱ]' . $marks . ')';
            }
            if ($char === 'ه') {
                return '(?:[هة]' . $marks . ')';
            }
            if ($char === 'ي') {
                return '(?:[يىئ]' . $marks . ')';
            }
            if ($char === 'و') {
                return '(?:[وؤ]' . $marks . ')';
            }
            if (preg_match('/\p{Arabic}/u', $char)) {
                return '(?:' . preg_quote($char, '/') . $marks . ')';
            }
        }

        if ($detectLeetspeak) {
            $lower = mb_strtolower($char);
            if (isset($this->leetspeakMap[$lower])) {
                return $this->leetspeakMap[$lower];
            }
        }

        return preg_quote($char, '/');
    }

    /**
     * Build the regex pattern for matching bad words.
     *
     * Supports Unicode (Arabic, etc.) using Unicode-aware boundaries,
     * optional tatweel/kashida evasion, diacritics, and leetspeak.
     */
    protected function buildPatterns(): array
    {
        if ($this->compiledPatterns !== null) {
            return $this->compiledPatterns;
        }

        $badWords = $this->sanitizeWords(Config::get('badwords.words', []), 'words');

        if (empty($badWords)) {
            return $this->compiledPatterns = [];
        }

        $normalizeArabic = (bool) Config::get('badwords.normalize_arabic', true);
        $detectLeetspeak = (bool) Config::get('badwords.detect_leetspeak', true);
        $maxSeparators = $this->integerConfig('max_separators', 8, 0, 32);
        $maxRepeatedCharacters = $this->integerConfig('max_repeated_characters', 8, 1, 32);
        $chunkSize = $this->integerConfig('pattern_chunk_size', 250, 1, 1000);
        $separator = '[\W_\x{0640}\p{M}]{0,' . $maxSeparators . '}';

        usort($badWords, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $alternatives = array_map(function (string $word) use (
            $normalizeArabic,
            $detectLeetspeak,
            $maxSeparators,
            $maxRepeatedCharacters,
            $separator,
        ): string {
            $normalizedWord = $normalizeArabic ? $this->normalizeArabicWord($word) : $word;

            $chars = preg_split('//u', $normalizedWord, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            if ($chars === []) {
                return '';
            }

            $charPatterns = array_map(function (string $char) use (
                $normalizeArabic,
                $detectLeetspeak,
                $maxSeparators,
            ): string {
                return $this->getCharPattern($char, $normalizeArabic, $detectLeetspeak, $maxSeparators);
            }, $chars);

            $inner = implode($separator, array_map(
                static fn (string $charPattern): string => '(?:' . $charPattern . '){1,' . $maxRepeatedCharacters . '}',
                $charPatterns
            ));

            // Unicode-aware word boundaries:
            // (?<![^\P{L}\x{0640}]) — not preceded by a letter (except tatweel \x{0640})
            // (?![^\P{L}\x{0640}]) — not followed by a letter (except tatweel \x{0640})
            return '(?<![^\P{L}\x{0640}])\x{0640}{0,' . $maxSeparators . '}'
                . $inner
                . '\x{0640}{0,' . $maxSeparators . '}(?![^\P{L}\x{0640}])';
        }, $badWords);

        $alternatives = array_values(array_filter($alternatives));

        $patterns = array_map(
            static fn (array $chunk): string => '/' . implode('|', $chunk) . '/iu',
            array_chunk($alternatives, $chunkSize)
        );

        return $this->compiledPatterns = $patterns;
    }

    /**
     * Build the regex pattern for matching whitelisted words.
     */
    protected function buildWhitelistPatterns(): array
    {
        if ($this->compiledWhitelistPatterns !== null) {
            return $this->compiledWhitelistPatterns;
        }

        $whitelist = $this->getWhitelist();

        if (empty($whitelist)) {
            return $this->compiledWhitelistPatterns = [];
        }

        // Sort by length descending so longer phrases match before shorter sub-words
        usort($whitelist, function (string $a, string $b): int {
            return mb_strlen($b) <=> mb_strlen($a);
        });

        $alternatives = array_map(function (string $word): string {
            return '(?<![^\P{L}\x{0640}])' . preg_quote($word, '/') . '(?![^\P{L}\x{0640}])';
        }, $whitelist);

        $chunkSize = $this->integerConfig('pattern_chunk_size', 250, 1, 1000);

        return $this->compiledWhitelistPatterns = array_map(
            static fn (array $chunk): string => '/' . implode('|', $chunk) . '/iu',
            array_chunk($alternatives, $chunkSize)
        );
    }

    /**
     * Protect whitelisted words in text by replacing them with unique placeholders.
     *
     * @param array<string, string> $placeholders
     */
    protected function protectWhitelisted(string $text, array &$placeholders): ?string
    {
        $patterns = $this->buildWhitelistPatterns();

        if (empty($patterns)) {
            return $text;
        }

        $counter = 0;

        foreach ($patterns as $pattern) {
            $result = preg_replace_callback($pattern, function (array $matches) use (&$placeholders, &$counter): string {
                $placeholder = $this->makePlaceholder($counter++, 0xF0000, "\u{E000}", "\u{E001}");
                $placeholders[$placeholder] = $matches[0];

                return $placeholder;
            }, $text);

            if ($result === null) {
                $this->logRegexError($pattern);

                return null;
            }

            $text = $result;
        }

        return $text;
    }

    /**
     * Build a Unicode private-use placeholder that filtering patterns cannot
     * consume or bridge across (padding exceeds the maximum separator limit).
     */
    protected function makePlaceholder(
        int $counter,
        int $baseCodePoint,
        string $leftPadding,
        string $rightPadding,
    ): string
    {
        return str_repeat($leftPadding, 33)
            . mb_chr($baseCodePoint + ($counter % 0xFFFD), 'UTF-8')
            . str_repeat($rightPadding, 33);
    }

    /**
     * Normalize configured word lists before compiling them into regular expressions.
     *
     * @return array<int, string>
     */
    protected function sanitizeWords(mixed $words, string $listName): array
    {
        if (!is_array($words)) {
            throw new \InvalidArgumentException("PureText: The {$listName} configuration must be an array.");
        }

        $maxWordLength = $this->integerConfig('max_word_length', 100, 1, 1000);
        $maxWords = $this->integerConfig('max_words', 10000, 1, 100000);
        $sanitized = [];
        $seen = [];

        foreach ($words as $word) {
            if (!is_string($word)) {
                continue;
            }

            $word = preg_replace('/^\s+|\s+$/u', '', $word) ?? trim($word);

            if ($word === '') {
                continue;
            }

            if (mb_strlen($word) > $maxWordLength) {
                throw new \InvalidArgumentException(
                    "PureText: An entry in {$listName} exceeds max_word_length ({$maxWordLength})."
                );
            }

            $key = mb_strtolower($word);

            if (isset($seen[$key])) {
                continue;
            }

            if (count($sanitized) >= $maxWords) {
                throw new \InvalidArgumentException(
                    "PureText: The {$listName} configuration exceeds max_words ({$maxWords})."
                );
            }

            $seen[$key] = true;
            $sanitized[] = $word;
        }

        return $sanitized;
    }

    protected function integerConfig(string $key, int $default, int $minimum, int $maximum): int
    {
        $value = filter_var(Config::get("badwords.{$key}", $default), FILTER_VALIDATE_INT);

        if ($value === false) {
            return $default;
        }

        return max($minimum, min($maximum, $value));
    }

    protected function failClosed(): bool
    {
        return (bool) Config::get('badwords.fail_closed', true);
    }

    protected function regexFailureReplacement(string $originalText): string
    {
        return $this->failClosed()
            ? (string) Config::get('badwords.replacement', '***')
            : $originalText;
    }

    protected function logRegexError(string $pattern): void
    {
        Log::warning('PureText: Regex error while filtering text.', [
            'error' => preg_last_error_msg(),
            'pattern_hash' => hash('sha256', $pattern),
        ]);
    }

    protected function logConfigurationError(\InvalidArgumentException $exception): void
    {
        Log::warning($exception->getMessage());
    }

    /**
     * Restore whitelisted placeholders back to their original text.
     *
     * @param array<string, string> $placeholders
     */
    protected function restoreWhitelisted(string $text, array $placeholders): string
    {
        if (empty($placeholders)) {
            return $text;
        }

        return strtr($text, $placeholders);
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
     * Replace bad words in the given text with the configured replacement,
     * preserving any whitelisted words.
     */
    public function filter(string $text): string
    {
        $originalText = $text;
        $placeholders = [];

        try {
            $text = $this->protectWhitelisted($text, $placeholders);
            $patterns = $this->buildPatterns();
        } catch (\InvalidArgumentException $exception) {
            $this->logConfigurationError($exception);

            return $this->regexFailureReplacement($originalText);
        }

        if ($text === null) {
            return $this->regexFailureReplacement($originalText);
        }

        if (empty($patterns)) {
            return $this->restoreWhitelisted($text, $placeholders);
        }

        $replacementPlaceholders = [];
        $replacementCounter = 0;

        foreach ($patterns as $pattern) {
            $result = preg_replace_callback($pattern, function (array $matches) use (
                &$replacementPlaceholders,
                &$replacementCounter,
            ): string {
                $placeholder = $this->makePlaceholder(
                    $replacementCounter++,
                    0x100000,
                    "\u{E002}",
                    "\u{E003}",
                );
                $replacementPlaceholders[$placeholder] = $this->getReplacement($matches[0]);

                return $placeholder;
            }, $text);

            if ($result === null) {
                $this->logRegexError($pattern);

                return $this->regexFailureReplacement($originalText);
            }

            $text = $result;
        }

        $text = strtr($text, $replacementPlaceholders);

        return $this->restoreWhitelisted($text, $placeholders);
    }

    /**
     * Check if the given text contains any bad words (excluding whitelisted words).
     */
    public function containsBadWords(string $text): bool
    {
        $placeholders = [];

        try {
            $text = $this->protectWhitelisted($text, $placeholders);
            $patterns = $this->buildPatterns();
        } catch (\InvalidArgumentException $exception) {
            $this->logConfigurationError($exception);

            return $this->failClosed();
        }

        if ($text === null) {
            return $this->failClosed();
        }

        if (empty($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            $result = preg_match($pattern, $text);

            if ($result === false) {
                $this->logRegexError($pattern);

                return $this->failClosed();
            }

            if ($result === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract all bad words found in the given text (excluding whitelisted words).
     *
     * @return array<int, string>
     */
    public function getBadWords(string $text): array
    {
        $placeholders = [];

        try {
            $text = $this->protectWhitelisted($text, $placeholders);
            $patterns = $this->buildPatterns();
        } catch (\InvalidArgumentException $exception) {
            $this->logConfigurationError($exception);

            return [];
        }

        if ($text === null) {
            return [];
        }

        if (empty($patterns)) {
            return [];
        }

        $found = [];

        foreach ($patterns as $pattern) {
            $result = preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

            if ($result === false) {
                $this->logRegexError($pattern);

                return [];
            }

            foreach ($matches[0] ?? [] as [$match, $offset]) {
                $found[] = [
                    'match' => $match,
                    'offset' => $offset,
                    'length' => strlen($match),
                ];
            }
        }

        usort($found, static function (array $a, array $b): int {
            return ($a['offset'] <=> $b['offset']) ?: ($b['length'] <=> $a['length']);
        });

        $matches = [];
        $lastMatchEnd = -1;

        foreach ($found as $item) {
            if ($item['offset'] < $lastMatchEnd) {
                continue;
            }

            $matches[] = $item['match'];
            $lastMatchEnd = $item['offset'] + $item['length'];
        }

        return $matches;
    }

    /**
     * Get the active whitelist of words (config + custom).
     *
     * @return array<int, string>
     */
    public function getWhitelist(): array
    {
        $configWhitelist = Config::get('badwords.whitelist', []);

        if (!is_array($configWhitelist)) {
            throw new \InvalidArgumentException('PureText: The whitelist configuration must be an array.');
        }

        return $this->sanitizeWords(array_merge($configWhitelist, $this->customWhitelist), 'whitelist');
    }

    /**
     * Return a new filter instance with additional whitelisted words.
     *
     * @param array<int, string> $words
     */
    public function withWhitelist(array $words): self
    {
        $clone = clone $this;
        $clone->customWhitelist = array_values(array_merge($this->customWhitelist, $words));
        $clone->compiledWhitelistPatterns = null;

        return $clone;
    }

    /**
     * Alias for withWhitelist.
     *
     * @param array<int, string> $words
     */
    public function whitelist(array $words): self
    {
        return $this->withWhitelist($words);
    }

    /**
     * Reset cached patterns and custom state (useful between tests).
     */
    public function resetPattern(): void
    {
        $this->compiledPatterns = null;
        $this->compiledWhitelistPatterns = null;
        $this->customWhitelist = [];
    }
}
