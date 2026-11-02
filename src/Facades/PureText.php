<?php

namespace YasserElgammal\PureText\Facades;

use Illuminate\Support\Facades\Facade;
use YasserElgammal\PureText\Contracts\TextFilterInterface;

/**
 * @method static string filter(string $text)
 * @method static bool containsBadWords(string $text)
 * @method static array getBadWords(string $text)
 * @method static \YasserElgammal\PureText\Contracts\TextFilterInterface withWhitelist(array $words)
 * @method static \YasserElgammal\PureText\Contracts\TextFilterInterface whitelist(array $words)
 * @method static \YasserElgammal\PureText\Services\PureTextFilterService setWhitelist(array $words)
 * @method static array getWhitelist()
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
