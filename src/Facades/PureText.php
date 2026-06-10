<?php

namespace YasserElgammal\PureText\Facades;

use Illuminate\Support\Facades\Facade;
use YasserElgammal\PureText\Contracts\TextFilterInterface;

/**
 * @method static string filter(string $text)
 * @method static bool containsBadWords(string $text)
 * @method static array getBadWords(string $text)
 *
 * @see \YasserElgammal\PureText\Services\PureTextFilterService
 */
class PureText extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TextFilterInterface::class;
    }
}
