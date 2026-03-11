<?php

use Illuminate\Support\Facades\Mail;
use Lettr\Contracts\TransporterContract;
use Lettr\Laravel\Transport\LettrTransportFactory;
use Lettr\Lettr;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

it('can create lettr mail transport', function () {
    // Configure the lettr mailer in mail config
    config()->set('mail.mailers.lettr', [
        'transport' => 'lettr',
    ]);

    $transport = Mail::mailer('lettr')->getSymfonyTransport();

    expect($transport)->toBeInstanceOf(LettrTransportFactory::class);
});

it('does not pass subject to builder when subject is null and template slug is set', function () {
    $capturedData = null;

    $fakeTransporter = new class($capturedData) implements TransporterContract
    {
        public function __construct(public mixed &$captured) {}

        public function post(string $uri, array $data): array
        {
            $this->captured = $data;

            return ['request_id' => 'test-id', 'accepted' => 1, 'rejected' => 0];
        }

        public function get(string $uri): array
        {
            return [];
        }

        public function getWithQuery(string $uri, array $query = []): array
        {
            return [];
        }

        public function delete(string $uri): void {}

        public function lastResponseHeaders(): array
        {
            return [];
        }
    };

    $lettr = new Lettr($fakeTransporter);
    $transport = new LettrTransportFactory($lettr);

    $email = (new Email)
        ->from('sender@example.com')
        ->to('recipient@example.com')
        ->text('Fallback content');
    $email->getHeaders()->addTextHeader('X-Lettr-Template-Slug', 'welcome-email');
    // No subject set — $email->getSubject() returns null

    $envelope = new Envelope(
        new Address('sender@example.com'),
        [new Address('recipient@example.com')]
    );

    $sentMessage = new SentMessage($email, $envelope);

    $reflection = new ReflectionMethod($transport, 'doSend');
    $reflection->setAccessible(true);
    $reflection->invoke($transport, $sentMessage);

    expect($capturedData)->not->toHaveKey('subject')
        ->and($capturedData['template_slug'])->toBe('welcome-email');
});

it('passes subject to builder when subject is provided with template', function () {
    $capturedData = null;

    $fakeTransporter = new class($capturedData) implements TransporterContract
    {
        public function __construct(public mixed &$captured) {}

        public function post(string $uri, array $data): array
        {
            $this->captured = $data;

            return ['request_id' => 'test-id', 'accepted' => 1, 'rejected' => 0];
        }

        public function get(string $uri): array
        {
            return [];
        }

        public function getWithQuery(string $uri, array $query = []): array
        {
            return [];
        }

        public function delete(string $uri): void {}

        public function lastResponseHeaders(): array
        {
            return [];
        }
    };

    $lettr = new Lettr($fakeTransporter);
    $transport = new LettrTransportFactory($lettr);

    $email = (new Email)
        ->from('sender@example.com')
        ->to('recipient@example.com')
        ->subject('Override Subject')
        ->text('Fallback content');
    $email->getHeaders()->addTextHeader('X-Lettr-Template-Slug', 'welcome-email');

    $envelope = new Envelope(
        new Address('sender@example.com'),
        [new Address('recipient@example.com')]
    );

    $sentMessage = new SentMessage($email, $envelope);

    $reflection = new ReflectionMethod($transport, 'doSend');
    $reflection->setAccessible(true);
    $reflection->invoke($transport, $sentMessage);

    expect($capturedData['subject'])->toBe('Override Subject')
        ->and($capturedData['template_slug'])->toBe('welcome-email');
});
