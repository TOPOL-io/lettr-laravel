<?php

declare(strict_types=1);

namespace Lettr\Laravel\Mail;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

abstract class LettrMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * The Lettr template slug (for API template mode).
     */
    protected ?string $templateSlug = null;

    /**
     * The Blade view for this email (for Blade view mode).
     */
    protected ?string $bladeView = null;

    /**
     * The Lettr template version.
     */
    protected ?int $templateVersion = null;

    /**
     * The Lettr project ID.
     */
    protected ?int $projectId = null;

    /**
     * The substitution data for the template.
     *
     * @var array<string, mixed>
     */
    protected array $substitutionData = [];

    /**
     * Custom headers to send with the email.
     *
     * @var array<string, string>
     */
    protected array $customHeaders = [];

    /**
     * ISO 8601 timestamp at which the email should be sent.
     */
    protected ?string $scheduledAt = null;

    /**
     * Whether the Lettr header callback has already been registered.
     */
    protected bool $lettrHeadersRegistered = false;

    /**
     * Set the template slug.
     */
    public function template(string $slug, ?int $version = null, ?int $projectId = null): static
    {
        $this->templateSlug = $slug;
        $this->templateVersion = $version;
        $this->projectId = $projectId;

        return $this;
    }

    /**
     * Set the template version.
     */
    public function templateVersion(int $version): static
    {
        $this->templateVersion = $version;

        return $this;
    }

    /**
     * Set the project ID.
     */
    public function projectId(int $projectId): static
    {
        $this->projectId = $projectId;

        return $this;
    }

    /**
     * Set substitution data for the template.
     *
     * @param  array<string, mixed>  $data
     */
    public function substitutionData(array $data): static
    {
        $this->substitutionData = array_merge($this->substitutionData, $data);

        return $this;
    }

    /**
     * Set custom headers for the email.
     *
     * @param  array<string, string>  $headers
     */
    public function customHeaders(array $headers): static
    {
        $this->customHeaders = array_merge($this->customHeaders, $headers);

        return $this;
    }

    /**
     * Schedule the email for future delivery via the Lettr API.
     */
    public function scheduledAt(DateTimeInterface|string $when): static
    {
        $this->scheduledAt = $when instanceof DateTimeInterface
            ? $when->format(DateTimeInterface::ATOM)
            : $when;

        return $this;
    }

    /**
     * Build the subject for the message.
     *
     * When using a Lettr template without an explicit subject, skip setting one
     * so the template's own subject is used instead of an auto-generated fallback.
     */
    protected function buildSubject($message): static
    {
        if ($this->templateSlug !== null && ! $this->subject) {
            return $this;
        }

        return parent::buildSubject($message);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $this->prepareLettrDelivery();

        // If Blade view is set, use view rendering
        if ($this->bladeView !== null) {
            return new Content(
                view: $this->bladeView,
                with: $this->buildViewData(),
            );
        }

        // API template mode - the body is set by prepareLettrDelivery()
        return new Content;
    }

    /**
     * Build the view data array for Blade rendering.
     *
     * @return array<string, mixed>
     */
    public function buildViewData(): array
    {
        return array_merge(parent::buildViewData(), $this->withMergeTags());
    }

    /**
     * Get the merge tags for the template.
     *
     * Override this method in subclasses to provide merge tag data.
     * This is automatically called before delivery and merged with substitutionData.
     *
     * @return array<string, mixed>
     */
    public function withMergeTags(): array
    {
        return [];
    }

    /**
     * Build the message.
     *
     * Kept for backwards compatibility: subclasses that call parent::build()
     * still work, but the Lettr setup no longer depends on it being reached.
     */
    public function build(): static
    {
        $this->prepareLettrDelivery();

        return $this;
    }

    /**
     * Prepare the mailable for delivery.
     *
     * Laravel calls this before every send and render, so Lettr's setup runs
     * even when a subclass overrides build() or content() without calling the
     * parent implementation.
     */
    protected function prepareMailableForDelivery(): void
    {
        parent::prepareMailableForDelivery();

        $this->prepareLettrDelivery();
    }

    /**
     * Apply the Lettr transport configuration to this mailable.
     *
     * Safe to call repeatedly: the header callback is registered once, and
     * everything it reads is resolved when the callback runs, so the call
     * order relative to template()/substitutionData() does not matter.
     */
    protected function prepareLettrDelivery(): void
    {
        // If using Blade view, skip Lettr-specific setup
        if ($this->bladeView !== null) {
            return;
        }

        // Use the lettr mailer/transport for API template mode
        $this->mailer('lettr');

        // Merge data from withMergeTags() with any manually set substitution data
        $mergeTags = $this->withMergeTags();
        if (! empty($mergeTags)) {
            $this->substitutionData = array_merge($mergeTags, $this->substitutionData);
        }

        // Laravel documents $html as "string" even though it stays null until a
        // body is set, so read it through get_object_vars() to keep the null case
        // visible to static analysis.
        $body = get_object_vars($this)['html'] ?? null;

        // Set placeholder HTML - the actual content comes from the Lettr template
        // This is required because Laravel's Mailer expects some content. A body
        // set by the mailable itself is left alone.
        if ($this->templateSlug !== null && $body === null) {
            $this->html('<p>This email uses Lettr template: '.$this->templateSlug.'</p>');
        }

        if ($this->lettrHeadersRegistered) {
            return;
        }

        $this->lettrHeadersRegistered = true;

        // Register callback to add Lettr headers
        $this->withSymfonyMessage(function ($message): void {
            if ($this->templateSlug !== null) {
                $message->getHeaders()->addTextHeader('X-Lettr-Template-Slug', $this->templateSlug);
            }

            if ($this->templateVersion !== null) {
                $message->getHeaders()->addTextHeader('X-Lettr-Template-Version', (string) $this->templateVersion);
            }

            if ($this->projectId !== null) {
                $message->getHeaders()->addTextHeader('X-Lettr-Project-Id', (string) $this->projectId);
            }

            if (count($this->substitutionData) > 0) {
                $message->getHeaders()->addTextHeader(
                    'X-Lettr-Substitution-Data',
                    base64_encode(json_encode($this->substitutionData, JSON_THROW_ON_ERROR))
                );
            }

            if ($this->scheduledAt !== null) {
                $message->getHeaders()->addTextHeader('X-Lettr-Scheduled-At', $this->scheduledAt);
            }

            // Add custom headers
            foreach ($this->customHeaders as $name => $value) {
                $message->getHeaders()->addTextHeader($name, $value);
            }

            // Add tag header:
            // - If tags set by user, join with _ and use that
            // - If using template slug and no tags, skip (server uses template slug as tag)
            // - If not using template slug, auto-generate from mailable class name
            if (count($this->tags) > 0) {
                $message->getHeaders()->addTextHeader('X-Lettr-Tag', implode('_', $this->tags));
            } elseif ($this->templateSlug === null) {
                $message->getHeaders()->addTextHeader('X-Lettr-Tag', $this->generateTag());
            }
        });
    }

    /**
     * Generate a tag from the mailable class name.
     * Converts "App\Mail\OrderConfirmation" to "order-confirmation".
     */
    protected function generateTag(): string
    {
        $className = class_basename(static::class);

        // Convert PascalCase to kebab-case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className) ?? $className);
    }
}
