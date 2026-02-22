# Services Reference

The services layer provides the main building blocks of the bundle -- autowirable Symfony services for media management, email sending, S3 storage, Slack notifications, PDF generation, markdown parsing, form protection, and more. Each service is registered in the container and pre-configured through the bundle's configuration, so consuming projects can inject them directly without boilerplate setup.

## Table of Contents

- [Media & Files](#media--files)
  - [MediaManager](#mediamanager)
  - [PdfGenerator](#pdfgenerator)
  - [ObjectStorage](#objectstorage)
  - [SpreadsheetExporter](#spreadsheetexporter)
- [Email & Notifications](#email--notifications)
  - [Mailer](#mailer)
  - [SlackManager](#slackmanager)
- [Parsing & Rendering](#parsing--rendering)
  - [TextParser](#textparser)
  - [MarkdownParser](#markdownparser)
- [Forms & Security](#forms--security)
  - [FormManager](#formmanager)
  - [AntiRobot](#antirobot)
  - [Signer](#signer)
- [Database](#database)
  - [BackupDatabase](#backupdatabase)
- [HTTP & Tracking](#http--tracking)
  - [UtmManager](#utmmanager)
  - [Paginator](#paginator)
- [Utilities](#utilities)
  - [Utils](#utils)

---

## Media & Files

### MediaManager

Handles file uploads, deletion, PDF generation, and image processing. Uploaded JPEG/PNG images are automatically converted to WebP format when the Imagick extension is available. Image dimensions are extracted and stored on the `Media` entity.

**Class:** `UbeeDev\LibBundle\Service\MediaManager`

#### upload

Uploads a file, persists a `Media` entity, and returns it. The file is stored under `public/` or `private/` depending on the `$private` flag, organized by context and year-month subdirectories.

```php
use Symfony\Component\HttpFoundation\File\File;

/** @var \UbeeDev\LibBundle\Service\MediaManager $mediaManager */

// Upload a public file
$file = new File('/tmp/uploaded-photo.jpg');
$media = $mediaManager->upload($file, 'avatars');

// Upload a private file (not web-accessible)
$media = $mediaManager->upload($file, 'invoices', private: true);

// Upload without flushing (useful inside a larger transaction)
$media = $mediaManager->upload($file, 'avatars', andFlush: false);
```

#### delete

Removes the `Media` entity from the database. To also remove the physical file, call `deleteAsset` first.

```php
$mediaManager->deleteAsset($media); // remove file from disk
$mediaManager->delete($media);      // remove entity from database
```

#### deleteAsset

Deletes only the physical file from disk, leaving the entity in the database.

```php
$mediaManager->deleteAsset($media);
```

#### getWebPath

Returns the public URL path for a media file. Throws a `RuntimeException` if the media is private.

```php
$url = $mediaManager->getWebPath($media);
// e.g. "/uploads/avatars/202601/a1b2c3d4.webp"
```

#### getRelativePath

Returns the absolute filesystem path to the media file.

```php
$path = $mediaManager->getRelativePath($media);
// e.g. "/var/www/project/public/uploads/avatars/202601/a1b2c3d4.webp"
```

#### updateImageDimensions

Reads image dimensions from the file on disk and stores width/height on the entity. Returns `false` if the media is not an image or the file does not exist.

```php
$updated = $mediaManager->updateImageDimensions($media);

if ($updated) {
    echo $media->getWidth() . 'x' . $media->getHeight();
}
```

#### convertMediaToWebp

Converts an existing JPEG/PNG media to WebP format on disk and updates the entity metadata (filename, content type, size). Requires the Imagick PHP extension.

```php
$converted = $mediaManager->convertMediaToWebp($media);

if ($converted) {
    // $media->getContentType() is now 'image/webp'
    $entityManager->flush();
}
```

#### createPdfFromHtml

Generates a PDF file from HTML content using dompdf, stores it on disk, persists a `Media` entity, and returns it.

```php
$html = '<h1>Invoice #123</h1><p>Total: 99.00 EUR</p>';
$media = $mediaManager->createPdfFromHtml($html, 'invoices');

// Private PDF (not web-accessible)
$media = $mediaManager->createPdfFromHtml($html, 'invoices', private: true);
```

---

### PdfGenerator

Generates raw PDF content from HTML using dompdf. Use this when you need the PDF binary string without persisting a `Media` entity.

**Class:** `UbeeDev\LibBundle\Service\PdfGenerator`

#### htmlToPdfContent

Returns the raw PDF binary content as a string. The output uses A4 portrait format with HTML5 parsing enabled.

```php
/** @var \UbeeDev\LibBundle\Service\PdfGenerator $pdfGenerator */

$html = '<h1>Report</h1><p>Generated on ' . date('Y-m-d') . '</p>';
$pdfContent = $pdfGenerator->htmlToPdfContent($html);

// Return as a Symfony response
return new Response($pdfContent, 200, [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'attachment; filename="report.pdf"',
]);
```

---

### ObjectStorage

Abstraction layer for S3-compatible object storage providers (AWS S3, OVH, MinIO, etc.). Provides a unified interface for uploading, downloading, listing, and deleting objects in buckets. The bundle pre-configures the client with environment variables (key, secret, region) so callers never need to build provider-specific option arrays.

**Interface:** `UbeeDev\LibBundle\Service\ObjectStorageInterface`

**Implementations:**
- `UbeeDev\LibBundle\Service\ObjectStorage\S3ObjectStorage` -- AWS S3 (default)
- `UbeeDev\LibBundle\Service\ObjectStorage\OvhObjectStorage` -- OVH (S3-compatible with custom endpoint)

To switch provider, see [ObjectStorage Configuration](configuration.md#objectstorage-configuration).

#### upload

Uploads a local file and returns the public object URL.

```php
/** @var \UbeeDev\LibBundle\Service\ObjectStorageInterface $storage */

$objectUrl = $storage->upload('/tmp/backup.sql', 'my-bucket', 'backups/2026/backup.sql');
// Returns: "https://my-bucket.s3.amazonaws.com/backups/2026/backup.sql"
```

#### get

Returns the effective URI of an object.

```php
$uri = $storage->get('my-bucket', 'backups/2026/backup.sql');
```

#### download

Downloads an object to a local temporary file and returns the local file path.

```php
$localPath = $storage->download('my-bucket', 'backups/2026/backup.sql', '/tmp/downloads', 'backup.sql');
// Returns: "/tmp/downloads/backup.sql"
```

#### delete

Deletes an object. Returns `true` on success.

```php
$storage->delete('my-bucket', 'backups/2026/backup.sql');
```

#### list

Lists object keys in a bucket, optionally filtered by prefix.

```php
$keys = $storage->list('my-bucket', 'backups/2026/');
// Returns: ["backups/2026/backup-01.sql", "backups/2026/backup-02.sql"]
```

---

### SpreadsheetExporter

Exports data to an Excel spreadsheet using phpoffice/phpspreadsheet. Supports accessor chaining via dot notation and optional formatting functions.

**Class:** `UbeeDev\LibBundle\Service\SpreadsheetExporter`

#### exportSpreadSheet

Returns a `Spreadsheet` object that you can write to a file or stream as a download.

Each descriptor is an array with:
- `[0]` -- Header label (string)
- `[1]` -- Property path using dot notation, or an array of paths (e.g. `'user.email'`)
- `[2]` -- (optional) Formatter method name on the injected formatter object
- `[3]` -- (optional) Extra parameters passed to the getter

```php
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** @var \UbeeDev\LibBundle\Service\SpreadsheetExporter $exporter */

$users = $userRepository->findAll();

$descriptors = [
    ['ID',         'id'],
    ['Full Name',  'fullName'],
    ['Email',      'email'],
    ['Created At', 'createdAt'],
    ['Role',       'role',  'formatRole'],  // calls $formatter->formatRole($role)
];

$spreadsheet = $exporter->exportSpreadSheet('Users Export', $users, $descriptors);

// Write to file
$writer = new Xlsx($spreadsheet);
$writer->save('/tmp/users.xlsx');

// Or stream as a Symfony response
$response = new StreamedResponse(function () use ($writer) {
    $writer->save('php://output');
});
$response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$response->headers->set('Content-Disposition', 'attachment; filename="users.xlsx"');

return $response;
```

---

## Email & Notifications

### Mailer

Sends emails through a pluggable provider system. The actual transport is determined by the `EmailProviderInterface` implementation injected at service configuration time.

**Class:** `UbeeDev\LibBundle\Service\Mailer`

**Available providers:**
- `UbeeDev\LibBundle\Service\EmailProvider\Mailchimp\EmailProvider`
- `UbeeDev\LibBundle\Service\EmailProvider\Gmail\EmailProvider`
- `UbeeDev\LibBundle\Service\EmailProvider\SymfonyMailer\EmailProvider`

**Constants:**
- `Mailer::HTML_CONTENT_TYPE` = `'html'`
- `Mailer::TEXT_CONTENT_TYPE` = `'text'`

#### sendMail

```php
/** @var \UbeeDev\LibBundle\Service\Mailer $mailer */

// Simple HTML email
$result = $mailer->sendMail(
    from: 'noreply@example.com',
    to: ['user@example.com'],
    body: '<h1>Welcome!</h1><p>Thanks for signing up.</p>',
    subject: 'Welcome to our platform'
);

// With reply-to and named sender
$result = $mailer->sendMail(
    from: ['contact@example.com' => 'My App'],
    to: ['user@example.com'],
    body: '<p>Your order has shipped.</p>',
    subject: 'Order Confirmation',
    replyTo: 'support@example.com'
);

// Plain text email
$result = $mailer->sendMail(
    from: 'noreply@example.com',
    to: ['user@example.com'],
    body: 'Your verification code is 123456',
    subject: 'Verification Code',
    contentType: Mailer::TEXT_CONTENT_TYPE
);

// With attachments
$result = $mailer->sendMail(
    from: 'noreply@example.com',
    to: ['user@example.com'],
    body: '<p>Please find the report attached.</p>',
    subject: 'Monthly Report',
    attachments: ['/tmp/report.pdf']
);
```

---

### SlackManager

Sends notifications to Slack channels using the Slack API. Supports text messages, JSON data, file uploads, and threaded messages. Automatically splits long text messages into multiple Slack blocks.

**Class:** `UbeeDev\LibBundle\Service\SlackManager`

#### sendNotification

Sends a single notification to a Slack channel. For `TextSnippet`, the message is sent as Slack blocks. For all other snippet types (`JsonSnippet`, `FileSnippet`, `ShellSnippet`), the content is uploaded as a file attachment.

```php
use UbeeDev\LibBundle\Service\Slack\TextSnippet;
use UbeeDev\LibBundle\Service\Slack\JsonSnippet;
use UbeeDev\LibBundle\Service\Slack\FileSnippet;
use UbeeDev\LibBundle\Service\Slack\ShellSnippet;

/** @var \UbeeDev\LibBundle\Service\SlackManager $slack */

// Send a text notification
$slack->sendNotification(
    '#ops-alerts',
    ':white_check_mark: Deploy successful',
    new TextSnippet('Version 2.4.1 deployed to production.')
);

// Send JSON data as a file snippet
$slack->sendNotification(
    '#ops-alerts',
    ':warning: Error report',
    new JsonSnippet(['error' => 'Connection timeout', 'service' => 'payment-api'])
);

// Send a log file
$slack->sendNotification(
    '#ops-alerts',
    ':page_facing_up: Latest logs',
    new FileSnippet('/var/log/app/error.log')
);

// Send shell command output
$slack->sendNotification(
    '#ops-alerts',
    ':computer: Server status',
    new ShellSnippet('uptime: 42 days, load: 0.5')
);

// Reply in a thread
$response = $slack->sendNotification('#ops-alerts', 'Deploy started', new TextSnippet('Starting...'));
$threadTs = $response['ts'];
$slack->sendNotification('#ops-alerts', 'Step 1', new TextSnippet('Migrations done.'), $threadTs);
```

#### sendNotifications

Sends multiple snippets as a thread. The first message is the parent, and each snippet becomes a reply.

```php
$slack->sendNotifications('#ops-alerts', ':rocket: Deploy Report', [
    new TextSnippet('Deploy completed in 45s.'),
    new JsonSnippet(['version' => '2.4.1', 'commits' => 12]),
    new FileSnippet('/tmp/deploy.log'),
]);
```

**Snippet types summary:**

| Class | Sent as | Use case |
|---|---|---|
| `TextSnippet` | Slack blocks message | Short notifications, status updates |
| `JsonSnippet` | File upload (JSON) | Structured data, error payloads |
| `FileSnippet` | File upload (auto-detected type) | Log files, SQL dumps, any file on disk |
| `ShellSnippet` | File upload (shell) | Command output |

---

## Parsing & Rendering

### TextParser

Simple text-to-HTML parser that converts newline characters to `<br>` tags.

**Class:** `UbeeDev\LibBundle\Service\TextParser`

#### parse

```php
/** @var \UbeeDev\LibBundle\Service\TextParser $textParser */

$html = $textParser->parse('Line one\nLine two');
// Returns: "Line one<br>Line two"
```

---

### MarkdownParser

Extended markdown parser built on ParsedownExtra. Implements `MarkdownParserInterface`. Adds custom syntax for rich content blocks, auto-injects image dimensions from the `Media` entity, and applies Bootstrap responsive classes.

**Class:** `UbeeDev\LibBundle\Service\MarkdownParser`

#### parse

Parses markdown to HTML. With `$fullParsing = true` (default), all custom syntax extensions and Bootstrap post-processing are applied. With `$fullParsing = false`, only standard markdown and superscript parsing are used.

```php
/** @var \UbeeDev\LibBundle\Service\MarkdownParser $parser */

// Full parsing with all extensions
$html = $parser->parse('## Hello World');

// Basic parsing only (no custom blocks, no Bootstrap classes)
$html = $parser->parse('## Hello World', fullParsing: false);
```

#### Custom syntax reference

**YouTube embed:**

```markdown
{youtube:https://www.youtube.com/watch?v=dQw4w9WgXcQ}
```

Renders a responsive 16:9 iframe.

**Vimeo embed:**

```markdown
{vimeo:https://vimeo.com/123456789}
```

**Button:**

```markdown
{ [Click here](/pricing) }
```

Renders as a styled `<a>` with `btn btn_dark` classes.

**Strong/spotlight text:**

```markdown
{strong:highlighted text}
```

Renders as `<strong class="spotlight">highlighted text</strong>`.

**Superscript:**

```markdown
This is trademarked^TM^
```

Renders as `This is trademarked<sup>TM</sup>`.

**Steps block:**

```markdown
{steps-start}
{step:1:Create account}
Go to the registration page and fill in your details.
- Enter your email
- Choose a password
{/step}
{step:2:Verify email}
Check your inbox for the confirmation link.
{/step}
{steps-end}
```

**Features grid:**

```markdown
{features-start}
{feature:fas fa-bolt:Fast:Lightning fast response times across all endpoints.}
{feature:fas fa-shield-alt:Secure:End-to-end encryption for all data.}
{features-end}
```

**Events grid:**

```markdown
{events-grid-start}
2024|Paris|12 participants
2025|London|15 participants
{note}All events include networking sessions.
{events-grid-end}
```

**Timeline:**

```markdown
{timeline-start}
{title}Our History
2020|Company founded in Paris.
2022|Launched version 2.0.
2025|Reached 10,000 customers.
{timeline-end}
```

**CTA banner:**

```markdown
{cta-banner-start}
{title}Ready to get started?
{description}Join thousands of developers who trust our platform. **No credit card required.**
{button1}Start Free Trial|/signup
{button2}View Pricing|/pricing
{cta-banner-end}
```

**CTA banner extended (with features column):**

```markdown
{cta-banner-extended-start}
{title}Enterprise Plan
{description}Everything you need to scale your business.
{button1}Contact Sales|/contact
{button2}See Demo|/demo
{features}
fas fa-users|Team Management|Manage up to 500 team members.
fas fa-lock|SSO|SAML-based single sign-on.
fas fa-headset|Priority Support|24/7 dedicated support.
{/features}
{cta-banner-extended-end}
```

**Callout block:**

```markdown
{callout-block-start}
{type}warning
{title}Deprecation Notice
{description}This API endpoint will be removed in v3. Please migrate to the new `/api/v2/users` endpoint.
{callout-block-end}
```

Type is optional (defaults to `info`). Supported types: `info`, `warning`.

**Iframe:**

```markdown
{iframe:https://example.com/embed,height.400}
```

**Video timestamp link:**

```markdown
{videoTime:1:23:45}
```

Renders a clickable timestamp link with a `data-video-time` attribute in seconds.

#### Post-processing features

When `$fullParsing = true`, the parser also:

- Adds `class="table table-bordered"` to all `<table>` elements
- Wraps tables in `<div class="table-responsive">`
- Adds `class="blockquote"` to all `<blockquote>` elements
- Adds `class="js-external-link-target"` to links
- Adds `target="_blank"` and `rel="noopener noreferrer"` to external links
- Adds `rel="nofollow"` to links pointing to social media and marketplace domains
- Injects `width` and `height` attributes on `<img>` tags by looking up the `Media` entity by filename

---

## Forms & Security

### FormManager

Provides honeypot-based bot detection and CSRF token validation for forms.

**Class:** `UbeeDev\LibBundle\Service\FormManager`

**Constant:** `FormManager::MAX_EXECUTION_TIME = 2` -- Forms submitted in under 2 seconds are flagged as bot submissions.

#### wasFilledByARobot

Checks multiple signals to determine if a form was submitted by a bot: hidden honeypot fields, execution time, HTML injection, and identical first/last name values.

```php
/** @var \UbeeDev\LibBundle\Service\FormManager $formManager */

// In a controller
public function submitContact(Request $request): Response
{
    if ($formManager->wasFilledByARobot($request)) {
        // Silently reject -- do not reveal detection to the bot
        return new JsonResponse(['status' => 'ok']);
    }

    // Process the real submission...
}

// With custom field names
$isBot = $formManager->wasFilledByARobot(
    $request,
    firstNameField: 'first_name',
    lastNameField: 'last_name',
    emailField: 'user_email'
);

// When form data is nested under a key
$isBot = $formManager->wasFilledByARobot(
    $request,
    dataPath: 'contact_form'
);
```

#### removeAntiSpamFields

Strips the honeypot fields (`as_first`, `as_second`, `execution_time`) from the submitted data before processing.

```php
$data = $request->request->all();
$cleanData = $formManager->removeAntiSpamFields($data);
// $cleanData no longer contains as_first, as_second, execution_time
```

#### csrfTokenIsValid

Validates a CSRF token against a given token ID.

```php
$token = $request->request->get('_token');

if (!$formManager->csrfTokenIsValid($token, 'contact_form')) {
    throw new AccessDeniedHttpException('Invalid CSRF token.');
}
```

---

### AntiRobot

A pluggable anti-robot verification system with a factory pattern. Verifiers are auto-registered via a compiler pass and can be selected by name.

#### AntiRobotVerifierInterface

All verifiers implement this interface:

```php
interface AntiRobotVerifierInterface
{
    public function verify(Request $request, array $parameters): bool; // true = human
    public function getName(): string;
    public function getTemplateData(): array;
}
```

#### AntiRobotVerifierFactory

**Class:** `UbeeDev\LibBundle\Service\AntiRobot\AntiRobotVerifierFactory`

Retrieves a verifier by name, or falls back to the configured default (`honeypot`).

```php
/** @var \UbeeDev\LibBundle\Service\AntiRobot\AntiRobotVerifierFactory $factory */

// Get the default verifier
$verifier = $factory->getVerifier();

// Get a specific verifier
$verifier = $factory->getVerifier('turnstile');

// List available verifiers
$names = $factory->getAvailableVerifiers();
// e.g. ['honeypot', 'turnstile']
```

#### HoneypotVerifier

**Class:** `UbeeDev\LibBundle\Service\AntiRobot\HoneypotVerifier`

Uses `FormManager::wasFilledByARobot` under the hood. Returns `true` if the request comes from a human, `false` if it is a bot.

```php
$verifier = $factory->getVerifier('honeypot');

if (!$verifier->verify($request, $request->request->all())) {
    // Bot detected
    return new JsonResponse(['status' => 'ok']); // silent reject
}

// Template data for the frontend
$templateData = $verifier->getTemplateData();
// ['requires_javascript' => true, 'script_path' => '/assets/js/anti-spam.js']
```

#### TurnstileVerifier

**Class:** `UbeeDev\LibBundle\Service\AntiRobot\TurnstileVerifier`

Verifies the Cloudflare Turnstile challenge response token by calling the Cloudflare API.

```php
$verifier = $factory->getVerifier('turnstile');

$parameters = $request->request->all();
// $parameters must contain 'cf-turnstile-response' from the frontend widget

if (!$verifier->verify($request, $parameters)) {
    return new JsonResponse(['error' => 'Verification failed'], 403);
}

// Template data for the frontend (use in Twig to render the widget)
$templateData = $verifier->getTemplateData();
// ['site_key' => 'xxx', 'script_url' => 'https://challenges.cloudflare.com/turnstile/v0/api.js']
```

#### Twig integration

The `AntiRobotExtension` provides two Twig functions:

```twig
{# Get template data for the default verifier #}
{% set antiRobotData = anti_robot_data() %}

{# Get template data for a specific verifier #}
{% set antiRobotData = anti_robot_data('turnstile') %}

{# Get the active verifier name #}
{% set verifierName = anti_robot_verifier() %}

{# Example: Turnstile widget #}
{% if anti_robot_verifier() == 'turnstile' %}
    {% set data = anti_robot_data('turnstile') %}
    <script src="{{ data.script_url }}" async defer></script>
    <div class="cf-turnstile" data-sitekey="{{ data.site_key }}"></div>
{% endif %}
```

---

### Signer

Generates HMAC-style SHA-256 signatures from data arrays. Useful for webhook verification or tamper-proof URLs.

**Class:** `UbeeDev\LibBundle\Service\Signer`

#### sign

Concatenates all array values with the secret and produces a SHA-256 hash.

```php
/** @var \UbeeDev\LibBundle\Service\Signer $signer */

$signature = $signer->sign(
    ['order_id' => '123', 'amount' => '99.00'],
    'my-secret-key'
);
// Returns a 64-character hex string

// Verify an incoming signature
$expected = $signer->sign($data, $secret);
if (!hash_equals($expected, $incomingSignature)) {
    throw new AccessDeniedHttpException('Invalid signature.');
}
```

---

## Database

### BackupDatabase

Dumps and restores databases using a pluggable `DatabaseDumperInterface`. The default implementation is `MysqlDumper`; `PostgresDumper` is also available.

**Class:** `UbeeDev\LibBundle\Service\BackupDatabase`

#### dump

Creates a SQL dump file in the specified backup folder, organized by database name. Returns the path to the created dump file.

```php
use Doctrine\DBAL\Connection;

/** @var \UbeeDev\LibBundle\Service\BackupDatabase $backup */
/** @var Connection $connection */

$dumpFile = $backup->dump($connection, '/var/backups');
// Returns: "/var/backups/my_database/2026-02-20 14:30:00.sql"
```

#### restore

Restores a database from a SQL dump file.

```php
$backup->restore($connection, '/var/backups/my_database/2026-02-20 14:30:00.sql');
```

#### Combining with ObjectStorage

```php
// Backup and upload
$dumpFile = $backup->dump($connection, '/tmp/backups');
$storage->upload($dumpFile, 'my-backup-bucket', 'db/' . basename($dumpFile));

// Download and restore
$localFile = $storage->download('my-backup-bucket', 'db/2026-02-20 14:30:00.sql', '/tmp', 'restore.sql');
$backup->restore($connection, $localFile);
```

---

## HTTP & Tracking

### UtmManager

Extracts UTM tracking parameters from the request query string or cookies, and appends them to URLs.

**Class:** `UbeeDev\LibBundle\Service\UtmManager`

#### getUTMParamsFromRequest

Returns an associative array of UTM parameters. Checks query parameters first, then falls back to a `utm` cookie (JSON-encoded).

```php
/** @var \UbeeDev\LibBundle\Service\UtmManager $utmManager */

$utms = $utmManager->getUTMParamsFromRequest($request);
// e.g. ['utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'spring-sale']
```

#### utmParams

Appends UTM parameters from the current request to a given URL. Handles URLs that already have query parameters.

```php
$url = $utmManager->utmParams('https://example.com/pricing', $request);
// "https://example.com/pricing?utm_source=google&utm_medium=cpc"

$url = $utmManager->utmParams('https://example.com/pricing?plan=pro', $request);
// "https://example.com/pricing?plan=pro&utm_source=google&utm_medium=cpc"
```

---

### Paginator

Paginates Doctrine query results using `page` and `per` query parameters. Returns a `PaginatedResult` object with metadata for building pagination UI.

**Class:** `UbeeDev\LibBundle\Service\Paginator`

#### getPaginatedQueryResult

Applies pagination to a `QueryBuilder`, executes the query, and returns a `PaginatedResult`. The maximum page size is capped at 50. An optional DTO class can be provided to transform each result.

```php
/** @var \UbeeDev\LibBundle\Service\Paginator $paginator */

// In a controller -- URL: /users?page=2&per=20
$qb = $userRepository->createQueryBuilder('u')->orderBy('u.createdAt', 'DESC');
$result = $paginator->getPaginatedQueryResult($qb, $request);

$users          = $result->getCurrentPageResults();
$totalUsers     = $result->getNbTotalResults();
$currentPage    = $result->getPageNumber();
$pageSize       = $result->getPageSize();
$lastPage       = $result->getLastPageNumber();
$hasNext        = $result->hasNextPage();
$hasPrevious    = $result->hasPreviousPage();
$nextPage       = $result->getNextPageNumber();       // null if last page
$previousPage   = $result->getPreviousPageNumber();    // null if first page
```

With a DTO class:

```php
$result = $paginator->getPaginatedQueryResult($qb, $request, UserDto::class);
// Each item in getCurrentPageResults() is now a UserDto instance
```

With extra constructor parameters for the DTO:

```php
$result = $paginator->getPaginatedQueryResult($qb, $request, UserDto::class, [$mediaManager]);
// Each DTO is constructed as: new UserDto($entity, $mediaManager)
```

---

## Utilities

### Utils

General-purpose string utilities. Use this service when you need to transliterate user-supplied text into ASCII-safe strings -- for example, generating URL slugs or sanitizing filenames. It replaces accented and special characters with their closest ASCII equivalents and collapses non-word characters into hyphens.

**Class:** `UbeeDev\LibBundle\Service\Utils`

#### removeAccentsFromString

Replaces accented characters with their ASCII equivalents and replaces non-word characters with hyphens. Useful for generating URL slugs.

```php
/** @var \UbeeDev\LibBundle\Service\Utils $utils */

$slug = $utils->removeAccentsFromString('Cafe resume naive');
// Returns: "Cafe-resume-naive"

$slug = $utils->removeAccentsFromString('Les Miserables a Paris');
// Returns: "Les-Miserables-a-Paris"
```
