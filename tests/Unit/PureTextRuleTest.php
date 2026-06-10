<?php

namespace YasserElgammal\PureText\Tests\Unit;

use Illuminate\Support\Facades\Config;
use YasserElgammal\PureText\Rules\PureTextRule;
use YasserElgammal\PureText\Tests\TestCase;

class PureTextRuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('badwords.words', ['bad', 'ugly']);
        Config::set('badwords.replacement', '***');
        Config::set('badwords.strategy', 'fixed');
    }

    public function test_validation_fails_when_text_contains_bad_words(): void
    {
        $rule = new PureTextRule();
        $failed = false;

        $rule->validate('content', 'This is bad', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_validation_passes_when_text_is_clean(): void
    {
        $rule = new PureTextRule();
        $failed = false;

        $rule->validate('content', 'This is good', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_validation_passes_for_non_string_value(): void
    {
        $rule = new PureTextRule();
        $failed = false;

        $rule->validate('content', 123, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_pure_text_validation_rule_works_via_validator(): void
    {
        $validator = validator(
            ['content' => 'This is bad text'],
            ['content' => 'required|pure_text']
        );

        $this->assertTrue($validator->fails());
    }

    public function test_pure_text_validation_rule_passes_clean_text(): void
    {
        $validator = validator(
            ['content' => 'This is clean text'],
            ['content' => 'required|pure_text']
        );

        $this->assertFalse($validator->fails());
    }
}
