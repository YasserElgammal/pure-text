# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[2.0.0]: https://github.com/YasserElgammal/pure-text/compare/v1.0.3...v2.0.0
[1.0.3]: https://github.com/YasserElgammal/pure-text/releases/tag/v1.0.3
