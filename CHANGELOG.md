# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.5.0] - 2026-04-23

No breaking changes. Adopts `lettr/lettr-php` v1.4 features additively and keeps existing public API intact.

### Added

- **Scheduled Emails** - Schedule a Lettr transmission from Laravel Mail:
  - `Mail::lettr()->scheduleAt($datetime)->sendTemplate(...)` for inline templates
  - `Mail::lettr()->scheduleAt($datetime)->send($mailable)` for `LettrMailable` subclasses
  - `$this->scheduledAt($datetime)` on `LettrMailable` subclasses
  - Transport detects `X-Lettr-Scheduled-At` and routes to `POST /emails/scheduled`
- **Template Wrapper Methods** - `TemplateServiceWrapper::update()` and `::getHtml()` passthroughs for the new SDK endpoints.
- **Facade PHPDoc** - Added `projects()` and `health()` accessors to the `Lettr` facade docblock (already worked at runtime via `__get`).

### Changed

- **Upgraded `lettr/lettr-php` to `^1.4.0`.** The SDK syncs to API v1.4.0 and adds scheduled emails, email list/events, full webhook CRUD, template update/html, and auth check. All new endpoints are reachable today via `Lettr::emails()` / `Lettr::webhooks()` / `Lettr::templates()` / `Lettr::health()`.
- **`lettr:push`** now shows the server-assigned slug after creation instead of the client-derived slug. `--dry-run` still previews the client-derived slug because no API call is made. The `(slug conflict resolved)` yellow marker is no longer emitted — the server handles collisions.

### Deprecated

Everything below still works, but will be removed in 2.0.0 — the underlying API has always generated slugs server-side, so client-side slug handling is meaningless.

- **`TemplateServiceWrapper::slugExists()`** — probes `GET /templates/{slug}` and returns a bool. Read the final slug from the `CreatedTemplate` response instead.
- **`PushCommand::resolveSlug()`** — internal conflict-resolution loop. Kept for subclasses that may have overridden or called it; the value it returns is no longer passed to the API.
- **The `$slug` parameter on `PushCommand::createTemplate()`** — the method signature is preserved for subclasses, but the argument is ignored. `CreateTemplateData` no longer accepts a slug.

### Notes for consumers using the raw SDK via the facade

These are upstream `lettr/lettr-php` v1.4 changes — if you drive SDK services directly through `Lettr::emails()` / `Lettr::webhooks()` / etc., check your code:

- `Dto\Template\CreateTemplateData` no longer accepts a `slug` parameter (the API ignored it anyway; the DTO was cleaned up).
- `Dto\Webhook\Webhook::$eventTypes` can now be `null` when the webhook subscribes to all events — use `$webhook->listensToAllEvents()` before iterating.
- `Enums\WebhookEventType` (namespaced: `message.delivery`, `engagement.click`, ...) is used for webhook subscriptions. `Enums\EventType` (unprefixed: `delivery`, `click`, ...) remains the filter for `/emails/events`.
- `Dto\Domain\Domain`, `DomainDetail`, and `DomainVerification` response shapes changed (see `lettr/lettr-php` 1.4 notes). The Laravel package does not expose these DTOs directly.

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
