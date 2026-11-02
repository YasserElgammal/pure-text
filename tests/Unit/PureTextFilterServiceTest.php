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

    // ─── Arabic Normalization ────────────────────────────────

    public function test_normalizes_arabic_tashkeel_diacritics(): void
    {
        Config::set('badwords.words', ['سيء']);
        Config::set('badwords.replacement', '***');
        Config::set('badwords.strategy', 'fixed');
        $this->service->resetPattern();

        // Harakat (fatha, kasra, shadda, tanween)
        $this->assertEquals('هذا *** جداً', $this->service->filter('هذا سَيِّءٌ جداً'));
    }

    public function test_normalizes_arabic_tatweel_kashida(): void
    {
        Config::set('badwords.words', ['سيء']);
        Config::set('badwords.replacement', '***');
        $this->service->resetPattern();

        // Middle tatweel: ســــيء
        $this->assertEquals('هذا *** جداً', $this->service->filter('هذا ســــيء جداً'));

        // Combination of tatweel and tashkeel
        $this->assertEquals('هذا *** جداً', $this->service->filter('هذا سـَـيـّـء جداً'));
    }

    public function test_normalizes_arabic_tatweel_at_boundaries(): void
    {
        Config::set('badwords.words', ['سيء']);
        Config::set('badwords.replacement', '***');
        $this->service->resetPattern();

        // Leading and trailing tatweel: ـسيءـ
        $this->assertEquals('هذا *** جداً', $this->service->filter('هذا ـسيءـ جداً'));
    }

    public function test_normalizes_arabic_alef_variants(): void
    {
        Config::set('badwords.words', ['احمق']);
        Config::set('badwords.replacement', '***');
        $this->service->resetPattern();

        // Matches أ, إ, آ, ٱ
        $this->assertEquals('هذا ***', $this->service->filter('هذا أحمق'));
        $this->assertEquals('هذا ***', $this->service->filter('هذا إحمق'));
        $this->assertEquals('هذا ***', $this->service->filter('هذا آحمق'));
    }

    public function test_normalizes_arabic_taa_marbuta(): void
    {
        Config::set('badwords.words', ['سفيهه']);
        Config::set('badwords.replacement', '***');
        $this->service->resetPattern();

        $this->assertEquals('هذه ***', $this->service->filter('هذه سفيهة'));
    }

    public function test_normalizes_arabic_alef_maqsura(): void
    {
        Config::set('badwords.words', ['قذر']);
        Config::set('badwords.replacement', '***');
        $this->service->resetPattern();

        Config::set('badwords.words', ['اذي']);
        $this->service->resetPattern();

        $this->assertEquals('هذا ***', $this->service->filter('هذا أذى'));
    }

    public function test_disabling_arabic_normalization(): void
    {
        Config::set('badwords.words', ['احمق']);
        Config::set('badwords.replacement', '***');
        Config::set('badwords.normalize_arabic', false);
        $this->service->resetPattern();

        // When disabled, "أحمق" with hamza does NOT match normalized "احمق"
        $this->assertEquals('هذا أحمق', $this->service->filter('هذا أحمق'));
    }

    // ─── Leetspeak Detection ─────────────────────────────────

    public function test_detects_leetspeak_digits(): void
    {
        Config::set('badwords.words', ['bad', 'porn']);
        Config::set('badwords.replacement', '***');
        $this->service->resetPattern();

        $this->assertEquals('This is ***', $this->service->filter('This is b4d'));
        $this->assertEquals('This is ***', $this->service->filter('This is p0rn'));
        $this->assertEquals('This is ***', $this->service->filter('This is 84d'));
    }

    public function test_detects_leetspeak_symbols(): void
    {
        Config::set('badwords.words', ['ass', 'shit']);
        Config::set('badwords.replacement', '***');
        $this->service->resetPattern();

        $this->assertEquals('You are an ***', $this->service->filter('You are an @ss'));
        $this->assertEquals('You are an ***', $this->service->filter('You are an a$$'));
        $this->assertEquals('Holy ***!', $this->service->filter('Holy sh!t!'));
    }

    public function test_disabling_leetspeak_detection(): void
    {
        Config::set('badwords.words', ['bad']);
        Config::set('badwords.replacement', '***');
        Config::set('badwords.detect_leetspeak', false);
        $this->service->resetPattern();

        // When disabled, "b4d" should remain unreplaced
        $this->assertEquals('This is b4d', $this->service->filter('This is b4d'));
    }

    // ─── Whitelist / Allowed Words ───────────────────────────

    public function test_whitelist_prevents_false_positives_in_filter(): void
    {
        Config::set('badwords.words', ['cock', 'bad']);
        Config::set('badwords.whitelist', ['cocktail']);
        Config::set('badwords.replacement', '***');
        $this->service->resetPattern();

        // "cocktail" should not be touched, but "bad" should be replaced
        $this->assertEquals(
            'Order a cocktail at the *** bar',
            $this->service->filter('Order a cocktail at the bad bar')
        );
    }

    public function test_whitelist_preserves_original_casing(): void
    {
        Config::set('badwords.words', ['cock']);
        Config::set('badwords.whitelist', ['cocktail']);
        Config::set('badwords.replacement', '***');
        $this->service->resetPattern();

        $this->assertEquals(
            'Enjoy your Cocktail and COCKTAIL',
            $this->service->filter('Enjoy your Cocktail and COCKTAIL')
        );
    }

    public function test_whitelist_prevents_false_positives_in_contains_bad_words(): void
    {
        Config::set('badwords.words', ['cock']);
        Config::set('badwords.whitelist', ['cocktail']);
        $this->service->resetPattern();

        $this->assertFalse($this->service->containsBadWords('I love drinking cocktail'));
        $this->assertTrue($this->service->containsBadWords('I love drinking cocktail and cock'));
    }

    public function test_whitelist_excludes_allowed_words_from_get_bad_words(): void
    {
        Config::set('badwords.words', ['cock', 'bad']);
        Config::set('badwords.whitelist', ['cocktail']);
        $this->service->resetPattern();

        $result = $this->service->getBadWords('I ordered a cocktail and bad wine');

        $this->assertEquals(['bad'], array_values($result));
    }

    public function test_runtime_with_whitelist_is_immutable_and_fluent(): void
    {
        Config::set('badwords.words', ['bad']);
        Config::set('badwords.whitelist', []);
        $this->service->resetPattern();

        // Without whitelist, "badlands" is filtered if "bad" matches or if whitelisted
        $customService = $this->service->withWhitelist(['badlands']);

        $this->assertFalse($customService->containsBadWords('Visiting badlands'));
        // Original singleton was not mutated
        $this->assertEmpty($this->service->getWhitelist());
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
