<?php

declare(strict_types=1);

namespace Lettr\Laravel\Mail;

use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\PendingMail;
use Illuminate\Mail\SentMessage;

/**
 * Extended PendingMail that supports Lettr template sending.
 */
class LettrPendingMail extends PendingMail
{
    /** @var array{address: string, name: ?string}|null */
    protected ?array $fromAddress = null;

    public function __construct(MailerContract $mailer)
    {
        parent::__construct($mailer);
    }

    /**
     * Set the sender for the email.
     */
    public function from(string $address, ?string $name = null): static
    {
        $this->fromAddress = compact('address', 'name');

        return $this;
    }

    /**
     * Send a new mailable message instance, applying from address if set.
     */
    public function send(MailableContract $mailable): ?SentMessage
    {
        if ($this->fromAddress !== null && $mailable instanceof Mailable) {
            $mailable->from($this->fromAddress['address'], $this->fromAddress['name']);
        }

        return parent::send($mailable);
    }

    /**
     * Send a Lettr template.
     *
     * @param  array<string, mixed>|Arrayable<string, mixed>  $substitutionData
     * @param  string|null  $tag  Override the default tag (template slug). If null, server uses template slug.
     * @param  string|null  $subject  Override the template's default subject line.
     * @param  array<string, string>  $customHeaders  Custom headers to send with the email.
     */
    public function sendTemplate(
        string $templateSlug,
        ?string $subject = null,
        array|Arrayable $substitutionData = [],
        ?int $version = null,
        ?string $tag = null,
        array $customHeaders = [],
    ): ?SentMessage {
        if ($substitutionData instanceof Arrayable) {
            $substitutionData = $substitutionData->toArray();
        }

        $mailable = new InlineLettrMailable(
            $templateSlug,
            $subject,
            $substitutionData,
            $version,
            $tag,
            $customHeaders,
        );

        return $this->send($mailable);
    }
}
