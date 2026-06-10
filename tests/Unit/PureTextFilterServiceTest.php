<?php

namespace YasserElgammal\PureText\Tests\Unit;

use Illuminate\Support\Facades\Config;
use YasserElgammal\PureText\Contracts\TextFilterInterface;
use YasserElgammal\PureText\Services\PureTextFilterService;
use YasserElgammal\PureText\Tests\TestCase;

class PureTextFilterServiceTest extends TestCase
{
    protected PureTextFilterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TextFilterInterface::class);
        $this->service->resetPattern();
    }

    // ─── filter() ────────────────────────────────────────────

    public function test_filters_bad_words_with_fixed_strategy(): void
    {
        Config::set('badwords.words', ['bad', 'ugly']);
        Config::set('badwords.replacement', '***');
        Config::set('badwords.strategy', 'fixed');
        $this->service->resetPattern();

        $this->assertEquals('This is ***', $this->service->filter('This is bad'));
        $this->assertEquals('*** and ***', $this->service->filter('bad and ugly'));
    }

    public function test_filters_bad_words_with_character_strategy(): void
    {
        Config::set('badwords.words', ['damn']);
        Config::set('badwords.strategy', 'character');
        Config::set('badwords.character', '#');
        $this->service->resetPattern();

        $this->assertEquals('Oh ####!', $this->service->filter('Oh damn!'));
    }

    public function test_filters_bad_words_with_length_match_strategy(): void
    {
        Config::set('badwords.words', ['hello']);
        Config::set('badwords.replacement', '*');
        Config::set('badwords.strategy', 'length_match');
        $this->service->resetPattern();

        $this->assertEquals('***** world', $this->service->filter('hello world'));
    }

    public function test_returns_text_unchanged_when_no_bad_words_configured(): void
    {
        Config::set('badwords.words', []);
        $this->service->resetPattern();

        $this->assertEquals('Hello world', $this->service->filter('Hello world'));
    }

    public function test_returns_text_unchanged_when_no_match(): void
    {
        Config::set('badwords.words', ['bad']);
        $this->service->resetPattern();

        $this->assertEquals('This is good', $this->service->filter('This is good'));
    }

    public function test_filter_is_case_insensitive(): void
    {
        Config::set('badwords.words', ['bad']);
        Config::set('badwords.replacement', '***');
        Config::set('badwords.strategy', 'fixed');
        $this->service->resetPattern();

        $this->assertEquals('This is ***', $this->service->filter('This is BAD'));
        $this->assertEquals('This is ***', $this->service->filter('This is Bad'));
    }

    public function test_handles_arabic_text(): void
    {
        Config::set('badwords.words', ['سيء']);
        Config::set('badwords.replacement', '***');
        Config::set('badwords.strategy', 'fixed');
        $this->service->resetPattern();

        $this->assertEquals('هذا *** جداً', $this->service->filter('هذا سيء جداً'));
    }

    public function test_handles_regex_special_characters_in_words(): void
    {
        Config::set('badwords.words', ['b.d', 'w+rd']);
        Config::set('badwords.replacement', '***');
        Config::set('badwords.strategy', 'fixed');
        $this->service->resetPattern();

        // Should match literal "b.d" not "bad" (the dot should be escaped)
        $this->assertEquals('this is ***', $this->service->filter('this is b.d'));
    }

    public function test_detects_evasion_with_special_characters(): void
    {
        Config::set('badwords.words', ['bad']);
        Config::set('badwords.replacement', '***');
        Config::set('badwords.strategy', 'fixed');
        $this->service->resetPattern();

        // b-a-d, b.a.d, b_a_d style evasion
        $this->assertEquals('this is ***', $this->service->filter('this is b-a-d'));
        $this->assertEquals('this is ***', $this->service->filter('this is b_a_d'));
    }

    // ─── containsBadWords() ──────────────────────────────────

    public function test_contains_bad_words_returns_true_when_found(): void
    {
        Config::set('badwords.words', ['bad']);
        $this->service->resetPattern();

        $this->assertTrue($this->service->containsBadWords('This is bad'));
    }

    public function test_contains_bad_words_returns_false_when_clean(): void
    {
        Config::set('badwords.words', ['bad']);
        $this->service->resetPattern();

        $this->assertFalse($this->service->containsBadWords('This is good'));
    }

    public function test_contains_bad_words_returns_false_when_no_words_configured(): void
    {
        Config::set('badwords.words', []);
        $this->service->resetPattern();

        $this->assertFalse($this->service->containsBadWords('anything'));
    }

    // ─── getBadWords() ───────────────────────────────────────

    public function test_get_bad_words_returns_matched_words(): void
    {
        Config::set('badwords.words', ['bad', 'ugly']);
        $this->service->resetPattern();

        $result = $this->service->getBadWords('This is bad and ugly text');

        $this->assertContains('bad', $result);
        $this->assertContains('ugly', $result);
        $this->assertCount(2, $result);
    }

    public function test_get_bad_words_returns_empty_when_clean(): void
    {
        Config::set('badwords.words', ['bad']);
        $this->service->resetPattern();

        $this->assertEmpty($this->service->getBadWords('This is good'));
    }

    public function test_get_bad_words_returns_empty_when_no_words_configured(): void
    {
        Config::set('badwords.words', []);
        $this->service->resetPattern();

        $this->assertEmpty($this->service->getBadWords('anything'));
    }
}
