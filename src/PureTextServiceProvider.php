<?php

namespace YasserElgammal\PureText;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use YasserElgammal\PureText\Contracts\TextFilterInterface;
use YasserElgammal\PureText\Middleware\PureTextMiddleware;
use YasserElgammal\PureText\Rules\PureTextRule;
use YasserElgammal\PureText\Services\PureTextFilterService;

class PureTextServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/badwords.php' => config_path('badwords.php'),
        ], 'pure-text-config');

        // Register the validation rule: 'pure_text'
        Validator::extend('pure_text', function ($attribute, $value, $parameters, $validator) {
            $rule = new PureTextRule();

            $failed = false;
            $rule->validate($attribute, $value, function () use (&$failed) {
                $failed = true;
            });

            return !$failed;
        });

        Validator::replacer('pure_text', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, __('The :attribute contains prohibited words.'));
        });

        // Register the Blade directive: @pureText($text)
        Blade::directive('pureText', function (string $expression): string {
            return "<?php echo e(app(\YasserElgammal\PureText\Contracts\TextFilterInterface::class)->filter({$expression})); ?>";
        });

        // Register the middleware alias
        if (method_exists($this->app['router'], 'aliasMiddleware')) {
            $this->app['router']->aliasMiddleware('pure-text', PureTextMiddleware::class);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/badwords.php',
            'badwords'
        );

        $this->app->singleton(TextFilterInterface::class, function () {
            return new PureTextFilterService();
        });

        // Keep backward compatibility: resolve by class name too
        $this->app->alias(TextFilterInterface::class, PureTextFilterService::class);
    }
}
