# Lettr for Laravel

Official Laravel integration for the [Lettr](https://uselettr.com/) email API.

> **Requires [PHP 8.4+](https://php.net/releases/)**

## Installation

First install Lettr for Laravel via the [Composer](https://getcomposer.org/) package manager:

```bash
composer require lettr/lettr-laravel
```

Next, you should configure your [Lettr API key](https://app.uselettr.com) in your application's `.env` file:

```ini
LETTR_API_KEY=your-api-key
```

## Usage

### Setting Lettr as Default Mail Driver

Lettr for Laravel integrates seamlessly with Laravel's Mail system. To set Lettr as your **default mail driver**, follow these steps:

**Step 1:** Add the Lettr mailer configuration to your `config/mail.php` file in the `mailers` array:

```php
'mailers' => [
    // ... other mailers

    'lettr' => [
        'transport' => 'lettr',
    ],
],
```

> See `config/mail.example.php` for a complete example.

**Step 2:** Set Lettr as the default mailer in your `.env` file:

```ini
MAIL_MAILER=lettr
LETTR_API_KEY=your-api-key
```

> See `.env.example` for a complete example.

**Step 3:** Send emails using Laravel's Mail facade - all emails will now use Lettr automatically:

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

// Send using Mailable
Mail::to('recipient@example.com')
    ->send(new WelcomeEmail());

// Send using raw content
Mail::raw('Plain text email', function ($message) {
    $message->to('recipient@example.com')
            ->subject('Test Email');
});

// Send using HTML view
Mail::send('emails.welcome', ['name' => 'John'], function ($message) {
    $message->to('recipient@example.com')
            ->subject('Welcome!');
});
```

> **Note**
> Once `MAIL_MAILER=lettr` is set, **all** emails sent via Laravel's Mail facade will use Lettr as the transport.

### Using Lettr with Multiple Mail Drivers

If you have multiple mail drivers configured and want to use Lettr for specific emails only, you can specify the mailer:

```php
use Illuminate\Support\Facades\Mail;

// Use Lettr for this specific email
Mail::mailer('lettr')
    ->to('recipient@example.com')
    ->send(new WelcomeEmail());

// This will use the default mailer (e.g., SMTP)
Mail::to('other@example.com')
    ->send(new NewsletterEmail());
```

### Using the Lettr Facade

You can also use the `Lettr` facade to access the Lettr API directly:

```php
use Lettr\Laravel\Facades\Lettr;
use Lettr\Dto\SendEmailData;

$email = new SendEmailData(
    from: 'sender@example.com',
    to: ['recipient@example.com'],
    subject: 'Hello from Lettr',
    text: 'Plain text body',
    html: '<p>HTML body</p>',
);

$response = Lettr::emails()->send($email);

echo $response->id; // The email ID
```

Or using array syntax:

```php
use Lettr\Laravel\Facades\Lettr;
use Lettr\Dto\SendEmailData;

$email = SendEmailData::from([
    'from' => 'sender@example.com',
    'to' => ['recipient@example.com'],
    'subject' => 'Hello from Lettr',
    'text' => 'Plain text body',
    'html' => '<p>HTML body</p>',
]);

$response = Lettr::emails()->send($email);
```

## Examples

Check the `examples/` directory for complete examples:
- `examples/WelcomeEmail.php` - Example Mailable class
- `examples/SendEmailController.php` - Example controller with different sending methods

## Configuration

You can publish the configuration file using:

```bash
php artisan vendor:publish --tag=lettr-config
```

This will create a `config/lettr.php` file where you can configure your Lettr API key.

## Testing

```bash
composer test
```

## Code Style

```bash
composer lint
```

## Static Analysis

```bash
composer analyse
```

## License

MIT License. See [LICENSE](LICENSE) for more information.

