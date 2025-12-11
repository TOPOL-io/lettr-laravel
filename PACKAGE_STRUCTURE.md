# Lettr Laravel Package Structure

This document describes the structure of the `lettr/lettr-laravel` package.

## Package Overview

This is a Laravel wrapper package for the `lettr/lettr-php` SDK that provides:
- Laravel Mail driver integration
- Facade for easy access to the Lettr API
- Service provider for automatic registration
- Configuration file for API key management

## Directory Structure

```
lettr-laravel/
├── config/
│   └── lettr.php                    # Configuration file
├── src/
│   ├── Exceptions/
│   │   └── ApiKeyIsMissing.php      # Exception for missing API key
│   ├── Facades/
│   │   └── Lettr.php                # Facade for Lettr client
│   ├── Transport/
│   │   └── LettrTransportFactory.php # Symfony Mailer transport
│   └── LettrServiceProvider.php     # Laravel service provider
├── tests/
│   ├── Unit/
│   │   ├── LettrServiceProviderTest.php
│   │   └── LettrTransportTest.php
│   ├── Pest.php                     # Pest configuration
│   └── TestCase.php                 # Base test case
├── .editorconfig
├── .gitattributes
├── .gitignore
├── composer.json
├── LICENSE
├── phpstan.neon                     # PHPStan configuration
├── phpunit.xml                      # PHPUnit configuration
├── pint.json                        # Laravel Pint configuration
└── README.md
```

## Key Components

### Service Provider (`src/LettrServiceProvider.php`)
- Registers the Lettr client as a singleton in the container
- Extends Laravel's Mail system with the 'lettr' transport
- Publishes configuration file
- Handles API key configuration from `.env` or config files

### Mail Transport (`src/Transport/LettrTransportFactory.php`)
- Implements Symfony Mailer's `AbstractTransport`
- Converts Symfony Email objects to Lettr's `SendEmailData` DTO
- Sends emails using the Lettr API
- Only implements the `send()` method as requested

### Facade (`src/Facades/Lettr.php`)
- Provides static access to the Lettr client
- Type-hinted for IDE support

### Configuration (`config/lettr.php`)
- Simple configuration file with API key setting
- Reads from `LETTR_API_KEY` environment variable

## Usage

### Installation

```bash
composer require lettr/lettr-laravel
```

### Configuration

Add to `.env`:
```
LETTR_API_KEY=your-api-key
```

### Using as Mail Driver

In `config/mail.php`:
```php
'lettr' => [
    'transport' => 'lettr',
],
```

In `.env`:
```
MAIL_MAILER=lettr
```

Then use Laravel's Mail facade:
```php
Mail::to('user@example.com')->send(new WelcomeEmail());
```

### Using the Facade

```php
use Lettr\Laravel\Facades\Lettr;
use Lettr\Dto\SendEmailData;

$email = new SendEmailData(
    from: 'sender@example.com',
    to: ['recipient@example.com'],
    subject: 'Hello',
    text: 'Plain text',
    html: '<p>HTML</p>',
);

$response = Lettr::emails()->send($email);
```

## Development

### Running Tests
```bash
composer test
```

### Code Style
```bash
composer lint
```

### Static Analysis
```bash
composer analyse
```

## Dependencies

### Production
- PHP ^8.4
- Laravel ^10.0|^11.0|^12.0
- lettr/lettr-php:dev-main
- symfony/mailer ^6.2|^7.0

### Development
- laravel/pint ^1.18
- larastan/larastan ^2.0|^3.0
- pestphp/pest ^2.0|^3.7
- orchestra/testbench ^8.17|^9.0|^10.8

## Notes

- The package only implements the `send()` method from lettr-php as requested
- Follows the same pattern as resend-laravel for consistency
- Uses Pint, Larastan, and Pest as specified
- Auto-discovery is enabled via composer.json extra section

