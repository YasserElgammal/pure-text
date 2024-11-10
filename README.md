# PureText

**PureText** is a Laravel package for filtering and replacing inappropriate or unwanted words within model attributes automatically. Designed to be customizable and efficient, PureText allows developers to specify filterable attributes for each model and handle multiple languages seamlessly. With a simple setup and flexible configuration, this package is ideal for applications requiring automatic content moderation.

---

## Features

- **Automatic Filtering**: Automatically filters designated model attributes upon saving.
- **Customizable Words List**: Easily modify the list of inappropriate words and replacements from the config file.
- **Language Support**: Works with multiple languages, including Arabic and other non-Latin character sets.
- **Trait Integration**: Apply the `Filterable` trait to models, specifying which attributes should be filtered.
- **Service Provider Configuration**: Provides easy configuration via a service provider and includes a singleton service for optimal performance.

## Installation

1. Install the package via Composer:

    ```bash
    composer require yasser-elgammal/pure-text
    ```

2. Publish the configuration file:

    ```bash
    php artisan vendor:publish --provider="YasserElgammal\PureText\PureTextServiceProvider"
    ```

3. Configure your list of words to filter in the `config/badwords.php` file.

## Usage

1. **Add the Trait to Your Model**

   Use the `PureTextFilterable` trait in any model where you need to filter specific attributes.

    ```php
    use YasserElgammal\PureText\Traits\PureTextFilterable;

    class Post extends Model
    {
        use PureTextFilterable;

        protected $filterable = ['title', 'content'];
    }
    ```

2. **Configuring Filterable Attributes**

   Define `protected $filterable` on the model with an array of attribute names you want to filter.

## Configuration

The configuration file `badwords.php` allows you to define:
- `words`: An array of bad words that should be filtered.
- `replacement`: The replacement text for filtered words, defaulting to `***`.

## Example

Here's a basic example of usage in a controller:

```php
$post = new Post();
$post->title = "This is a badword example";
$post->content = "Some more text with badword";
$post->save();

echo $post->title; // Outputs: This is a ***
```

## Contributing

Contributions are welcome! Please fork this repository and submit a pull request for any improvements.
