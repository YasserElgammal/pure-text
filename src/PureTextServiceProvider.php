<?php

namespace YasserElgammal\PureText;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use YasserElgammal\PureText\Rules\PureTextRule;
use YasserElgammal\PureText\Services\PureTextFilterService;

class PureTextServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/badwords.php' => config_path('badwords.php'),
        ], 'config');

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
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/badwords.php',
            'badwords'
        );

        $this->app->singleton(PureTextFilterService::class, function () {
            return new PureTextFilterService();
        });
    }
}
