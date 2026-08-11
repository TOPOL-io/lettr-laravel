<?php

use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;
use Lettr\Laravel\Mail\LettrMailable;
use Symfony\Component\Mime\Email;

/**
 * Mirrors the README example: build() is overridden and never calls parent::build().
 */
class DocStyleWelcomeEmail extends LettrMailable
{
    public function __construct(
        public string $userName = 'John',
        public string $activationUrl = 'https://example.com/activate/abc123',
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

/**
 * The pre-existing pattern: build() delegates to parent::build().
 */
class ParentCallingWelcomeEmail extends LettrMailable
{
    public function build(): static
    {
        $this->template('welcome-email', version: 2)
            ->substitutionData(['user_name' => 'John']);

        return parent::build();
    }
}

/**
 * Mirrors the generated stub: slug as a property, no build() override.
 */
class PropertyOnlyWelcomeEmail extends LettrMailable
{
    protected ?string $templateSlug = 'welcome-email';

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome!');
    }

    public function withMergeTags(): array
    {
        return ['user_name' => 'John'];
    }
}

/**
 * README "Custom Headers" example.
 */
class DocStyleCustomHeaderEmail extends LettrMailable
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

class BladeViewEmail extends LettrMailable
{
    protected ?string $bladeView = 'emails.welcome';
}

beforeEach(function () {
    config()->set('mail.default', 'array');
});

/**
 * @return array<int, Email>
 */
function sentEmails(): array
{
    return Mail::getSymfonyTransport()->messages()
        ->map(fn ($message) => $message->getOriginalMessage())
        ->all();
}

function headerValue(Email $email, string $name): ?string
{
    return $email->getHeaders()->get($name)?->getBodyAsString();
}

function headerCount(Email $email, string $name): int
{
    return count(iterator_to_array($email->getHeaders()->all($name)));
}

function substitutionDataOf(Email $email): array
{
    $header = headerValue($email, 'X-Lettr-Substitution-Data');

    return $header === null ? [] : json_decode(base64_decode($header), true);
}

it('delivers a mailable whose build() does not call parent::build()', function () {
    Mail::to('user@example.com')->send(new DocStyleWelcomeEmail);

    $emails = sentEmails();

    expect($emails)->toHaveCount(1);
    expect(headerValue($emails[0], 'X-Lettr-Template-Slug'))->toBe('welcome-email');
    expect(headerValue($emails[0], 'X-Lettr-Template-Version'))->toBe('2');
    expect(substitutionDataOf($emails[0]))->toBe([
        'user_name' => 'John',
        'activation_url' => 'https://example.com/activate/abc123',
    ]);
});

it('delivers a mailable whose build() calls parent::build()', function () {
    Mail::to('user@example.com')->send(new ParentCallingWelcomeEmail);

    $emails = sentEmails();

    expect($emails)->toHaveCount(1);
    expect(headerValue($emails[0], 'X-Lettr-Template-Slug'))->toBe('welcome-email');
    expect(substitutionDataOf($emails[0]))->toBe(['user_name' => 'John']);
});

it('delivers a mailable that only declares the template slug as a property', function () {
    Mail::to('user@example.com')->send(new PropertyOnlyWelcomeEmail);

    $emails = sentEmails();

    expect($emails)->toHaveCount(1);
    expect(headerValue($emails[0], 'X-Lettr-Template-Slug'))->toBe('welcome-email');
    expect($emails[0]->getSubject())->toBe('Welcome!');
    expect(substitutionDataOf($emails[0]))->toBe(['user_name' => 'John']);
});

it('sends the documented custom headers', function () {
    Mail::to('user@example.com')->send(new DocStyleCustomHeaderEmail);

    $emails = sentEmails();

    expect(headerValue($emails[0], 'X-Campaign-Id'))->toBe('welcome-2024');
    expect(headerValue($emails[0], 'X-Entity-Ref'))->toBe('order-123');
});

it('adds each Lettr header exactly once when build() is also called by hand', function () {
    $mailable = new DocStyleWelcomeEmail;
    $mailable->build();
    $mailable->build();

    Mail::to('user@example.com')->send($mailable);

    $email = sentEmails()[0];

    expect(headerCount($email, 'X-Lettr-Template-Slug'))->toBe(1);
    expect(headerCount($email, 'X-Lettr-Substitution-Data'))->toBe(1);
});

it('sets a placeholder body so the mailer always has a view to render', function () {
    Mail::to('user@example.com')->send(new DocStyleWelcomeEmail);

    expect(sentEmails()[0]->getHtmlBody())->toContain('welcome-email');
});

it('keeps an explicitly set body instead of the placeholder', function () {
    $mailable = new DocStyleWelcomeEmail;
    $mailable->build()->html('<p>Custom body</p>');

    Mail::to('user@example.com')->send($mailable);

    $email = sentEmails()[0];

    expect($email->getHtmlBody())->toBe('<p>Custom body</p>');
    expect(headerValue($email, 'X-Lettr-Template-Slug'))->toBe('welcome-email');
});

it('leaves Blade view mailables untouched', function () {
    $mailable = new BladeViewEmail;
    $mailable->build();

    expect($mailable->content()->view)->toBe('emails.welcome');
    expect($mailable->mailer)->toBeNull();
    expect($mailable->callbacks)->toBeEmpty();
});
