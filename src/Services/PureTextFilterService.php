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

        return str_ireplace($badWords, $replacement, $text);
    }
}
