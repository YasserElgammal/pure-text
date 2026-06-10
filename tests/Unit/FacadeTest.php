<?php

namespace YasserElgammal\PureText\Tests\Unit;

use YasserElgammal\PureText\Facades\PureText;
use YasserElgammal\PureText\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class FacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('badwords.words', ['bad']);
        Config::set('badwords.replacement', '***');
        Config::set('badwords.strategy', 'fixed');
    }

    public function test_facade_filter_method(): void
    {
        $this->assertEquals('This is ***', PureText::filter('This is bad'));
    }

    public function test_facade_contains_bad_words_method(): void
    {
        $this->assertTrue(PureText::containsBadWords('This is bad'));
        $this->assertFalse(PureText::containsBadWords('This is good'));
    }

    public function test_facade_get_bad_words_method(): void
    {
        $result = PureText::getBadWords('This is bad');
        $this->assertContains('bad', $result);
    }
}
