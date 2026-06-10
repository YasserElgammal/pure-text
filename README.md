# PureText

[![Tests](https://github.com/YasserElgammal/pure-text/actions/workflows/tests.yml/badge.svg)](https://github.com/YasserElgammal/pure-text/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/yasser-elgammal/pure-text.svg)](https://packagist.org/packages/yasser-elgammal/pure-text)
[![License](https://img.shields.io/packagist/l/yasser-elgammal/pure-text.svg)](https://packagist.org/packages/yasser-elgammal/pure-text)
[![PHP Version](https://img.shields.io/packagist/php-v/yasser-elgammal/pure-text.svg)](https://packagist.org/packages/yasser-elgammal/pure-text)

**PureText** is a Laravel package for filtering and replacing inappropriate or unwanted words within model attributes automatically. Designed to be customizable and efficient, PureText allows developers to specify filterable attributes for each model and handle multiple languages seamlessly — including **Arabic and other Unicode languages**.

---

## ✨ Features

- **Automatic Model Filtering** — Apply the `PureTextFilterable` trait and bad words are filtered on save.
- **Multiple Replacement Strategies** — Choose between `fixed`, `character`, or `length_match` replacements.
- **Unicode & Arabic Support** — Proper Unicode-aware word boundaries for non-Latin languages.
- **Evasion Detection** — Catches common tricks like `b-a-d`, `b_a_d`, `b.a.d`.
- **Facade** — `PureText::filter()`, `PureText::containsBadWords()`, `PureText::getBadWords()`.
- **Blade Directive** — `@pureText($text)` for templates.
- **Middleware** — Filter request fields automatically via route middleware.
- **Validation Rule** — `pure_text` rule for Laravel Validator.
- **Helper Functions** — `pure_text()` and `has_bad_words()` global helpers.
- **Events** — `TextFiltered` event dispatched on model attribute filtering.
- **Customizable** — Bring your own implementation via `TextFilterInterface`.

---

## 📋 Requirements

- PHP 8.1+
- Laravel 10, 11, or 12

---

## 📦 Installation

Install via Composer:

```bash
composer require yasser-elgammal/pure-text
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=pure-text-config
```

Configure your word list in `config/badwords.php`.

---

## 🚀 Usage

### 1. Automatic Model Filtering (Trait)

Add the `PureTextFilterable` trait to any model and define `$filterable` attributes:

```php
use YasserElgammal\PureText\Traits\PureTextFilterable;

class Post extends Model
{
    use PureTextFilterable;

    protected $filterable = ['title', 'content'];
}
```

Now, bad words are automatically replaced when saving:

```php
$post = new Post();
$post->title = "This is a badword example";
$post->save();

echo $post->title; // "This is a *** example"
```

### 2. Facade

```php
use YasserElgammal\PureText\Facades\PureText;

// Filter text
$clean = PureText::filter('Some bad text');

// Check if text contains bad words
if (PureText::containsBadWords($text)) {
    // handle it
}

// Get all bad words found
$words = PureText::getBadWords($text); // ['bad', 'ugly']
```

### 3. Blade Directive

```blade
<p>@pureText($post->content)</p>
```

### 4. Validation Rule

```php
$request->validate([
    'title'   => 'required|pure_text',
    'content' => 'required|pure_text',
]);
```

### 5. Middleware

Register in your route:

```php
// Filter all string fields
Route::post('/comment', [CommentController::class, 'store'])
    ->middleware('pure-text');

// Filter specific fields only
Route::post('/comment', [CommentController::class, 'store'])
    ->middleware('pure-text:title,content');
```

### 6. Helper Functions

```php
// Filter text
$clean = pure_text('Some bad text');

// Check for bad words
if (has_bad_words($text)) {
    // handle it
}
```

---

## ⚙️ Configuration

After publishing, edit `config/badwords.php`:

```php
return [
    // List of bad words to filter
    'words' => ['badword1', 'badword2'],

    // Replacement text (used with 'fixed' strategy)
    'replacement' => '***',

    // Strategy: 'fixed', 'character', or 'length_match'
    'strategy' => 'fixed',

    // Character used with 'character' strategy
    'character' => '*',
];
```

### Replacement Strategies

| Strategy | Input | Output | Description |
|----------|-------|--------|-------------|
| `fixed` | `badword` | `***` | Replaces with the `replacement` value |
| `character` | `badword` | `*******` | Each character replaced with `character` |
| `length_match` | `badword` | `*******` | First char of `replacement` repeated to match length |

---

## 🔌 Advanced: Custom Implementation

You can swap the filter service with your own by binding `TextFilterInterface`:

```php
use YasserElgammal\PureText\Contracts\TextFilterInterface;

$this->app->singleton(TextFilterInterface::class, function () {
    return new MyCustomFilterService();
});
```

---

## 📡 Events

When a model attribute is filtered via the `PureTextFilterable` trait, a `TextFiltered` event is dispatched:

```php
use YasserElgammal\PureText\Events\TextFiltered;

class TextFilteredListener
{
    public function handle(TextFiltered $event): void
    {
        logger('Filtered text on model', [
            'original'  => $event->originalText,
            'filtered'  => $event->filteredText,
            'model'     => $event->model?->getTable(),
            'attribute' => $event->attribute,
        ]);
    }
}
```

---

## 🧪 Testing

```bash
composer test
```

---

## 📄 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

## 🤝 Contributing

Contributions are welcome! Please fork this repository and submit a pull request for any improvements.

## 📜 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
