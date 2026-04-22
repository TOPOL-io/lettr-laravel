# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.4.0] - 2026-04-22

### Added

- **Scheduled Emails** - Schedule a Lettr transmission from Laravel Mail:
  - `Mail::lettr()->scheduleAt($datetime)->sendTemplate(...)` for inline templates
  - `$this->scheduledAt($datetime)` on `LettrMailable` subclasses
  - Transport detects `X-Lettr-Scheduled-At` and routes to `POST /emails/scheduled`
- **Template Wrapper Methods** - `TemplateServiceWrapper::update()` and `::getHtml()` passthroughs.
- **Facade PHPDoc** - Added `projects()` and `health()` accessors to the `Lettr` facade docblock.

### Changed

- **Upgraded `lettr/lettr-php` to `^1.4.0`.** The SDK syncs to API v1.4.0 and adds scheduled emails, email list/events, full webhook CRUD, template update/html, and auth check. All new endpoints are reachable today via `Lettr::emails()` / `Lettr::webhooks()` / `Lettr::templates()` / `Lettr::health()`.
- **`lettr:push`** no longer computes slugs client-side. The API generates the slug; the command displays whatever the server assigned.

### Removed

- **`TemplateServiceWrapper::slugExists()`** - The underlying create endpoint has always server-generated slugs, so the client-side existence check was a no-op. Removed from the public API of the wrapper.

### Notes for v1.4 consumers

- `Webhook::$eventTypes` can now be `null` when the webhook subscribes to all events (use `$webhook->listensToAllEvents()`).
- `Enums\WebhookEventType` (namespaced: `message.delivery`, `engagement.click`, ...) is distinct from `Enums\EventType` (used for filtering `/emails/events`).
- `Domain` / `DomainDetail` / `DomainVerification` response shapes changed upstream (see `lettr/lettr-php` 1.4 notes); the Laravel package does not expose these DTOs directly.

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
