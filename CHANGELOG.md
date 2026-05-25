# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [2.1.0] - 2026-05-25

### Added

- **Audience API** - Manage contacts, lists, segments, topics, and custom properties through a single `Lettr::audience()` entry point:
  - `Lettr::audience()->contacts()` / `->lists()` / `->segments()` / `->topics()` / `->properties()` expose the five audience sub-services (also reachable as magic properties, e.g. `Lettr::audience()->contacts`)
  - Contacts support creation with list attachment, custom properties, and double opt-in, plus bulk operations (bulk create, attach/detach lists, subscribe/unsubscribe topics)
  - Lists, segments, topics, and properties expose full CRUD with paginated, filterable `list()` endpoints
  - Added `audience()` to the `Lettr` facade and `LettrManager` docblocks
  - See the **Audience Management** section of the README for full usage examples

### Changed

- **Upgraded `lettr/lettr-php` to `^2.1.0`** for the audience API. The SDK's `TransporterContract` gained `patch()`, `deleteWithBody()`, and `lastStatusCode()` methods — only affects custom transporter implementations.

## [2.0.0] - 2026-04-23

Major version bump with breaking changes on two axes:

1. **Direct breaks in `lettr-laravel`'s public API** — the slug-handling surface on `TemplateServiceWrapper` and `PushCommand` has been deleted (see **Removed** below). Code that called `Lettr::templates()->slugExists()` or subclassed `PushCommand` and used its slug helpers will need updates.
2. **Transitive breaks from `lettr/lettr-php ^2.0.0`** — the SDK's DTOs, enums, and `TransporterContract` changed shape (see **Transitive SDK v2.0 changes** below).

If you only use `Mail::lettr()` / `LettrMailable` subclasses / the Laravel mail transport, and never touch `TemplateServiceWrapper::slugExists()` or subclass `PushCommand`, the upgrade is `composer update` and you're done. Otherwise audit the two sections below.

### Added

- **Scheduled Emails** - Schedule a Lettr transmission from Laravel Mail:
  - `Mail::lettr()->scheduleAt($datetime)->sendTemplate(...)` for inline templates
  - `Mail::lettr()->scheduleAt($datetime)->send($mailable)` for `LettrMailable` subclasses
  - `$this->scheduledAt($datetime)` on `LettrMailable` subclasses
  - Transport detects `X-Lettr-Scheduled-At` and routes to `POST /emails/scheduled`
- **Template Wrapper Methods** - `TemplateServiceWrapper::update()`, `::getHtml()`, and `::delete()` passthroughs for the new SDK endpoints.
- **Facade PHPDoc** - Added `projects()` and `health()` accessors to the `Lettr` facade docblock (already worked at runtime via `__get`).

### Changed

- **Upgraded `lettr/lettr-php` to `^2.0.0`.** The SDK syncs to API v1.4 (scheduled emails, email list/events, full webhook CRUD, template update/html, auth check) and was re-tagged as v2.0.0 to correctly reflect its breaking DTO/contract changes under SemVer. All new endpoints are reachable via `Lettr::emails()` / `Lettr::webhooks()` / `Lettr::templates()` / `Lettr::health()`.
- **`lettr:push`** now shows the server-assigned slug after creation. In `--dry-run` mode the summary no longer shows a client-guessed slug (the server assigns it at create time) — it prints `(slug assigned by server)` next to each template name. The `(slug conflict resolved)` yellow marker is no longer emitted — the server handles collisions.

### Removed

The underlying API has always generated slugs server-side, so client-side slug handling was dead code. Cashing in the removals now that this is a major bump:

- **`TemplateServiceWrapper::slugExists()`** — gone. Read the server-assigned slug from the `CreatedTemplate` response instead.
- **`PushCommand::resolveSlug()`** — gone. No replacement needed.
- **`PushCommand::filenameToSlug()`** — gone. Dry-run previews no longer show a client-derived slug.
- **The `$slug` parameter on `PushCommand::createTemplate()`** — signature changed from `createTemplate(string $name, string $slug, string $html)` to `createTemplate(string $name, string $html)`. Subclasses that overrode it must update their signature.
- **`PushCommand`'s `$createdTemplates` entries no longer carry a `conflict_resolved` key.**

### Transitive SDK v2.0 changes (upstream `lettr/lettr-php` breaking changes)

If you drive SDK services directly through the facade, audit these call sites:

- `Dto\Template\CreateTemplateData` no longer accepts a `slug` parameter. Drop the `slug:` arg; read the server-assigned slug from the `CreatedTemplate` response.
- `Dto\Webhook\Webhook::$eventTypes` is now `?WebhookEventTypeCollection` — `null` means the webhook subscribes to all events. Guard iteration with `$webhook->listensToAllEvents()`.
- `Enums\WebhookEventType` (namespaced: `message.delivery`, `engagement.click`, ...) replaces `Enums\EventType` in webhook subscription contexts. `Enums\EventType` (unprefixed: `delivery`, `click`, ...) remains the filter for `/emails/events`.
- `Dto\Domain\Domain` fields: removed `returnPathStatus`, `verifiedAt`; added `cnameStatus`, `statusLabel`, `updatedAt`.
- `Dto\Domain\DomainDetail` fields: removed `verifiedAt`; added `statusLabel`, `spfStatus`, `isPrimaryDomain`, `dnsProvider`.
- `Dto\Domain\DomainVerification::$ownershipVerified` retyped `?bool` → `?string`.
- `Contracts\TransporterContract` gained a required `put(string $uri, array $data): array` method. Only affects custom transporter implementations.

## [1.3.0] - 2026-03-19

### Added

- **Laravel 13 Support** - Added compatibility with Laravel 13, Symfony Mailer 8, and Orchestra Testbench 11
- **Custom Headers** - Support for custom email headers via `customHeaders()` on mailables and `customHeaders` parameter on `sendTemplate()`
- **CI Matrix** - Added Laravel 13 to GitHub Actions test matrix

## [1.2.0] - 2026-03-19

### Added

- **Custom From Address** - `from()` method on `LettrPendingMail` for sending emails from different addresses
  - `Mail::lettr()->from('hello@marketing.example.com', 'Marketing Team')->to()->sendTemplate()` fluent API
  - Works with both inline `sendTemplate()` and regular `send()` flows

### Fixed

- PHPStan: removed always-true `$depth > 0` comparisons in `BladeToSparkpostConverter` and `SparkpostToBladeConverter`

## [0.2.0] - 2025-01-23

### Added

- **Inline Template Sending** - Send Lettr templates without creating a Mailable class
  - `Mail::lettr()->to()->sendTemplate()` fluent API
  - Support for template version and project ID parameters
  - Full support for CC and BCC recipients
- **LettrPendingMail** - Extended PendingMail with `sendTemplate()` method
- **InlineLettrMailable** - Concrete mailable for inline template usage
- **Mail::fake() Support** - Full compatibility with Laravel's mail faking for tests
- **Documentation** - Added usage examples and testing documentation to README

### Changed

- LettrMailable now automatically uses the `lettr` mailer
- LettrMailable sets placeholder HTML content for Laravel compatibility
- Moved config publishing instructions to Installation section in README

## [0.1.0] - 2025-01-23

### Added

- Initial release of Lettr for Laravel
- **Laravel Mail Integration**
  - Seamless integration with Laravel's Mail system
  - Use Lettr as default mail driver or alongside other drivers
  - Full support for Mailable classes
- **LettrMailable Base Class**
  - Use Lettr templates instead of Blade views
  - Fluent API for setting template slug, version, and project ID
  - Substitution data support for template variables
- **Service Provider**
  - Auto-registration via Laravel package discovery
  - Publishes configuration via `vendor:publish`
  - Lazy-loaded Lettr client singleton
- **Lettr Facade**
  - Direct access to Email, Domain, and Webhook services
  - IDE-friendly with full PHPDoc type hints
- **Mail Transport**
  - Converts Laravel emails to Lettr API format
  - Supports HTML, plain text, and attachments
  - CC and BCC recipient support
  - Automatic Lettr template detection via headers
- **Configuration**
  - Simple API key configuration via `.env`
  - Fallback to `services.lettr.key` config
- **Laravel Support**
  - Laravel 10.x, 11.x, and 12.x compatibility
  - PHP 8.4+ required
