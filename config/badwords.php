<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bad Words List
    |--------------------------------------------------------------------------
    |
    | Define the list of words that should be filtered. These words will be
    | matched case-insensitively and will detect common evasion techniques
    | like inserting special characters between letters (e.g., "b-a-d").
    |
    | Supports Latin, Arabic, and other Unicode characters.
    |
    */

    'words' => [
        // 'badword1',
        // 'badword2',
    ],

    /*
    |--------------------------------------------------------------------------
    | Whitelist / Allowed Words
    |--------------------------------------------------------------------------
    |
    | Words or phrases that should never be flagged or replaced, even if they
    | match bad words or evasion patterns (prevents the Scunthorpe problem).
    |
    | Example: ['cocktail', 'classic', 'document', 'pass']
    |
    */

    'whitelist' => [
        // 'cocktail',
        // 'document',
    ],

    /*
    |--------------------------------------------------------------------------
    | Arabic & Unicode Normalization
    |--------------------------------------------------------------------------
    |
    | When enabled, normalizes Arabic diacritics (tashkeel), tatweel (kashida),
    | and letter variants (e.g., أ/إ/آ -> ا, ة -> ه, ى -> ي) to detect evasion.
    |
    */

    'normalize_arabic' => true,

    /*
    |--------------------------------------------------------------------------
    | Leetspeak & Homoglyph Detection
    |--------------------------------------------------------------------------
    |
    | When enabled, detects common leetspeak substitutions such as
    | b4d, @ss, sh!t, p0rn, and 1337.
    |
    */

    'detect_leetspeak' => true,

    /*
    |--------------------------------------------------------------------------
    | Pattern Safety Limits
    |--------------------------------------------------------------------------
    |
    | These limits keep generated regular expressions predictable and prevent
    | unbounded separator/repeated-character matching on untrusted input.
    | Large word lists are compiled in smaller independent chunks.
    |
    */

    'max_separators' => 8,
    'max_repeated_characters' => 8,
    'pattern_chunk_size' => 250,
    'max_word_length' => 100,
    'max_words' => 10000,

    /*
    |--------------------------------------------------------------------------
    | Regex Failure Policy
    |--------------------------------------------------------------------------
    |
    | When enabled, a PCRE failure blocks the text instead of returning it
    | unfiltered. Disable only when preserving the legacy fail-open behavior.
    |
    */

    'fail_closed' => true,

    /*
    |--------------------------------------------------------------------------
    | Replacement Text
    |--------------------------------------------------------------------------
    |
    | The text used to replace filtered words. This is used with the 'fixed'
    | strategy. For other strategies, see the 'strategy' option below.
    |
    */

    'replacement' => '***',

    /*
    |--------------------------------------------------------------------------
    | Replacement Strategy
    |--------------------------------------------------------------------------
    |
    | Defines how bad words are replaced:
    |
    | - 'fixed'        : Replaces the entire word with the 'replacement' value.
    |                     Example: "badword" → "***"
    |
    | - 'character'     : Replaces each character with the 'character' value.
    |                     Example: "badword" → "*******"
    |
    | - 'length_match'  : Repeats the first character of 'replacement' to
    |                     match the word length.
    |                     Example (replacement='*'): "badword" → "*******"
    |
    */

    'strategy' => 'fixed',

    /*
    |--------------------------------------------------------------------------
    | Replacement Character
    |--------------------------------------------------------------------------
    |
    | The character used when 'strategy' is set to 'character'.
    | Each character in the bad word will be replaced with this character.
    |
    */

    'character' => '*',

];
