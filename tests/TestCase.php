<?php

namespace YasserElgammal\PureText\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use YasserElgammal\PureText\PureTextServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PureTextServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'PureText' => \YasserElgammal\PureText\Facades\PureText::class,
        ];
    }
}
