# Configuration

This document covers all configuration options for the UbeeDev LibBundle, including environment variables, bundle parameters, and package-level settings.

The bundle relies on environment variables and parameters to connect to external services such as Redis, RabbitMQ, S3, Slack, and email providers. Getting these right is essential -- most bundle features will not work without proper configuration of the underlying services.

## Table of Contents

- [Environment Variables](#environment-variables)
  - [Database](#database)
  - [Redis](#redis)
  - [RabbitMQ](#rabbitmq)
  - [Object Storage](#object-storage)
  - [Notifications](#notifications)
  - [Email](#email)
  - [Error Tracking](#error-tracking)
  - [Authentication and Security](#authentication-and-security)
  - [Anti-Robot Protection](#anti-robot-protection)
  - [Application Settings](#application-settings)
  - [Testing](#testing)
- [Bundle Parameters](#bundle-parameters)
- [Package Configurations](#package-configurations)
  - [Doctrine Custom Types](#doctrine-custom-types)
  - [Redis](#redis-configuration)
  - [RabbitMQ](#rabbitmq-configuration)
  - [Sessions](#sessions)
  - [Sentry](#sentry)
  - [Monolog](#monolog)
  - [Twig](#twig)
- [Media Storage](#media-storage)
- [DatabaseDumper Configuration](#databasedumper-configuration)

---

## Environment Variables

Below is the complete list of environment variables used by the bundle. Set these in your `.env`, `.env.local`, or through your hosting environment.

### Database

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `DATABASE_URL` | Yes | -- | Doctrine DBAL connection string. |

```bash
# MySQL
DATABASE_URL="mysql://user:password@127.0.0.1:3306/my_database"

# PostgreSQL
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/my_database?serverVersion=15"
```

### Redis

Redis is used for session storage and caching throughout the application.

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `REDIS_HOST` | Yes | -- | Redis server hostname or IP address. |
| `REDIS_PASSWORD` | No | -- | Redis authentication password. Leave empty if no password is set. |

```bash
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=my_redis_secret
```

### RabbitMQ

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `RABBITMQ_HOST` | Yes | -- | RabbitMQ server hostname or IP address. |
| `RABBITMQ_USER` | Yes | -- | RabbitMQ username. |
| `RABBITMQ_PASSWORD` | Yes | -- | RabbitMQ password. |

```bash
RABBITMQ_HOST=127.0.0.1
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
```

### Object Storage

Object storage is used for database backups and file storage. The bundle supports any S3-compatible provider (AWS S3, OVH, MinIO, etc.).

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `OBJECT_STORAGE_KEY` | No | `'none'` | Access key ID for the storage provider. Required when using S3/OVH storage. |
| `OBJECT_STORAGE_SECRET` | No | `'none'` | Secret access key for the storage provider. Required when using S3/OVH storage. |
| `OBJECT_STORAGE_REGION` | No | `'us-east-1'` | Region of the storage provider (e.g. `eu-west-3`). |
| `BACKUP_BUCKET` | Yes | -- | Bucket name used for database backups. |
| `OBJECT_STORAGE_ENDPOINT` | No | `''` | Custom endpoint URL (required for OVH and other S3-compatible providers). |

**AWS S3:**

```bash
OBJECT_STORAGE_KEY=AKIAIOSFODNN7EXAMPLE
OBJECT_STORAGE_SECRET=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
OBJECT_STORAGE_REGION=eu-west-3
BACKUP_BUCKET=my-app-backups
```

**OVH:**

```bash
OBJECT_STORAGE_KEY=ovh-access-key
OBJECT_STORAGE_SECRET=ovh-secret-key
OBJECT_STORAGE_REGION=gra
OBJECT_STORAGE_ENDPOINT=https://s3.gra.io.cloud.ovh.net
BACKUP_BUCKET=my-app-backups
```

### Notifications

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `SLACK_TOKEN` | Yes | -- | Slack API token for sending notifications. |
| `ERROR_CHANNEL` | No | `''` | Slack channel where error notifications are sent. |
| `POST_DEPLOY_SLACK_CHANNEL` | No | `''` | Slack channel for post-deploy notifications. |
| `MUTE_OPS_ALERTS` | No | `false` | Set to `true` to mute all operations Slack alerts. |

```bash
SLACK_TOKEN=xoxb-1234567890-abcdefghijk
ERROR_CHANNEL=ops-errors
POST_DEPLOY_SLACK_CHANNEL=deployments
MUTE_OPS_ALERTS=false
```

### Email

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `MAILER_DSN` | Yes | -- | Symfony Mailer transport DSN. |
| `MAILER_PASSWORD` | No | -- | Mailchimp API key, used when the Mailchimp email provider is active. |
| `ERROR_EMAIL` | No | `''` | Email address for error notifications. |

```bash
MAILER_DSN=smtp://user:pass@smtp.example.com:587
MAILER_PASSWORD=my-mailchimp-api-key
ERROR_EMAIL=errors@example.com
```

### Error Tracking

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `SENTRY_DSN` | No | -- | Sentry DSN for error tracking. Only active in the `prod` environment. |

```bash
# Production only
SENTRY_DSN=https://examplePublicKey@o0.ingest.sentry.io/0
```

### Authentication and Security

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `APP_TOKEN` | No | `~` (null) | Application API token for authenticating API requests. |
| `APP_ENV` | Yes | -- | Current Symfony environment (`dev`, `test`, or `prod`). |

```bash
APP_TOKEN=my-secret-api-token
APP_ENV=prod
```

### Anti-Robot Protection

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `TURNSTILE_SECRET_KEY` | No | `''` | Cloudflare Turnstile server-side secret key. |
| `TURNSTILE_SITE_KEY` | No | `''` | Cloudflare Turnstile client-side site key. |
| `ANTI_ROBOT_DEFAULT_VERIFIER` | No | `'honeypot'` | Default anti-robot verifier strategy. Accepted values: `'turnstile'` or `'honeypot'`. |

```bash
# Use the built-in honeypot (no external service needed)
ANTI_ROBOT_DEFAULT_VERIFIER=honeypot

# Or use Cloudflare Turnstile
ANTI_ROBOT_DEFAULT_VERIFIER=turnstile
TURNSTILE_SECRET_KEY=0x4AAAAAABcdef...
TURNSTILE_SITE_KEY=0x4AAAAAAAbcdef...
```

### Application Settings

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `PAGE_SIZE` | No | `30` | Default number of items per page in paginated lists. |

```bash
PAGE_SIZE=50
```

### Testing

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `TEST_TOKEN` | No | `"1"` | Token used by ParaTest to create isolated parallel test databases. Each parallel process gets a unique token, resulting in database names suffixed with `_test1`, `_test2`, etc. |

```bash
# Usually set automatically by ParaTest; manual override is rarely needed
TEST_TOKEN=1
```

---

## Bundle Parameters

The bundle exposes its own configuration under the `ubee_dev_lib` key. Add these to `config/packages/ubee_dev_lib.yaml`:

```yaml
# config/packages/ubee_dev_lib.yaml
ubee_dev_lib:
    export_dir: "%kernel.logs_dir%/../exports"    # Directory for spreadsheet exports
    tmp_backup_folder: /tmp/dump                  # Temporary folder for database dumps
```

| Parameter | Default | Description |
|-----------|---------|-------------|
| `export_dir` | `%kernel.logs_dir%/../exports` | Local directory where generated spreadsheet exports are stored. |
| `tmp_backup_folder` | `/tmp/dump` | Local temporary directory used for database dump files before uploading to object storage. |

---

## Package Configurations

### Doctrine Custom Types

**File:** `config/packages/doctrine.yaml`

The bundle registers several custom Doctrine DBAL types that provide value-object mapping for common domain concepts:

```yaml
doctrine:
    dbal:
        types:
            datetime: UbeeDev\LibBundle\Doctrine\DBAL\Types\DateTimeType
            date:     UbeeDev\LibBundle\Doctrine\DBAL\Types\DateType
            money:    UbeeDev\LibBundle\Doctrine\DBAL\Types\MoneyType
            email:    UbeeDev\LibBundle\Doctrine\DBAL\Types\EmailType
            name:     UbeeDev\LibBundle\Doctrine\DBAL\Types\NameType
            url:      UbeeDev\LibBundle\Doctrine\DBAL\Types\UrlType
            htmlName: UbeeDev\LibBundle\Doctrine\DBAL\Types\HtmlNameType
```

In the **test** environment, a `dbname_suffix` is appended to the default connection's database name. This allows ParaTest to run parallel test processes against isolated databases:

```yaml
when@test:
    doctrine:
        dbal:
            connections:
                default:
                    # Produces database names like "my_database_test1", "my_database_test2", etc.
                    dbname_suffix: '_test%env(TEST_TOKEN)%'
```

### Redis Configuration

**File:** `config/packages/snc_redis.yaml`

A Predis client is configured with the prefix `ubee_dev`. It is the backend for session storage (see [Sessions](#sessions)):

```yaml
snc_redis:
    clients:
        default:
            type: predis
            dsn: "redis://%env(REDIS_PASSWORD)%@%env(REDIS_HOST)%:6379"
            options:
                prefix: ubee_dev
```

### RabbitMQ Configuration

**File:** `config/packages/old_sound_rabbit_mq.yaml`

The bundle defines five producer/consumer pairs for asynchronous processing. All connections use `lazy: true` so a RabbitMQ connection is not opened until the first message is published.

**Connection:**

```yaml
old_sound_rabbit_mq:
    connections:
        default:
            host:     '%env(RABBITMQ_HOST)%'
            port:     5672
            user:     '%env(RABBITMQ_USER)%'
            password: '%env(RABBITMQ_PASSWORD)%'
            vhost:    '/'
            lazy:     true
```

**Producers and consumers:**

| Producer | Exchange | Type | Consumer Callback |
|----------|----------|------|-------------------|
| `error` | `error-exchange` | `direct` | `ErrorConsumer` |
| `send_email` | `send_email` | `x-delayed-message` | `EmailConsumer` |
| `send_bulk_email` | `send_bulk_email` | `x-delayed-message` | `BulkEmailConsumer` |
| `send_slack_notification` | `send_slack_notification` | `x-delayed-message` | `SlackNotificationConsumer` |
| `compress_pdf` | `compress_pdf` | `direct` | `CompressPdfConsumer` |

The `x-delayed-message` exchange type requires the [RabbitMQ Delayed Message Plugin](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange). It allows scheduling messages with a delay before delivery.

To publish a message, inject the corresponding producer service:

```php
use UbeeDev\LibBundle\Producer\EmailProducer;

class MyService
{
    public function __construct(private EmailProducer $emailProducer) {}

    public function sendWelcome(): void
    {
        $this->emailProducer->publish(json_encode([
            'to' => 'user@example.com',
            'subject' => 'Welcome!',
        ]));
    }
}
```

### Sessions

**File:** `config/packages/framework.yaml`

Sessions are backed by Redis through the `RedisSessionHandler`. Cookies are secure by default (except in `dev` where `cookie_secure` is set to `false` for local development over HTTP):

```yaml
framework:
    session:
        cookie_lifetime: 0            # Session cookie expires when the browser closes
        gc_maxlifetime: 14000         # Garbage collection after 4 hours (in seconds)
        handler_id: Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler
        name: SESSID
        cookie_secure: true
        cookie_httponly: true
```

| Setting | Value | Description |
|---------|-------|-------------|
| `cookie_lifetime` | `0` | Cookie expires when the browser is closed. |
| `gc_maxlifetime` | `14000` | Session data is eligible for garbage collection after ~4 hours. |
| `name` | `SESSID` | The name of the session cookie. |
| `cookie_secure` | `true` (`false` in dev) | Cookie is only sent over HTTPS in production. |
| `cookie_httponly` | `true` | Cookie is not accessible from JavaScript. |

### Sentry

**File:** `config/packages/sentry.yaml`

Sentry error tracking is enabled **only in production**. The configuration excludes framework build/cache/vendor directories from stack traces and ignores `NotFoundHttpException` (404 errors):

```yaml
when@prod:
    sentry:
        dsn: "%env(SENTRY_DSN)%"
        register_error_listener: false
        register_error_handler: false
        options:
            in_app_exclude:
                - "%kernel.build_dir%"
                - "%kernel.cache_dir%"
                - "%kernel.project_dir%/vendor"
            ignore_exceptions:
                - Symfony\Component\HttpKernel\Exception\NotFoundHttpException
```

A Monolog handler is also registered in production to send `CRITICAL`-level log messages to Sentry:

```yaml
when@prod:
    monolog:
        handlers:
            sentry:
                type: service
                id: Sentry\Monolog\Handler
                level: !php/const Monolog\Logger::CRITICAL
```

### Monolog

**File:** `config/packages/monolog.yaml`

Logging is configured differently per environment:

**Development (`dev`):**
- `main`: Rotating file handler (14 days retention, `debug` level), excludes the `event` channel.
- `console`: Console handler for CLI output, excludes `event`, `doctrine`, and `console` channels.

```yaml
when@dev:
    monolog:
        handlers:
            main:
                type: rotating_file
                path: "%kernel.logs_dir%/%kernel.environment%.log"
                level: debug
                max_files: 14
                channels: ["!event"]
            console:
                type: console
                process_psr_3_messages: false
                channels: ["!event", "!doctrine", "!console"]
```

**Test (`test`):**
- `main`: Fingers-crossed handler that only flushes buffered messages when an `error`-level message occurs. Excludes HTTP 404 and 405 codes.
- `nested`: Delegates to a custom `MonologCISlackHandler` service at `critical` level (sends test failures to Slack in CI).

```yaml
when@test:
    monolog:
        handlers:
            main:
                type: fingers_crossed
                action_level: error
                handler: nested
                excluded_http_codes: [404, 405]
                channels: ["!event"]
            nested:
                type: service
                id: UbeeDev\LibBundle\Tests\Helper\MonologCISlackHandler
                level: critical
                channels: ["!event"]
```

**Production (`prod`):**
- `main`: Fingers-crossed handler (buffer size 50) that flushes on `error`. Excludes HTTP 404, 405, 401, and 403 codes.
- `nested`: Rotating file handler (7 days retention) with JSON formatting.
- `console`: Console handler for CLI, excluding `event` and `doctrine`.
- `deprecation`: Dedicated rotating file handler for the `deprecation` channel.
- `sentry`: Sends `CRITICAL`-level logs to Sentry (see [Sentry](#sentry)).

```yaml
when@prod:
    monolog:
        handlers:
            main:
                type: fingers_crossed
                action_level: error
                handler: nested
                excluded_http_codes: [404, 405, 401, 403]
                buffer_size: 50
            nested:
                type: rotating_file
                path: "%kernel.logs_dir%/%kernel.environment%.log"
                level: debug
                formatter: monolog.formatter.json
                max_files: 7
            console:
                type: console
                process_psr_3_messages: false
                channels: ["!event", "!doctrine"]
            deprecation:
                type: rotating_file
                channels: [deprecation]
                path: "%kernel.logs_dir%/%kernel.environment%.log"
                max_files: 7
```

### Twig

**File:** `config/packages/twig.yaml`

The bundle registers two custom form themes and exposes the `MediaManager` service as a global Twig variable:

```yaml
twig:
    form_themes:
        - '@UbeeDevLib/Form/Type/lib_button.html.twig'
        - '@UbeeDevLib/Form/Type/custom_html.html.twig'
    globals:
        mediaManager: '@UbeeDev\LibBundle\Service\MediaManager'
```

You can use `mediaManager` directly in any Twig template:

```twig
{{ mediaManager.getUrl(entity.image) }}
```

---

## Media Storage

The bundle provides a `MediaStorageInterface` that `MediaManager` uses to store, delete, and serve media files. By default, files are stored on the local filesystem (`LocalMediaStorage`). To use S3 or OVH, switch to `ObjectStorageMediaStorage`.

### Environment Variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `MEDIA_BUCKET` | No | `''` | Bucket name for media files (only needed with `ObjectStorageMediaStorage`). |
| `MEDIA_CDN_URL` | No | `''` | CDN base URL for public media files. Used by `MediaManager::getWebPath()` to prefix relative paths. If empty, relative paths are returned as-is (backward compatible). Example: `https://cdn.example.com`. |

### Bundle Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `media_presigned_url_expiry` | `3600` | Expiry time in seconds for presigned URLs (private media on S3/OVH). |

### Local Storage (default)

No configuration needed. Files are stored in `{projectDir}/public/` and `{projectDir}/private/`.

### S3/OVH Storage

Override the alias and set environment variables in your project:

```yaml
# config/services.yaml
services:
  UbeeDev\LibBundle\Service\MediaStorage\MediaStorageInterface:
    alias: UbeeDev\LibBundle\Service\MediaStorage\ObjectStorageMediaStorage
```

For OVH, also override the `ObjectStorageInterface`:

```yaml
  UbeeDev\LibBundle\Service\ObjectStorageInterface:
    alias: UbeeDev\LibBundle\Service\ObjectStorage\OvhObjectStorage
```

```bash
# .env.local
MEDIA_BUCKET=my-media-bucket
MEDIA_CDN_URL=                   # leave empty if no CDN
OBJECT_STORAGE_KEY=my-key
OBJECT_STORAGE_SECRET=my-secret
OBJECT_STORAGE_REGION=gra
OBJECT_STORAGE_ENDPOINT=https://s3.gra.io.cloud.ovh.net   # OVH only
```

---

## Image Resize

The bundle provides an `ImageResizeService` that resizes images on the fly and an `ImageResizeController` that exposes a `/media/{width}/{path}` route. Works with both local storage and S3. A CDN can be placed in front to cache resized images.

### How it works

```
Mobile → CDN → Backend (/media/375/event/202602/abc123.webp)
                  ↓
            Check resized exists (disk or S3)
                  ↓ yes → serve
                  ↓ no  → download original → resize → store resized → serve
                  ↓
            CDN caches the response
```

### Configuration

```yaml
# config/services.yaml
UbeeDev\LibBundle\Service\ImageResizeService:
  arguments:
    $mediaBucket: '%env(MEDIA_BUCKET)%'     # S3 bucket (same as media storage)
    $publicDir: '%kernel.project_dir%/public'
    $outputFormat: 'webp'                    # webp, jpg, png
    $widthBuckets: []                        # Override default width buckets (empty = defaults)
```

**Width buckets:** Default: `[320, 375, 414, 430, 600, 860, 1290]`. Pass a non-empty `$widthBuckets` array to replace the defaults entirely (e.g. `[64, 160, 320, 375, 414, 430, 600, 860, 1290]` to add thumbnail sizes).

Import the bundle routes. The default prefix is `/media`, configurable at import:

```yaml
# config/routes.yaml — default prefix (/media)
ubee_dev_lib:
  resource: '@UbeeDevLibBundle/config/routing.yml'

# config/routes.yaml — custom prefix
ubee_dev_lib_media:
  resource: UbeeDev\LibBundle\Controller\ImageResizeController
  type: attribute
  prefix: /cdn-images
```

### Local mode

No extra config needed. Original files are read from `{publicDir}/uploads/`, resized versions are cached in `{publicDir}/media/{width}/`.

### Remote mode (S3 + CDN)

Requires `ObjectStorageMediaStorage` to be configured (see [Media Storage](#media-storage)). Resized versions are stored on S3 under `public/media/{width}/`. S3 lifecycle policies on the `media/` prefix handle expiration.

---

## ObjectStorage Configuration

The bundle provides an `ObjectStorageInterface` used by the backup commands to upload, download, and list files in a remote bucket. The default implementation is **S3ObjectStorage** (AWS S3).

To switch to **OVH** (or any S3-compatible provider), override the interface alias in your application's service configuration:

```yaml
# config/services.yaml
services:
    UbeeDev\LibBundle\Service\ObjectStorageInterface:
        alias: UbeeDev\LibBundle\Service\ObjectStorage\OvhObjectStorage
```

Make sure to set the `OBJECT_STORAGE_ENDPOINT` environment variable when using OVH.

---

## DatabaseDumper Configuration

The bundle provides a `DatabaseDumperInterface` used by the backup system to dump databases before uploading to object storage. The default implementation is **MysqlDumper**.

To switch to **PostgreSQL**, override the interface alias in your application's service configuration:

```yaml
# config/services.yaml
services:
    UbeeDev\LibBundle\Service\DatabaseDumperInterface:
        alias: UbeeDev\LibBundle\Service\DatabaseDumper\PostgresDumper
```

The dumper uses the `tmp_backup_folder` bundle parameter (default: `/tmp/dump`) as the temporary directory for dump files before uploading to object storage. Make sure this directory is writable by the application.
