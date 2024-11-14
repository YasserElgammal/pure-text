<?php

namespace YasserElgammal\PureText\Services;

use Illuminate\Support\Facades\Config;

class PureTextFilterService
{
    /**
     * Replace bad words in the given text with a specified replacement.
     *
     * @param string $text
     * @return string
     */
    public function filter($text)
    {
        $badWords = Config::get('badwords.words', []);
        $replacement = Config::get('badwords.replacement', '***');

        if (empty($badWords)) {
            return $text; // Return text as is if no bad words are configured
        }

        $pattern = '/' . implode('|', array_map(function ($word) {
            return '\b' . implode('[\W_]*', array_map(function ($char) {
                return $char . '{1,}';
            }, preg_split('//u', preg_quote($word), -1, PREG_SPLIT_NO_EMPTY))) . '\b';
        }, $badWords)) . '/iu';

        return preg_replace($pattern, $replacement, $text);
    }
}
