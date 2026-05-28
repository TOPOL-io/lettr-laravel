# Lettr for Laravel

[![CI](https://github.com/TOPOL-io/lettr-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/TOPOL-io/lettr-laravel/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/lettr/lettr-laravel.svg)](https://packagist.org/packages/lettr/lettr-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/lettr/lettr-laravel.svg)](https://packagist.org/packages/lettr/lettr-laravel)
[![PHP Version](https://img.shields.io/packagist/php-v/lettr/lettr-laravel.svg)](https://packagist.org/packages/lettr/lettr-laravel)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Official Laravel integration for the [Lettr](https://lettr.com) email API.

## Requirements

- PHP 8.4+
- Laravel 10.x, 11.x, 12.x, or 13.x

## Installation

```bash
composer require lettr/lettr-laravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=lettr-config
```

## Getting Started

The easiest way to set up Lettr in your Laravel application is using the interactive init command:

```bash
php artisan lettr:init
```

This command will guide you through:

- **API Key Configuration** - Automatically adds your Lettr API key to `.env`
- **Mailer Setup** - Configures the Lettr mailer in `config/mail.php`
- **Template Download** - Optionally pulls your email templates as Blade files
- **Code Generation** - Generates type-safe DTOs, Mailables, and template enums
- **Domain Verification** - Checks your sending domain is properly configured

> **Tip:** If you already have a verified sending domain in your [Lettr account](https://app.lettr.com/domains/sending), the init command will automatically configure your `MAIL_FROM_ADDRESS` to match it.

After running `lettr:init`, you're ready to send emails:

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\Lettr\WelcomeEmail;

// Using a generated Mailable
Mail::to('user@example.com')->send(new WelcomeEmail($data));

// Or send templates inline
Mail::lettr()->to('user@example.com')->sendTemplate('welcome-email', substitutionData: $data);
```

## Manual Setup

If you prefer to configure manually, add your [Lettr API key](https://app.lettr.com) to your `.env` file:

```ini
LETTR_API_KEY=your-api-key
```

### Sending Domain

To send emails through Lettr, you must have a verified sending domain in your [Lettr account](https://app.lettr.com/domains/sending). Your `MAIL_FROM_ADDRESS` (or any "from" address you use) must match a verified domain.

For example, if you've verified `example.com` in Lettr:

```ini
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="My App"
```

Emails sent from addresses on unverified domains will be rejected.

## Quick Start

### Using Laravel Mail (Recommended)

Add the Lettr mailer to your `config/mail.php`:

```php
'mailers' => [
    // ... other mailers

    'lettr' => [
        'transport' => 'lettr',
    ],
],
```

Set as default in `.env`:

```ini
MAIL_MAILER=lettr
```

Send emails using Laravel's Mail facade:

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

Mail::to('recipient@example.com')->send(new WelcomeEmail());
```

### Using the Lettr Facade Directly

```php
use Lettr\Laravel\Facades\Lettr;

$response = Lettr::emails()->send(
    Lettr::emails()->create()
        ->from('sender@example.com', 'Sender Name')
        ->to(['recipient@example.com'])
        ->subject('Hello from Lettr')
        ->html('<h1>Hello!</h1><p>This is a test email.</p>')
);

echo $response->requestId; // Request ID for tracking
echo $response->accepted;  // Number of accepted recipients
```

## Laravel Mail Integration

### With Mailable Classes

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmation;

// Send using Mailable
Mail::to('customer@example.com')
    ->cc('sales@example.com')
    ->bcc('records@example.com')
    ->send(new OrderConfirmation($order));
```

### With Raw Content

```php
Mail::raw('Plain text email content', function ($message) {
    $message->to('recipient@example.com')
            ->subject('Quick Update');
});
```

### With Views

```php
Mail::send('emails.welcome', ['user' => $user], function ($message) {
    $message->to('recipient@example.com')
            ->subject('Welcome!');
});
```

### Multiple Mail Drivers

Use Lettr for specific emails while keeping another default:

```php
// Use Lettr for this specific email
Mail::mailer('lettr')
    ->to('recipient@example.com')
    ->send(new TransactionalEmail());

// Uses default mailer
Mail::to('other@example.com')
    ->send(new MarketingEmail());
```

## Using Lettr Templates with Mailables

Instead of using Blade views, you can send emails using Lettr templates directly. Extend the `LettrMailable` class:

```php
<?php

namespace App\Mail;

use Lettr\Laravel\Mail\LettrMailable;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeEmail extends LettrMailable
{
    public function __construct(
        public string $userName,
        public string $activationUrl,
    ) {}

    public function build(): static
    {
        return $this
            ->template('welcome-email', version: 2)
            ->substitutionData([
                'user_name' => $this->userName,
                'activation_url' => $this->activationUrl,
            ]);
    }
}
```

Then send it like any other Mailable:

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

Mail::to('user@example.com')
    ->send(new WelcomeEmail(
        userName: 'John',
        activationUrl: 'https://example.com/activate/abc123'
    ));
```

### LettrMailable Methods

| Method | Description |
|--------|-------------|
| `template($slug, $version)` | Set template slug with optional version |
| `templateVersion($version)` | Set template version separately |
| `substitutionData($data)` | Set substitution variables for the template |
| `customHeaders($headers)` | Set custom email headers |
| `scheduledAt($when)` | Schedule delivery for a future `DateTimeInterface` (or ISO-8601 string) |

### Example: Order Confirmation

```php
class OrderConfirmation extends LettrMailable
{
    public function __construct(
        public Order $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order #{$this->order->id} Confirmed",
        );
    }

    public function build(): static
    {
        return $this
            ->template('order-confirmation')
            ->substitutionData([
                'order_id' => $this->order->id,
                'customer_name' => $this->order->customer->name,
                'items' => $this->order->items->map(fn ($item) => [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->formatted_price,
                ])->toArray(),
                'total' => $this->order->formatted_total,
                'shipping_address' => $this->order->shipping_address,
            ]);
    }
}
```

## Inline Template Sending

For quick template sending without creating a Mailable class, use the `Mail::lettr()` method:

> **Note:** When no subject is provided, the template's own subject is used. Pass a `subject` only if you want to override it.

```php
use Illuminate\Support\Facades\Mail;

// Simple usage — subject comes from the template
Mail::lettr()
    ->to('user@example.com')
    ->sendTemplate('welcome-email', substitutionData: ['name' => 'John']);

// Override the template's subject
Mail::lettr()
    ->to('user@example.com')
    ->sendTemplate('welcome-email', subject: 'Hey John!', substitutionData: ['name' => 'John']);

// With specific template version
Mail::lettr()
    ->to('user@example.com')
    ->sendTemplate('order-confirmation', substitutionData: [
        'order_id' => 123,
        'items' => $items,
    ], version: 2);

// With a custom from address
Mail::lettr()
    ->from('hello@marketing.example.com', 'Marketing Team')
    ->to('user@example.com')
    ->sendTemplate('promo-campaign', substitutionData: $promoData);

// With CC and BCC
Mail::lettr()
    ->to('user@example.com')
    ->cc('manager@example.com')
    ->bcc('records@example.com')
    ->sendTemplate('invoice', substitutionData: $invoiceData);

// With a generated DTO (implements Arrayable)
Mail::lettr()
    ->to('user@example.com')
    ->sendTemplate('welcome-email', substitutionData: new WelcomeEmailData(
        userName: 'John',
        activationUrl: 'https://example.com/activate/abc123',
    ));
```

### Custom From Address

By default, emails are sent from the address configured in `MAIL_FROM_ADDRESS`. To send from a different address (e.g. a marketing domain), use `from()`:

```php
// Inline template sending
Mail::lettr()
    ->from('hello@marketing.example.com', 'Marketing Team')
    ->to('user@example.com')
    ->sendTemplate('promo-campaign', substitutionData: $promoData);

// Regular Mailable sending
Mail::lettr()
    ->from('noreply@transactional.example.com')
    ->to('user@example.com')
    ->send(new OrderConfirmation($order));
```

For Mailable classes, you can also set the from address in the `envelope()` method:

```php
class MarketingEmail extends LettrMailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('hello@marketing.example.com', 'Marketing Team'),
            subject: 'Special Offer',
        );
    }
}
```

> **Note:** The from address must belong to a verified sending domain in your [Lettr account](https://app.lettr.com/domains/sending).

### Custom Headers

You can pass custom headers with your emails. These are forwarded directly to the Lettr API.

```php
// Inline template sending
Mail::lettr()
    ->to('user@example.com')
    ->sendTemplate('welcome-email', substitutionData: ['name' => 'John'], customHeaders: [
        'X-Campaign-Id' => 'welcome-2024',
        'X-Entity-Ref' => 'order-123',
    ]);
```

For Mailable classes, use the `customHeaders()` method:

```php
class WelcomeEmail extends LettrMailable
{
    public function build(): static
    {
        return $this
            ->template('welcome-email')
            ->customHeaders([
                'X-Campaign-Id' => 'welcome-2024',
                'X-Entity-Ref' => 'order-123',
            ]);
    }
}
```

### Scheduled Emails

Schedule a Lettr transmission for future delivery. The transport routes to the `POST /emails/scheduled` endpoint when a scheduled-at timestamp is present.

```php
use Illuminate\Support\Facades\Mail;

// Inline template
Mail::lettr()
    ->to('user@example.com')
    ->scheduleAt(now()->addHours(6))
    ->sendTemplate('welcome-email', substitutionData: ['name' => 'John']);

// Mailable class
Mail::lettr()
    ->scheduleAt(new DateTimeImmutable('2030-01-01T12:00:00+00:00'))
    ->send(new OrderConfirmation($order));

// Or set it inside a LettrMailable's build()
class DripDay3 extends LettrMailable
{
    public function build(): static
    {
        return $this
            ->template('drip-day-3')
            ->scheduledAt(now()->addDays(3));
    }
}
```

You can also manage scheduled transmissions directly through the SDK:

```php
$response = Lettr::emails()->schedule($emailBuilder);

// Look up a scheduled transmission
$detail = Lettr::emails()->getScheduled($response->requestId);

// Cancel before it sends
Lettr::emails()->cancelScheduled($response->requestId);
```

### Testing with Mail::fake()

The `Mail::lettr()` method works seamlessly with Laravel's `Mail::fake()` for testing:

```php
use Illuminate\Support\Facades\Mail;
use Lettr\Laravel\Mail\InlineLettrMailable;

public function test_welcome_email_is_sent(): void
{
    Mail::fake();

    // Trigger the code that sends the email
    Mail::lettr()
        ->to('user@example.com')
        ->sendTemplate('welcome-email', substitutionData: ['name' => 'John']);

    // Assert the email was sent
    Mail::assertSent(InlineLettrMailable::class, function ($mailable) {
        return $mailable->hasTo('user@example.com');
    });
}

public function test_order_confirmation_has_correct_recipients(): void
{
    Mail::fake();

    Mail::lettr()
        ->to('customer@example.com')
        ->cc('sales@example.com')
        ->bcc('records@example.com')
        ->sendTemplate('order-confirmation', substitutionData: ['order_id' => 123]);

    Mail::assertSent(InlineLettrMailable::class, function ($mailable) {
        return $mailable->hasTo('customer@example.com')
            && $mailable->hasCc('sales@example.com')
            && $mailable->hasBcc('records@example.com');
    });
}
```

## Direct API Usage

### Sending Emails

#### Using the Email Builder (Recommended)

```php
use Lettr\Laravel\Facades\Lettr;

$response = Lettr::emails()->send(
    Lettr::emails()->create()
        ->from('sender@example.com', 'Sender Name')
        ->to(['recipient@example.com'])
        ->cc(['cc@example.com'])
        ->bcc(['bcc@example.com'])
        ->replyTo('reply@example.com')
        ->subject('Welcome!')
        ->html('<h1>Welcome</h1>')
        ->text('Welcome (plain text fallback)')
        ->transactional()
        ->withClickTracking(true)
        ->withOpenTracking(true)
        ->metadata(['user_id' => '123', 'campaign' => 'welcome'])
        ->substitutionData(['name' => 'John', 'company' => 'Acme'])
        ->tag('welcome')
);
```

#### Quick Send Methods

```php
// HTML email
$response = Lettr::emails()->sendHtml(
    from: 'sender@example.com',
    to: 'recipient@example.com',
    subject: 'Hello',
    html: '<p>HTML content</p>',
);

// Plain text email
$response = Lettr::emails()->sendText(
    from: ['email' => 'sender@example.com', 'name' => 'Sender'],
    to: ['recipient1@example.com', 'recipient2@example.com'],
    subject: 'Hello',
    text: 'Plain text content',
);

// Template email (subject is optional — the template defines its own subject)
$response = Lettr::emails()->sendTemplate(
    from: 'sender@example.com',
    to: 'recipient@example.com',
    subject: null,
    templateSlug: 'welcome-email',
    templateVersion: 2,
    substitutionData: ['name' => 'John'],
);
```

### Attachments

```php
use Lettr\Dto\Email\Attachment;

$email = Lettr::emails()->create()
    ->from('sender@example.com')
    ->to(['recipient@example.com'])
    ->subject('Document attached')
    ->html('<p>Please find the document attached.</p>')
    // From file path
    ->attachFile('/path/to/document.pdf')
    // With custom name and mime type
    ->attachFile('/path/to/file', 'custom-name.pdf', 'application/pdf')
    // From binary data
    ->attachData($binaryContent, 'report.csv', 'text/csv')
    // Using Attachment DTO
    ->attach(Attachment::fromFile('/path/to/image.png'));

$response = Lettr::emails()->send($email);
```

### Templates with Substitution Data

When using a template, the subject is optional — the template defines its own subject. You can omit `->subject()` entirely, or provide one to override the template's subject.

```php
$response = Lettr::emails()->send(
    Lettr::emails()->create()
        ->from('sender@example.com')
        ->to(['recipient@example.com'])
        ->useTemplate('order-confirmation', version: 1)
        ->substitutionData([
            'order_id' => '12345',
            'customer_name' => 'John Doe',
            'items' => [
                ['name' => 'Product A', 'price' => 29.99],
                ['name' => 'Product B', 'price' => 49.99],
            ],
            'total' => 79.98,
        ])
);
```

### Email Options

```php
$email = Lettr::emails()->create()
    ->from('sender@example.com')
    ->to(['recipient@example.com'])
    ->subject('Newsletter')
    ->html($htmlContent)
    // Tracking
    ->withClickTracking(true)
    ->withOpenTracking(true)
    // Mark as non-transactional (marketing email, respects unsubscribe lists)
    ->transactional(false)
    // CSS inlining
    ->withInlineCss(true)
    // Template variable substitution
    ->withSubstitutions(true);
```

### Retrieving Emails

#### Get Email Events by Request ID

```php
use Lettr\Enums\EventType;

// After sending
$response = Lettr::emails()->send($email);
$requestId = $response->requestId;

// Later, retrieve events
$result = Lettr::emails()->get($requestId);

foreach ($result->events as $event) {
    echo $event->type->value;      // 'delivery', 'open', 'click', etc.
    echo $event->recipient;        // Recipient email
    echo $event->timestamp;        // When the event occurred

    // Event-specific data
    if ($event->type === EventType::Click) {
        echo $event->clickUrl;
    }
    if ($event->type === EventType::Bounce) {
        echo $event->bounceClass;
        echo $event->reason;
    }
}
```

#### List Email Events with Filtering

```php
use Lettr\Dto\Email\ListEmailsFilter;

// List all events
$result = Lettr::emails()->list();

// With filters
$filter = ListEmailsFilter::create()
    ->perPage(50)
    ->forRecipient('user@example.com')
    ->fromDate('2024-01-01')
    ->toDate('2024-12-31');

$result = Lettr::emails()->list($filter);

echo $result->totalCount;
echo $result->pagination->hasNextPage();

// Paginate through results
while ($result->hasMore()) {
    foreach ($result->events as $event) {
        // Process event
    }

    $filter = $filter->cursor($result->pagination->nextCursor);
    $result = Lettr::emails()->list($filter);
}
```

## Domain Management

### List Domains

```php
$domains = Lettr::domains()->list();

foreach ($domains as $domain) {
    echo $domain->domain;           // example.com
    echo $domain->status->value;    // 'pending', 'approved'
    echo $domain->canSend;          // true/false
}
```

### Add a Domain

```php
use Lettr\ValueObjects\DomainName;

$result = Lettr::domains()->create('example.com');

echo $result->domain;
echo $result->status;

// DNS records to configure
echo $result->dns->returnPathHost;
echo $result->dns->returnPathValue;

if ($result->dns->dkim !== null) {
    echo $result->dns->dkim->selector;
    echo $result->dns->dkim->publicKey;
}
```

### Verify Domain DNS

```php
$verification = Lettr::domains()->verify('example.com');

if ($verification->isFullyVerified()) {
    echo "Domain is ready to send!";
} else {
    if (!$verification->dkim->isValid()) {
        echo "DKIM error: " . $verification->dkim->error;
    }
    if (!$verification->returnPath->isValid()) {
        echo "Return path error: " . $verification->returnPath->error;
    }
}
```

### Get Domain Details

```php
$domain = Lettr::domains()->get('example.com');

echo $domain->domain;
echo $domain->status;
echo $domain->trackingDomain;
echo $domain->createdAt;
```

### Delete a Domain

```php
Lettr::domains()->delete('example.com');
```

## Webhooks

### List Webhooks

```php
$webhooks = Lettr::webhooks()->list();

foreach ($webhooks as $webhook) {
    echo $webhook->id;
    echo $webhook->name;
    echo $webhook->url;
    echo $webhook->enabled;
    echo $webhook->authType->value;  // 'none', 'basic', 'oauth2'

    // $webhook->eventTypes is null when the webhook subscribes to all events.
    if ($webhook->listensToAllEvents()) {
        echo "Subscribed to every event type";
    } else {
        foreach ($webhook->eventTypes as $eventType) {
            echo $eventType->value;  // e.g. 'message.delivery', 'engagement.click'
        }
    }

    if ($webhook->isFailing()) {
        echo "Last error: " . $webhook->lastError;
    }
}
```

### Get Webhook Details

```php
use Lettr\Enums\WebhookEventType;

$webhook = Lettr::webhooks()->get('webhook-id');

echo $webhook->name;
echo $webhook->url;
echo $webhook->lastTriggeredAt;

if ($webhook->listensTo(WebhookEventType::Bounce)) {
    echo "Webhook receives bounce notifications";
}
```

### Create, Update, Delete Webhooks

```php
use Lettr\Dto\Webhook\CreateWebhookData;
use Lettr\Dto\Webhook\UpdateWebhookData;
use Lettr\Enums\WebhookEventType;

// Create
$created = Lettr::webhooks()->create(new CreateWebhookData(
    name: 'Engagement tracker',
    url: 'https://example.com/hooks/lettr',
    eventTypes: [WebhookEventType::Delivery, WebhookEventType::Click],
));

// Update
Lettr::webhooks()->update($created->id, new UpdateWebhookData(
    enabled: false,
));

// Delete
Lettr::webhooks()->delete($created->id);
```

## Audience Management

The audience API lets you manage **contacts**, **lists**, **segments**, **topics**, and
custom **properties**. Everything is reached through a single entry point,
`Lettr::audience()`, which mirrors the underlying SDK:

```php
Lettr::audience()->contacts();    // Lettr\Services\Audience\AudienceContactService
Lettr::audience()->lists();       // Lettr\Services\Audience\AudienceListService
Lettr::audience()->segments();    // Lettr\Services\Audience\AudienceSegmentService
Lettr::audience()->topics();      // Lettr\Services\Audience\AudienceTopicService
Lettr::audience()->properties();  // Lettr\Services\Audience\AudiencePropertyService
```

All list endpoints are paginated and return a response object exposing the data collection,
a `pagination` object, and a `hasMore()` helper.

### Contacts

```php
use Lettr\Dto\Audience\CreateAudienceContactData;
use Lettr\Dto\Audience\UpdateAudienceContactData;
use Lettr\Dto\Audience\ListAudienceContactsFilter;
use Lettr\Enums\AudienceContactStatus;

// Create a contact (optionally attach to a list and set custom properties)
$contact = Lettr::audience()->contacts()->create(new CreateAudienceContactData(
    email: 'jane@example.com',
    listId: 'list-uuid',
    properties: ['first_name' => 'Jane', 'plan' => 'pro'],
));

$contact->id;     // "contact-uuid"
$contact->email;  // "jane@example.com"
$contact->status; // AudienceContactStatus::Subscribed

// List with filtering and pagination
$response = Lettr::audience()->contacts()->list(
    ListAudienceContactsFilter::create()
        ->page(1)
        ->perPage(50)
        ->search('jane')
        ->status(AudienceContactStatus::Subscribed)
        ->listId('list-uuid')
);

foreach ($response->contacts as $contact) {
    // ...
}
$response->pagination->total;
$response->hasMore();

// Get, update, delete
$contact = Lettr::audience()->contacts()->get('contact-uuid');

Lettr::audience()->contacts()->update('contact-uuid', new UpdateAudienceContactData(
    status: AudienceContactStatus::Unsubscribed,
    properties: ['plan' => 'enterprise'],
));

Lettr::audience()->contacts()->delete('contact-uuid');
```

#### Double opt-in

Pass a `DoubleOptInConfig` to create the contact as `unverified` and trigger a confirmation
email — the contact becomes `subscribed` once they click the link.

```php
use Lettr\Dto\Audience\CreateAudienceContactData;
use Lettr\Dto\Audience\DoubleOptInConfig;

Lettr::audience()->contacts()->create(new CreateAudienceContactData(
    email: 'jane@example.com',
    doubleOptIn: new DoubleOptInConfig(
        from: 'hello@example.com',
        subject: 'Confirm your subscription',
        templateSlug: 'email-confirmation',
        redirectUrl: 'https://example.com/confirmed',
        fromName: 'Example',
    ),
));
```

#### Bulk operations & list/topic membership

```php
use Lettr\Dto\Audience\BulkCreateAudienceContactsData;
use Lettr\Dto\Audience\BulkAttachContactsToListsData;
use Lettr\Dto\Audience\BulkDetachContactsFromListsData;

// Bulk create (up to 1000 emails)
$result = Lettr::audience()->contacts()->bulkCreate(new BulkCreateAudienceContactsData(
    emails: ['a@example.com', 'b@example.com'],
    listId: 'list-uuid',
));
$result->created;        // int
$result->alreadyExisted; // int

// Attach / detach a single contact to a list (true if newly attached, false if already attached)
$attached = Lettr::audience()->contacts()->attachList('contact-uuid', 'list-uuid');
Lettr::audience()->contacts()->detachList('contact-uuid', 'list-uuid');

// Bulk attach / detach (up to 1000 contacts x 50 lists)
$result = Lettr::audience()->contacts()->bulkAttachLists(new BulkAttachContactsToListsData(
    contactIds: ['contact-1', 'contact-2'],
    listIds: ['list-1', 'list-2'],
));
$result->attached;        // int
$result->alreadyAttached; // int
$result->totalPairs;      // int

Lettr::audience()->contacts()->bulkDetachLists(new BulkDetachContactsFromListsData(
    contactIds: ['contact-1'],
    listIds: ['list-1'],
));

// Subscribe / unsubscribe a contact to a topic (true if newly subscribed)
Lettr::audience()->contacts()->subscribeTopic('contact-uuid', 'topic-uuid');
Lettr::audience()->contacts()->unsubscribeTopic('contact-uuid', 'topic-uuid');
```

### Lists

```php
use Lettr\Dto\Audience\CreateAudienceListData;
use Lettr\Dto\Audience\UpdateAudienceListData;
use Lettr\Dto\Audience\BulkDeleteAudienceListsData;
use Lettr\Dto\Audience\ListAudienceListsFilter;

$list = Lettr::audience()->lists()->create(new CreateAudienceListData(name: 'Newsletter'));
$list->id;
$list->name;
$list->contactsCount;

$response = Lettr::audience()->lists()->list(
    ListAudienceListsFilter::create()->page(1)->perPage(20)
);

$list = Lettr::audience()->lists()->get('list-uuid');

Lettr::audience()->lists()->update('list-uuid', new UpdateAudienceListData(name: 'Weekly digest'));

Lettr::audience()->lists()->delete('list-uuid');

// Bulk delete (up to 50)
$result = Lettr::audience()->lists()->bulkDelete(new BulkDeleteAudienceListsData(
    listIds: ['list-1', 'list-2'],
));
$result->deleted; // int
```

### Segments

Segment conditions are grouped: groups are joined by **OR**, and conditions within a group
are joined by **AND**. Build them with `SegmentConditionsInput` / `SegmentConditionGroup` /
`SegmentCondition` and the `SegmentOperator` enum.

```php
use Lettr\Dto\Audience\CreateAudienceSegmentData;
use Lettr\Dto\Audience\UpdateAudienceSegmentData;
use Lettr\Dto\Audience\ListAudienceSegmentsFilter;
use Lettr\Dto\Audience\SegmentConditionsInput;
use Lettr\Dto\Audience\SegmentConditionGroup;
use Lettr\Dto\Audience\SegmentCondition;
use Lettr\Enums\SegmentOperator;

$conditions = new SegmentConditionsInput(groups: [
    new SegmentConditionGroup(conditions: [
        new SegmentCondition('email', SegmentOperator::EndsWith, '@example.com'),
        new SegmentCondition('plan', SegmentOperator::Equals, 'pro'),
    ]),
]);

$segment = Lettr::audience()->segments()->create(new CreateAudienceSegmentData(
    name: 'Pro users at example.com',
    conditions: $conditions,
    listId: 'list-uuid', // optional: restrict to one list (null = all lists)
));

$response = Lettr::audience()->segments()->list(
    ListAudienceSegmentsFilter::create()->listId('list-uuid')
);

$segment = Lettr::audience()->segments()->get('segment-uuid');

// Update DTO uses a builder (only the touched fields are sent)
Lettr::audience()->segments()->update('segment-uuid', UpdateAudienceSegmentData::empty()
    ->withName('Renamed segment')
    ->withConditions($conditions));

Lettr::audience()->segments()->delete('segment-uuid');
```

Operators that don't take a value (`SegmentOperator::IsTrue`, `SegmentOperator::IsFalse`)
report this via `requiresValue()`.

### Topics

```php
use Lettr\Dto\Audience\CreateAudienceTopicData;
use Lettr\Dto\Audience\UpdateAudienceTopicData;
use Lettr\Dto\Audience\ListAudienceTopicsFilter;
use Lettr\Enums\AudienceTopicDefaultSubscription;
use Lettr\Enums\AudienceTopicVisibility;

$topic = Lettr::audience()->topics()->create(new CreateAudienceTopicData(
    name: 'Product updates',
    description: 'Occasional product news',
    defaultSubscription: AudienceTopicDefaultSubscription::OptIn, // immutable after creation
    visibility: AudienceTopicVisibility::PublicVisibility,
));

$response = Lettr::audience()->topics()->list(
    ListAudienceTopicsFilter::create()->perPage(20)
);

$topic = Lettr::audience()->topics()->get('topic-uuid');

// default_subscription cannot be changed after creation
Lettr::audience()->topics()->update('topic-uuid', new UpdateAudienceTopicData(
    name: 'Product news',
    visibility: AudienceTopicVisibility::PrivateVisibility,
));

Lettr::audience()->topics()->delete('topic-uuid');
```

### Properties

Custom contact properties have an immutable `name` (must match `^[a-z][a-z0-9_]*$`) and an
immutable `type`. Only the fallback value can be updated.

```php
use Lettr\Dto\Audience\CreateAudiencePropertyData;
use Lettr\Dto\Audience\UpdateAudiencePropertyData;
use Lettr\Dto\Audience\ListAudiencePropertiesFilter;
use Lettr\Enums\AudiencePropertyType;

$property = Lettr::audience()->properties()->create(new CreateAudiencePropertyData(
    name: 'plan',
    type: AudiencePropertyType::StringType,
    fallbackValue: 'free',
));

$response = Lettr::audience()->properties()->list(
    ListAudiencePropertiesFilter::create()->page(1)
);

$property = Lettr::audience()->properties()->get('property-uuid');

// Only the fallback can change — use the named constructors
Lettr::audience()->properties()->update('property-uuid', UpdateAudiencePropertyData::withFallback('basic'));
Lettr::audience()->properties()->update('property-uuid', UpdateAudiencePropertyData::clearFallback());

Lettr::audience()->properties()->delete('property-uuid');
```

## Campaigns

Campaigns are read-only plus lifecycle actions (send, schedule, unschedule) — there is no
create/update/delete via the API. Reach the service via `Lettr::campaigns()`. All endpoints
require an API key with the `campaigns:read` (reads) or `campaigns:write` (actions) scope.

- `list()` returns `Lettr\Dto\Campaign\CampaignSummary` items.
- `get()` returns a `Lettr\Dto\Campaign\CampaignDetail` (a subclass of `CampaignSummary`)
  with the rendered email body at `$campaign->htmlContent`.
- `send()`, `schedule()`, and `unschedule()` return a plain `CampaignSummary` —
  `htmlContent` is intentionally not exposed on the action results because the API
  doesn't include it in action responses. Call `get()` if you need the rendered body.

`$campaign->status` is typed `CampaignStatus|string` and `$event->eventType` is typed
`EventType|string` — unknown values from a server-side enum extension are preserved as raw
strings instead of throwing.

### List Campaigns

```php
use Lettr\Dto\Campaign\ListCampaignsFilter;
use Lettr\Enums\CampaignStatus;

$response = Lettr::campaigns()->list();

foreach ($response->campaigns as $campaign) {
    echo $campaign->name;               // "Spring Sale"
    echo $campaign->sentCount;          // 124
    echo $campaign->stats->uniqueOpens; // engagement stats are embedded

    if ($campaign->status === CampaignStatus::Sent) {
        echo 'Already delivered';
    }
}

echo $response->pagination->total;
echo $response->hasMore();

// Filter by status and page
$response = Lettr::campaigns()->list(
    ListCampaignsFilter::create()->status(CampaignStatus::Sent)->page(2)->perPage(50),
);
```

### Get a Campaign

```php
// Returns CampaignDetail (extends CampaignSummary, adds $htmlContent)
$campaign = Lettr::campaigns()->get('campaign-uuid');

echo $campaign->subject;
echo $campaign->htmlContent; // rendered HTML body — CampaignDetail only
echo $campaign->stats->clicks;
```

### List Campaign Events

Engagement events use cursor-based pagination. Reuse `Lettr\Enums\EventType`; the campaigns
endpoint only emits the seven engagement-subset values (`injection`, `delivery`, `bounce`,
`spam_complaint`, `open`, `click`, `list_unsubscribe`).

```php
use DateTimeImmutable;
use Lettr\Dto\Campaign\ListCampaignEventsFilter;
use Lettr\Enums\EventType;

$cursor = null;

do {
    $response = Lettr::campaigns()->events('campaign-uuid', ListCampaignEventsFilter::create()
        ->eventType(EventType::Click)
        ->startDate(new DateTimeImmutable('-7 days'))
        ->cursor($cursor));

    foreach ($response->events as $event) {
        echo $event->email;
        echo $event->timestamp;
        echo $event->targetLinkUrl; // for click events
    }

    $cursor = $response->nextCursor;
} while ($response->hasMore());
```

### Send / Schedule / Unschedule

```php
use DateTimeImmutable;
use DateTimeZone;

// Dispatch a draft campaign immediately (asynchronous; transitions to "preparing")
$campaign = Lettr::campaigns()->send('campaign-uuid');

// Schedule for future delivery — accepts a DateTimeInterface or an ISO-8601 string.
// Calling schedule() on an already-scheduled campaign reschedules it.
Lettr::campaigns()->schedule(
    'campaign-uuid',
    new DateTimeImmutable('2026-06-01 09:00:00', new DateTimeZone('+02:00')),
);

// Cancel a scheduled send, returning the campaign to draft
Lettr::campaigns()->unschedule('campaign-uuid');
```

The three action methods always return a non-null `CampaignSummary` — if the API omits the
campaign payload from the action response, the SDK transparently refetches it.

## Event Types

The SDK exposes two related enums:

- **`Lettr\Enums\EventType`** — used when filtering or inspecting events returned from `/emails/events` (e.g. `$event->type === EventType::Delivery`). Values are unprefixed: `delivery`, `click`, `bounce`, ...
- **`Lettr\Enums\WebhookEventType`** — used when creating or reading webhook subscriptions. Values are namespaced: `message.delivery`, `engagement.click`, `unsubscribe.list_unsubscribe`, `relay.relay_injection`, ...

```php
use Lettr\Enums\EventType;

$type = EventType::Delivery;

$type->label();        // "Delivery"
$type->isSuccess();    // true (injection, delivery)
$type->isFailure();    // false (bounce, policy_rejection, etc.)
$type->isEngagement(); // false (open, initial_open, click, amp_open, amp_initial_open, amp_click)
$type->isUnsubscribe(); // false (list_unsubscribe, link_unsubscribe)
```

Available `EventType` values: `injection`, `delivery`, `bounce`, `delay`, `policy_rejection`, `out_of_band`, `open`, `initial_open`, `click`, `amp_open`, `amp_initial_open`, `amp_click`, `generation_failure`, `generation_rejection`, `spam_complaint`, `list_unsubscribe`, `link_unsubscribe`.

## Error Handling

```php
use Lettr\Exceptions\ApiException;
use Lettr\Exceptions\TransporterException;
use Lettr\Exceptions\ValidationException;
use Lettr\Exceptions\NotFoundException;
use Lettr\Exceptions\UnauthorizedException;
use Lettr\Exceptions\RateLimitException;
use Lettr\Exceptions\QuotaExceededException;

try {
    $response = Lettr::emails()->send($email);
} catch (RateLimitException $e) {
    // Too many requests (429)
    Log::warning("Rate limited, retry after: " . $e->retryAfter . "s");
} catch (QuotaExceededException $e) {
    // Sending quota exceeded
    Log::error("Quota exceeded: " . $e->getMessage());
} catch (ValidationException $e) {
    // Invalid request data (422)
    Log::error("Validation failed: " . $e->getMessage());
} catch (UnauthorizedException $e) {
    // Invalid API key (401)
    Log::error("Authentication failed: " . $e->getMessage());
} catch (NotFoundException $e) {
    // Resource not found (404)
    Log::error("Not found: " . $e->getMessage());
} catch (ApiException $e) {
    // Other API errors
    Log::error("API error ({$e->getCode()}): " . $e->getMessage());
} catch (TransporterException $e) {
    // Network/transport errors
    Log::error("Network error: " . $e->getMessage());
}
```

## Configuration

The published `config/lettr.php` file contains:

```php
return [
    'api_key' => env('LETTR_API_KEY'),

    'templates' => [
        'html_path' => resource_path('templates/lettr'),
        'blade_path' => resource_path('views/emails/lettr'),
        'mailable_path' => app_path('Mail/Lettr'),
        'mailable_namespace' => 'App\\Mail\\Lettr',
        'dto_path' => app_path('Dto/Lettr'),
        'dto_namespace' => 'App\\Dto\\Lettr',
        'enum_path' => app_path('Enums'),
        'enum_namespace' => 'App\\Enums',
        'enum_class' => 'LettrTemplate',
    ],
];
```

The `templates` block configures where `lettr:pull`, `lettr:generate-dtos`, and `lettr:generate-enum` commands save generated files.

The package also supports `config('services.lettr.key')` as a fallback for the API key.

## CLI Commands

### `lettr:check`

Verify that your Lettr integration is correctly configured:

```bash
php artisan lettr:check
```

Checks mailer registration, API key validity, and sending domain verification. Returns exit code 0 if all checks pass.

### `lettr:pull`

Download email templates from your Lettr account as Blade files:

```bash
php artisan lettr:pull
php artisan lettr:pull --template=welcome-email
php artisan lettr:pull --as-html
php artisan lettr:pull --with-mailables
php artisan lettr:pull --dry-run
```

| Option | Description |
|--------|-------------|
| `--template=` | Pull only a specific template by slug |
| `--as-html` | Save as raw HTML instead of Blade |
| `--with-mailables` | Also generate Mailable and DTO classes |
| `--skip-templates` | Skip downloading templates, only generate DTOs and Mailables |
| `--dry-run` | Preview what would be downloaded |

### `lettr:generate-enum`

Generate a PHP enum from your Lettr template slugs for type-safe template references:

```bash
php artisan lettr:generate-enum
php artisan lettr:generate-enum --dry-run
```

Generates an enum like:

```php
enum LettrTemplate: string
{
    case WelcomeEmail = 'welcome-email';
    case OrderConfirmation = 'order-confirmation';
}
```

### `lettr:generate-dtos`

Generate type-safe DTO classes from template merge tags:

```bash
php artisan lettr:generate-dtos
php artisan lettr:generate-dtos --template=welcome-email
php artisan lettr:generate-dtos --dry-run
```

Generated DTOs implement `Arrayable` and can be passed directly to `sendTemplate()`:

```php
$data = new WelcomeEmailData(userName: 'John', activationUrl: '...');

Mail::lettr()->to('user@example.com')->sendTemplate('welcome-email', substitutionData: $data);
```

## Development

### Install Dependencies

```bash
composer install
```

### Code Style

```bash
composer lint
```

### Static Analysis

```bash
composer analyse
```

### Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

MIT License. See [LICENSE](LICENSE) for details.
