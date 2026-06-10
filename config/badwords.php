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
    | Used only with the 'character' strategy. Each character of the bad word
    | will be replaced with this character.
    |
    */

    'character' => '*',

];
