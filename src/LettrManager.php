<?php

declare(strict_types=1);

namespace Lettr\Laravel;

use Lettr\Laravel\Services\TemplateServiceWrapper;
use Lettr\Lettr;
use Lettr\Services\DomainService;
use Lettr\Services\EmailService;
use Lettr\Services\WebhookService;

/**
 * Manager class that wraps the Lettr SDK and provides Laravel-specific service wrappers.
 *
 * @property-read EmailService $emails
 * @property-read DomainService $domains
 * @property-read WebhookService $webhooks
 * @property-read TemplateServiceWrapper $templates
 */
class LettrManager
{
    private ?TemplateServiceWrapper $templateServiceWrapper = null;

    public function __construct(
        private readonly Lettr $lettr,
        private readonly ?int $defaultProjectId = null,
    ) {}

    /**
     * Get the email service.
     */
    public function emails(): EmailService
    {
        return $this->lettr->emails();
    }

    /**
     * Get the domain service.
     */
    public function domains(): DomainService
    {
        return $this->lettr->domains();
    }

    /**
     * Get the webhook service.
     */
    public function webhooks(): WebhookService
    {
        return $this->lettr->webhooks();
    }

    /**
     * Get the template service wrapper with default project ID support.
     */
    public function templates(): TemplateServiceWrapper
    {
        if ($this->templateServiceWrapper === null) {
            $this->templateServiceWrapper = new TemplateServiceWrapper(
                $this->lettr->templates(),
                $this->defaultProjectId,
            );
        }

        return $this->templateServiceWrapper;
    }

    /**
     * Get the underlying Lettr SDK instance.
     */
    public function sdk(): Lettr
    {
        return $this->lettr;
    }

    /**
     * Magic method to access services as properties.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'emails' => $this->emails(),
            'domains' => $this->domains(),
            'webhooks' => $this->webhooks(),
            'templates' => $this->templates(),
            default => throw new \InvalidArgumentException("Unknown service: {$name}"),
        };
    }
}
