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
    protected ?string $compiledPattern = null;

    /**
     * Cached compiled regex pattern for whitelist.
     */
    protected ?string $compiledWhitelistPattern = null;

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
    protected function getCharPattern(string $char, bool $normalizeArabic, bool $detectLeetspeak): string
    {
        if ($normalizeArabic) {
            if ($char === 'ا') {
                return '(?:[اأإآٱ][\p{M}]*)';
            }
            if ($char === 'ه') {
                return '(?:[هة][\p{M}]*)';
            }
            if ($char === 'ي') {
                return '(?:[يىئ][\p{M}]*)';
            }
            if ($char === 'و') {
                return '(?:[وؤ][\p{M}]*)';
            }
            if (preg_match('/\p{Arabic}/u', $char)) {
                return '(?:' . preg_quote($char, '/') . '[\p{M}]*)';
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
    protected function buildPattern(): ?string
    {
        if ($this->compiledPattern !== null) {
            return $this->compiledPattern;
        }

        $badWords = Config::get('badwords.words', []);

        if (empty($badWords)) {
            return $this->compiledPattern = null;
        }

        $normalizeArabic = (bool) Config::get('badwords.normalize_arabic', true);
        $detectLeetspeak = (bool) Config::get('badwords.detect_leetspeak', true);

        $alternatives = array_map(function (string $word) use ($normalizeArabic, $detectLeetspeak): string {
            $normalizedWord = $normalizeArabic ? $this->normalizeArabicWord($word) : $word;

            $chars = preg_split('//u', $normalizedWord, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            $charPatterns = array_map(function (string $char) use ($normalizeArabic, $detectLeetspeak): string {
                return $this->getCharPattern($char, $normalizeArabic, $detectLeetspeak);
            }, $chars);

            $inner = implode('[\W_\x{0640}\p{M}]*', array_map(function (string $charPattern): string {
                return '(?:' . $charPattern . ')+';
            }, $charPatterns));

            // Unicode-aware word boundaries:
            // (?<![^\P{L}\x{0640}]) — not preceded by a letter (except tatweel \x{0640})
            // (?![^\P{L}\x{0640}]) — not followed by a letter (except tatweel \x{0640})
            return '(?<![^\P{L}\x{0640}])\x{0640}*' . $inner . '\x{0640}*(?![^\P{L}\x{0640}])';
        }, $badWords);

        $this->compiledPattern = '/' . implode('|', $alternatives) . '/iu';

        return $this->compiledPattern;
    }

    /**
     * Build the regex pattern for matching whitelisted words.
     */
    protected function buildWhitelistPattern(): ?string
    {
        if ($this->compiledWhitelistPattern !== null) {
            return $this->compiledWhitelistPattern;
        }

        $whitelist = $this->getWhitelist();

        if (empty($whitelist)) {
            return $this->compiledWhitelistPattern = null;
        }

        // Sort by length descending so longer phrases match before shorter sub-words
        usort($whitelist, function (string $a, string $b): int {
            return mb_strlen($b) <=> mb_strlen($a);
        });

        $alternatives = array_map(function (string $word): string {
            return '(?<![^\P{L}\x{0640}])' . preg_quote($word, '/') . '(?![^\P{L}\x{0640}])';
        }, $whitelist);

        $this->compiledWhitelistPattern = '/' . implode('|', $alternatives) . '/iu';

        return $this->compiledWhitelistPattern;
    }

    /**
     * Protect whitelisted words in text by replacing them with unique placeholders.
     *
     * @param array<string, string> $placeholders
     */
    protected function protectWhitelisted(string $text, array &$placeholders): string
    {
        $pattern = $this->buildWhitelistPattern();

        if ($pattern === null) {
            return $text;
        }

        $counter = 0;

        return preg_replace_callback($pattern, function (array $matches) use (&$placeholders, &$counter): string {
            $placeholder = "\x1E\x1FPTWL" . ($counter++) . "\x1F\x1E";
            $placeholders[$placeholder] = $matches[0];

            return $placeholder;
        }, $text) ?? $text;
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
        $placeholders = [];
        $text = $this->protectWhitelisted($text, $placeholders);

        $pattern = $this->buildPattern();

        if ($pattern === null) {
            return $this->restoreWhitelisted($text, $placeholders);
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

            return $this->restoreWhitelisted($text, $placeholders);
        }

        return $this->restoreWhitelisted($result, $placeholders);
    }

    /**
     * Check if the given text contains any bad words (excluding whitelisted words).
     */
    public function containsBadWords(string $text): bool
    {
        $placeholders = [];
        $text = $this->protectWhitelisted($text, $placeholders);

        $pattern = $this->buildPattern();

        if ($pattern === null) {
            return false;
        }

        return (bool) preg_match($pattern, $text);
    }

    /**
     * Extract all bad words found in the given text (excluding whitelisted words).
     *
     * @return array<int, string>
     */
    public function getBadWords(string $text): array
    {
        $placeholders = [];
        $text = $this->protectWhitelisted($text, $placeholders);

        $pattern = $this->buildPattern();

        if ($pattern === null) {
            return [];
        }

        preg_match_all($pattern, $text, $matches);

        return $matches[0] ?? [];
    }

    /**
     * Set a custom whitelist for this service instance.
     *
     * @param array<int, string> $words
     */
    public function setWhitelist(array $words): self
    {
        $this->customWhitelist = $words;
        $this->compiledWhitelistPattern = null;

        return $this;
    }

    /**
     * Get the active whitelist of words (config + custom).
     *
     * @return array<int, string>
     */
    public function getWhitelist(): array
    {
        $configWhitelist = Config::get('badwords.whitelist', []);

        return array_values(array_unique(array_merge($configWhitelist, $this->customWhitelist)));
    }

    /**
     * Return a new filter instance with additional whitelisted words.
     *
     * @param array<int, string> $words
     */
    public function withWhitelist(array $words): self
    {
        $clone = clone $this;
        $clone->customWhitelist = array_values(array_unique(array_merge($this->getWhitelist(), $words)));
        $clone->compiledWhitelistPattern = null;

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
        $this->compiledPattern = null;
        $this->compiledWhitelistPattern = null;
        $this->customWhitelist = [];
    }
}
