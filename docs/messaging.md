# Messaging

Asynchronous messaging layer built on [OldSoundRabbitMqBundle](https://github.com/php-amqplib/RabbitMqBundle). Handles transactional emails, bulk emails, error reporting, Slack notifications, and PDF compression through RabbitMQ producers and consumers.

Async messaging offloads slow operations -- email sending, PDF compression, Slack API calls -- from the HTTP request cycle, so controllers return immediately and response times stay fast. Failed jobs are retried automatically with escalating delays, without affecting the user experience.

## Table of Contents

- [RabbitMQ Setup](#rabbitmq-setup)
- [Producers](#producers)
  - [EmailProducer](#emailproducer)
  - [BulkEmailProducer](#bulkemailproducer)
  - [ErrorProducer](#errorproducer)
  - [SlackNotificationProducer](#slacknotificationproducer)
  - [CompressPdfProducer](#compresspdfproducer)
- [Consumers](#consumers)
  - [EmailConsumer](#emailconsumer)
  - [BulkEmailConsumer](#bulkemailconsumer)
  - [ErrorConsumer](#errorconsumer)
  - [SlackNotificationConsumer](#slacknotificationconsumer)
  - [CompressPdfConsumer](#compresspdfconsumer)
- [Retry Mechanism](#retry-mechanism)
- [SlackManager](#slackmanager)
- [Slack Snippets](#slack-snippets)

---

## RabbitMQ Setup

The bundle configures RabbitMQ through `config/packages/old_sound_rabbit_mq.yaml`. Connections use **lazy mode** so no connection is opened until the first message is published.

Exchanges that need delayed delivery use the `x-delayed-message` type (requires the [rabbitmq-delayed-message-exchange](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange) plugin). The `error` and `compress_pdf` exchanges use the standard `direct` type since they do not need delayed retry.

### Environment variables

| Variable            | Description                |
|---------------------|----------------------------|
| `RABBITMQ_HOST`     | RabbitMQ server hostname   |
| `RABBITMQ_USER`     | Authentication username    |
| `RABBITMQ_PASSWORD` | Authentication password    |

### Queues overview

| Producer / Consumer       | Exchange                  | Exchange Type       | Queue                     |
|---------------------------|---------------------------|---------------------|---------------------------|
| `send_email`              | `send_email`              | `x-delayed-message` | `send_email`              |
| `send_bulk_email`         | `send_bulk_email`         | `x-delayed-message` | `send_bulk_email`         |
| `send_slack_notification` | `send_slack_notification` | `x-delayed-message` | `send_slack_notification` |
| `error`                   | `error-exchange`          | `direct`            | `error-queue`             |
| `compress_pdf`            | `compress_pdf`            | `direct`            | `compress_pdf`            |

### Test environment

In the `test` environment, `AbstractProducer` replaces the real RabbitMQ producer with a `RabbitMQStub` so no actual messages are sent.

---

## Producers

All producers extend `AbstractProducer`, which wraps the OldSound `Producer` and provides the `getXDelayForRetryNumber()` helper for delayed retries.

### EmailProducer

Sends transactional emails asynchronously. The message body is serialized with `serialize()`.

```php
use UbeeDev\LibBundle\Producer\EmailProducer;
use UbeeDev\LibBundle\Model\Type\Email;

class RegistrationService
{
    public function __construct(private EmailProducer $emailProducer) {}

    public function onUserRegistered(string $userEmail): void
    {
        $this->emailProducer->sendMail(
            'noreply@example.com',       // from (string or array)
            ['user@example.com'],         // to
            '<h1>Welcome!</h1>',          // body (HTML)
            'Welcome to the platform',    // subject
            [],                           // attachments
            0,                            // retryNumber
            null                          // replyTo (?Email)
        );
    }
}
```

The `$from` parameter accepts a `string` or an `array` (for name + address). The `$replyTo` parameter accepts an `Email` value object:

```php
$this->emailProducer->sendMail(
    ['Company Name' => 'noreply@example.com'],
    ['user@example.com'],
    '<p>Check your invoice</p>',
    'Invoice #1042',
    ['/tmp/invoice-1042.pdf'],
    0,
    Email::from('support@example.com')
);
```

### BulkEmailProducer

Sends bulk or marketing emails. The message body is JSON-encoded.

```php
use UbeeDev\LibBundle\Producer\BulkEmailProducer;

class NewsletterService
{
    public function __construct(private BulkEmailProducer $bulkEmailProducer) {}

    public function sendNewsletter(array $recipients, string $templateId): void
    {
        $this->bulkEmailProducer->sendBulkEmail([
            'recipients' => $recipients,
            'templateId' => $templateId,
            'subject' => 'Monthly Newsletter',
        ]);
    }
}
```

The `$options` array is merged with `retryNumber` before publishing. The consumer delegates to a `BulkEmailProviderInterface` implementation.

### ErrorProducer

Reports errors to Slack via the `error-exchange` queue. Does not support delayed retries (direct exchange).

```php
use UbeeDev\LibBundle\Producer\ErrorProducer;

class PaymentService
{
    public function __construct(private ErrorProducer $errorProducer) {}

    public function processPayment(int $orderId): void
    {
        try {
            // ... payment logic
        } catch (\Exception $e) {
            $this->errorProducer->sendNotification(
                'PaymentService',          // component
                'processPayment',          // function
                ['orderId' => $orderId],   // params (context)
                $e->getMessage()           // exception message
            );
        }
    }
}
```

### SlackNotificationProducer

Queues Slack messages for asynchronous delivery. Provides two methods:

**`publish()`** -- high-level helper that accepts a snippet directly:

```php
use UbeeDev\LibBundle\Producer\SlackNotificationProducer;
use UbeeDev\LibBundle\Service\Slack\TextSnippet;

class DeployService
{
    public function __construct(private SlackNotificationProducer $slackProducer) {}

    public function notifyDeploy(string $version): void
    {
        $this->slackProducer->publish(
            '#deployments',
            'Deploy Complete',
            new TextSnippet("$version deployed successfully")
        );
    }
}
```

**`sendNotification()`** -- low-level method that accepts a raw options array:

```php
$this->slackProducer->sendNotification([
    'channel' => '#alerts',
    'title' => 'Disk usage warning',
    'snippet' => (new TextSnippet('Disk at 90%'))->jsonSerialize(),
], $retryNumber);
```

Thread replies are supported by passing a `$threadTs`:

```php
$this->slackProducer->publish('#deployments', 'Migration log', new TextSnippet($log), $threadTs);
```

### CompressPdfProducer

Queues PDF compression jobs. The consumer uses Ghostscript (`gs`) to compress the file in-place.

```php
use UbeeDev\LibBundle\Producer\CompressPdfProducer;

class DocumentService
{
    public function __construct(private CompressPdfProducer $compressPdfProducer) {}

    public function compressUploadedPdf(string $filePath): void
    {
        $this->compressPdfProducer->sendCompressPdf([
            'filePath' => $filePath,
        ]);
    }
}
```

> **Requirement**: The consumer host must have Ghostscript installed (`apt-get install ghostscript`).

---

## Consumers

Each producer has a matching consumer registered via `config/packages/old_sound_rabbit_mq.yaml`. Run a consumer with:

```bash
php bin/console rabbitmq:consumer send_email
php bin/console rabbitmq:consumer send_bulk_email
php bin/console rabbitmq:consumer send_slack_notification
php bin/console rabbitmq:consumer error
php bin/console rabbitmq:consumer compress_pdf
```

### EmailConsumer

Deserializes the message, sends the email through the `Mailer` service, and retries up to 2 times on failure. After exhausting retries, it reports the error via `ErrorProducer`. The email body is stripped from the error report to avoid large payloads.

### BulkEmailConsumer

Decodes the JSON message, strips the `retryNumber` key, and delegates to the application's `BulkEmailProviderInterface` implementation. Retries up to 2 times, then reports via `ErrorProducer`.

### ErrorConsumer

Receives error reports and posts them to Slack as a JSON snippet via `SlackManager`. Requires the `errorChannel` parameter to be configured. If Slack delivery fails and `errorEmail` is set, it falls back to sending the error by email through `EmailProducer`.

### SlackNotificationConsumer

Deserializes the snippet from the message (using the `class` key stored during serialization), sends it through `SlackManager`, and retries up to 2 times. After exhausting retries, it reports via `ErrorProducer`.

### CompressPdfConsumer

Compresses the PDF at the given `filePath` using Ghostscript with `/ebook` quality settings. On success the original file is replaced. On error the temporary file is removed and the error is reported via `ErrorProducer`. This consumer does not retry.

---

## Retry Mechanism

Producers that use `x-delayed-message` exchanges support automatic retries with escalating delays. The retry logic lives in `AbstractProducer::getXDelayForRetryNumber()`:

| `$retryNumber` | Delay          |
|-----------------|---------------|
| `0`             | No delay       |
| `1`             | 5 minutes      |
| `2+`            | 15 minutes     |

When a consumer catches an exception, it increments `retryNumber` and re-publishes the message through the same producer. Once `retryNumber >= 2`, the consumer stops retrying and reports the failure to `ErrorProducer` instead.

```
Producer ---> Queue ---> Consumer
                            |
                            |--> Success: ACK
                            |--> Failure (retry < 2): re-publish with delay
                            |--> Failure (retry >= 2): report to ErrorProducer
```

---

## SlackManager

`SlackManager` is the low-level service that sends messages to Slack. It handles channel name-to-ID resolution, block formatting, content chunking for long text, and file uploads for non-text snippets.

### Single notification

```php
use UbeeDev\LibBundle\Service\SlackManager;
use UbeeDev\LibBundle\Service\Slack\TextSnippet;

$this->slackManager->sendNotification(
    '#alerts',
    'Error Report',
    new TextSnippet('Something went wrong')
);
```

### File upload

Non-text snippets (JSON, file, shell) are uploaded as Slack files using the three-step upload API (`files.getUploadURLExternal`, upload content, `files.completeUploadExternal`):

```php
use UbeeDev\LibBundle\Service\Slack\JsonSnippet;
use UbeeDev\LibBundle\Service\Slack\FileSnippet;

// JSON data as a file snippet
$this->slackManager->sendNotification('#alerts', 'API Response', new JsonSnippet([
    'status' => 'error',
    'code' => 503,
    'message' => 'Service unavailable',
]));

// Upload an existing file
$this->slackManager->sendNotification('#reports', 'Daily Report', new FileSnippet('/tmp/report.csv'));
```

### Multiple snippets as a thread

`sendNotifications()` creates a parent message and posts each snippet as a threaded reply:

```php
use UbeeDev\LibBundle\Service\Slack\TextSnippet;
use UbeeDev\LibBundle\Service\Slack\JsonSnippet;

$this->slackManager->sendNotifications('#alerts', 'Full Report', [
    new TextSnippet('Summary of the issue'),
    new JsonSnippet(['error' => 'timeout', 'duration' => 30]),
]);
```

### Thread replies

Pass a `$threadTs` to reply in an existing thread:

```php
$response = $this->slackManager->sendNotification('#ops', 'Deploy started', new TextSnippet('v2.0.16'));
$threadTs = $response['ts'];

$this->slackManager->sendNotification('#ops', 'Migrations', new TextSnippet('3 migrations executed'), $threadTs);
```

### Channel resolution

Channels can be specified as names (`#alerts`) or IDs (`C1234567890`). Names are resolved to IDs automatically by querying `conversations.list`. The bot must be invited to private channels for name resolution to work.

### Muting notifications

When the environment variable `MUTE_OPS_ALERTS` is set to `true`, all notifications are silently skipped and `sendNotification()` / `sendNotifications()` return early with `['notSent' => 'Ops alerts are muted in this environment']`.

### Long content handling

Text content exceeding the Slack block limit (3000 characters by default) is automatically split into multiple blocks. Each block respects line boundaries when possible.

---

## Slack Snippets

Four snippet types control how content is formatted and delivered to Slack. All extend `AbstractSnippet` and implement `SlackSnippetInterface`.

```php
interface SlackSnippetInterface extends \JsonSerializable
{
    public function getFileName(): string;
    public function getSnippetType(): ?string;
    public function getContent(): string;
}
```

### TextSnippet

Sent as a Slack block message (not a file upload). Best for short notifications.

```php
use UbeeDev\LibBundle\Service\Slack\TextSnippet;

$snippet = new TextSnippet('Deployment completed in 42s');
```

### JsonSnippet

Uploaded as a JSON file. Accepts any value that `json_encode` can handle. The content is pretty-printed automatically.

```php
use UbeeDev\LibBundle\Service\Slack\JsonSnippet;

$snippet = new JsonSnippet([
    'orderId' => 1042,
    'status' => 'failed',
    'reason' => 'Payment declined',
]);
```

### FileSnippet

Uploads an existing file from disk. The snippet type is inferred from the file extension (supports `js`, `json`, `php`, `py`, `xml`, `yaml`, `html`, `css`, `sql`, `log`, `csv`). Throws `InvalidArgumentException` if the file does not exist.

```php
use UbeeDev\LibBundle\Service\Slack\FileSnippet;

$snippet = new FileSnippet('/var/log/app/error.log');
```

### ShellSnippet

Uploaded as a shell-formatted file. Useful for sending command output.

```php
use UbeeDev\LibBundle\Service\Slack\ShellSnippet;

$output = shell_exec('df -h');
$snippet = new ShellSnippet($output);
```

### Serialization

Snippets implement `JsonSerializable`. When passed through RabbitMQ (via `SlackNotificationProducer`), the snippet is serialized to `['content' => ..., 'class' => ...]` and reconstructed by `SlackNotificationConsumer` on the other side.
