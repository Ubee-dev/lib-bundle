# UbeeDev Lib Bundle

[![CI](https://github.com/Ubee-dev/lib-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/Ubee-dev/lib-bundle/actions/workflows/ci.yml)

A comprehensive Symfony bundle providing reusable services, traits, validators, Doctrine types, and testing tools for web applications. Built for Symfony 7.4 and PHP 8.4.

## Features

- **Media Management** -- Upload, delete, WebP conversion, PDF generation
- **Email System** -- Provider abstraction (Mailchimp, Gmail, Symfony Mailer) with RabbitMQ async
- **Database Backup** -- Dump/restore with pluggable drivers (MySQL, PostgreSQL) and S3 storage
- **Custom Doctrine Types** -- Money, Email, Name, Url, HtmlName, DateTime, Date
- **API Tools** -- Request sanitization, pagination, typed parameter expectations
- **Markdown Parser** -- Extended syntax with YouTube/Vimeo embeds, buttons, steps, timelines
- **Anti-Robot Protection** -- Honeypot and Cloudflare Turnstile verifiers
- **Slack Integration** -- Send notifications with text, JSON, file, or shell snippets
- **Twig Extensions** -- Asset versioning, video embeds, UTM tracking, phone formatting
- **Validation** -- File size/type, phone numbers, video URLs, money comparison
- **Traits** -- DateTime manipulation, money formatting, phone numbers, slugify, video URL parsing
- **Post-Deploy Automation** -- Auto-discover and execute post-deploy scripts
- **Testing Toolkit** -- Entity factory, database cleaner, email mocking, time freezing, 100+ Behat steps

## Requirements

- PHP ^8.4
- Symfony 7.4
- Extensions: `intl`, `mbstring`, `ctype`, `iconv`

## Installation

```bash
composer require ubee-dev/lib-bundle
```

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    UbeeDev\LibBundle\UbeeDevLibBundle::class => ['all' => true],
];
```

## Quick Start

### 1. Configure environment variables

Copy the required variables to your `.env`:

```env
DATABASE_URL=mysql://root:@127.0.0.1:3306/my_app
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
RABBITMQ_HOST=127.0.0.1
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
S3_KEY=your-key
S3_SECRET=your-secret
S3_BACKUP_BUCKET=my-backups
SLACK_TOKEN=xoxb-your-token
MAILER_DSN=null://null
MAILER_PASSWORD=your-mailchimp-key
SENTRY_DSN=
APP_TOKEN=your-api-token
MUTE_OPS_ALERTS=false
```

### 2. Import the bundle configuration

```yaml
# config/packages/ubee_dev_lib.yaml
ubee_dev_lib:
    s3_region: eu-west-3
    s3_version: "2006-03-01"
    export_dir: "%kernel.logs_dir%/../exports"
    tmp_backup_folder: /tmp/dump
```

### 3. Use the services

```php
use UbeeDev\LibBundle\Service\MediaManager;
use UbeeDev\LibBundle\Service\Mailer;

class MyController extends AbstractController
{
    public function upload(Request $request, MediaManager $mediaManager): Response
    {
        $file = $request->files->get('photo');
        $media = $mediaManager->upload($file, 'photos');

        return $this->json(['path' => $mediaManager->getWebPath($media)]);
    }

    public function notify(Mailer $mailer): Response
    {
        $mailer->sendMail(
            'noreply@example.com',
            ['user@example.com'],
            '<h1>Welcome!</h1>',
            'Welcome to our app'
        );

        return $this->json(['sent' => true]);
    }
}
```

## Documentation

| Topic | Description |
|-------|-------------|
| [Configuration](docs/configuration.md) | Environment variables, bundle parameters, package configs |
| [Services](docs/services.md) | MediaManager, Mailer, S3, Slack, Markdown, PDF, Backup |
| [Database](docs/database.md) | Custom Doctrine types, backup/restore, migrations |
| [Messaging](docs/messaging.md) | RabbitMQ producers/consumers, async email, Slack |
| [Traits](docs/traits.md) | DateTime, Money, PhoneNumber, String, Video, Process |
| [Validators](docs/validators.md) | File, PhoneNumber, Video URL, Money constraints |
| [Entities](docs/entities.md) | AbstractEntity, Media, Address, DateTime, Date |
| [Forms](docs/forms.md) | Button types, transformers, anti-robot protection |
| [Twig](docs/twig.md) | Filters, functions, template components |
| [Commands](docs/commands.md) | Database backup, post-deploy, fixtures, purge |
| [Testing](docs/testing.md) | Factory, Cleaner, email mocking, time freezing, Behat |
| [API Tools](docs/api.md) | Request sanitization, pagination, typed expectations |

## Contributing

This project uses automatic semantic versioning via commit messages:

| Commit subject | Version bump | Example |
|---------------|-------------|---------|
| `Fix something` | Patch | v2.0.15 -> v2.0.16 |
| `Add feature [minor]` | Minor | v2.0.15 -> v2.1.0 |
| `Breaking change [major]` | Major | v2.0.15 -> v3.0.0 |

CI runs PHPUnit tests on every push to `main`. If tests pass, a new tag is created and pushed automatically.

## License

MIT
