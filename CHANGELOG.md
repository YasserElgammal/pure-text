# Changelog

All notable changes to this project will be documented in this file.

## [2.1.0] - 2026-11-03

### Added
- **Laravel 13 Support**:
  - Added support for Laravel 13 (`illuminate/support: ^13.0`, `illuminate/validation: ^13.0`).
  - Added support for `orchestra/testbench: ^11.0` and `phpunit/phpunit: ^12.0`.
  - Updated GitHub Actions CI test matrix to test against Laravel 13.
- **Whitelist / Allowed Words**:
  - Global whitelist configuration in `config/badwords.php` to prevent the Scunthorpe problem (false positives on innocent words like `cocktail`, `classic`, `document`).
  - Runtime fluent whitelist support via `PureText::withWhitelist(array $words)` and `PureText::whitelist(array $words)`.
  - Case-preserving restoration: whitelisted words retain their original casing after filtering.
  - Whitelist parameters support in `pure_text` validation rule (e.g. `'pure_text:cocktail,wine'`) and constructor `new PureTextRule(['cocktail'])`.
- **Arabic & Unicode Normalization**:
  - Full Arabic diacritics (*tashkeel*) normalization: strips fatha, damma, kasra, tanween, shadda, sukun, and dagger alef during matching.
  - Tatweel / kashida (`ـ`) evasion detection in middle and at word boundaries (`ســــيء`, `ـسيءـ`).
  - Letter variant canonicalization: Alef forms (`أ`, `إ`, `آ`, `ٱ` $\rightarrow$ `ا`), Taa Marbuta / Haa (`ة` $\leftrightarrow$ `ه`), and Alef Maqsura / Yaa (`ى` $\leftrightarrow$ `ي`).
  - Configurable toggle via `'normalize_arabic' => true`.
- **Leetspeak & Homoglyph Evasion Detection**:
  - Catches evasion using digit substitutions (`b4d`, `sh1t`, `p0rn`, `84d`, `1337`).
  - Catches evasion using symbol substitutions (`@ss`, `sh!t`, `a$$`).
  - Configurable toggle via `'detect_leetspeak' => true`.
- Comprehensive unit tests covering Arabic normalization, leetspeak detection, whitelist filtering, and validation rule parameters.

## [2.0.0] - 2026-06-10

### Added
- `TextFilterInterface` contract for dependency inversion and custom implementations.
- `PureText` Facade for convenient static access (`PureText::filter()`, `PureText::containsBadWords()`).
- `containsBadWords(string $text): bool` method to check without replacing.
- `getBadWords(string $text): array` method to extract matched bad words.
- `PureTextMiddleware` for filtering request fields via route middleware.
- `@pureText()` Blade directive for template usage.
- `pure_text()` and `has_bad_words()` global helper functions.
- `TextFiltered` event dispatched when model attributes are filtered.
- Multiple replacement strategies: `fixed`, `character`, `length_match`.
- Pattern caching for improved performance.
- Full PHP type declarations throughout.
- Unit tests with PHPUnit + Orchestra Testbench.
- GitHub Actions CI workflow.
- `LICENSE`, `CHANGELOG.md`, `.editorconfig` files.

### Fixed
- **Unicode/Arabic support**: Replaced `\b` word boundaries with Unicode-aware boundaries (`\p{L}`) for proper Arabic and non-Latin language support.
- **Regex safety**: Fixed `preg_quote()` ordering — now applied per-character after splitting, not before.
- **Null safety**: `preg_replace` returning `null` on error now falls back to original text with a logged warning.
- **Singleton consistency**: `PureTextRule` now resolves `PureTextFilterService` from the container instead of creating a new instance.
- **README**: Fixed unclosed code block.

### Changed
- `PureTextFilterService` now implements `TextFilterInterface`.
- Service is bound to `TextFilterInterface` in the container (backward compatible via alias).
- Config publish tag renamed from `config` to `pure-text-config`.
- `minimum-stability` changed from `dev` to `stable`.
- Bumped version to 2.0.0.

## [1.0.3] - 2024-08-09

### Added
- Custom validation rule `pure_text`.
- `PureTextFilterable` trait for automatic model attribute filtering.
- Basic bad word filtering with regex.

[2.1.0]: https://github.com/YasserElgammal/pure-text/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/YasserElgammal/pure-text/compare/v1.0.3...v2.0.0
[1.0.3]: https://github.com/YasserElgammal/pure-text/releases/tag/v1.0.3
