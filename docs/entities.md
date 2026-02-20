# Entities

This document covers all entity classes provided by the bundle: base classes for your own entities, date/time value objects, and ready-to-use domain entities.

## Table of Contents

- [AbstractEntity](#abstractentity)
- [AbstractActivableEntity](#abstractactivableentity)
- [AbstractDateTime](#abstractdatetime)
- [DateTime](#datetime)
- [Date](#date)
- [Media](#media)
- [Address](#address)
- [FooterLink](#footerlink)
- [PostDeployExecution](#postdeployexecution)

---

## AbstractEntity

**Namespace:** `UbeeDev\LibBundle\Entity\AbstractEntity`

Base class for all Doctrine entities. It provides an auto-incremented primary key and automatic timestamp management through Doctrine lifecycle callbacks.

### Fields

| Property | Type | Description |
|----------|------|-------------|
| `$id` | `?int` | Auto-generated primary key. |
| `$createdAt` | `?DateTime` | Set automatically on first persist. |
| `$updatedAt` | `?DateTime` | Set automatically on every persist and update. |

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `getId()` | `?int` | Returns the entity ID. |
| `getCreatedAt()` | `?DateTime` | Returns the creation timestamp. |
| `setCreatedAt(DateTime)` | `static` | Manually sets the creation timestamp. |
| `getUpdatedAt()` | `?DateTime` | Returns the last update timestamp. |
| `setUpdatedAt(DateTime)` | `static` | Manually sets the update timestamp. |
| `updateTimestamps()` | `void` | Lifecycle callback (`PrePersist`, `PreUpdate`). Sets `updatedAt` to now; sets `createdAt` to now if it is not already set. |
| `getCollectionSortedBy($collectionName, $sort, string $sortType)` | `array` | Sorts a Doctrine collection property by one or more fields. |

### Automatic Timestamps

The `updateTimestamps()` method is registered as both `#[ORM\PrePersist]` and `#[ORM\PreUpdate]`. You never need to call it manually -- Doctrine triggers it automatically when the entity is persisted or updated.

### Sorting Collections

`getCollectionSortedBy()` lets you sort any Doctrine collection property (e.g., a `OneToMany` relation) by one or more getter fields:

```php
// Sort the "comments" collection by "createdAt" descending
$sorted = $article->getCollectionSortedBy('comments', 'createdAt', Criteria::DESC);

// Sort by multiple fields: first by "position", then by "name"
$sorted = $article->getCollectionSortedBy('items', ['position', 'name'], Criteria::ASC);
```

The first argument is the collection property name (the method `get{CollectionName}()` must exist). The second argument is a field name or an array of field names (each resolved via `get{FieldName}()`). The third argument is `Criteria::ASC` (default) or `Criteria::DESC`.

### Usage: Extending AbstractEntity

Every entity in your application should extend `AbstractEntity`:

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use UbeeDev\LibBundle\Entity\AbstractEntity;

#[ORM\Entity]
class Article extends AbstractEntity
{
    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $content;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }
}
```

You get `$id`, `$createdAt`, and `$updatedAt` for free. No need to define them or manage timestamps yourself:

```php
$article = new Article();
$article->setTitle('Hello World');
$article->setContent('This is my first article.');

$entityManager->persist($article);
$entityManager->flush();

// After flush, timestamps are set automatically:
$article->getId();        // e.g. 1
$article->getCreatedAt(); // DateTime object
$article->getUpdatedAt(); // DateTime object
```

---

## AbstractActivableEntity

**Namespace:** `UbeeDev\LibBundle\Entity\AbstractActivableEntity`

Extends `AbstractEntity` with an `active` boolean field. Use this for any entity that can be enabled or disabled.

### Fields

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$active` | `bool` | `false` | Whether the entity is active. |

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `isActive()` | `bool` | Returns the active status. |
| `setActive(bool)` | `self` | Sets the active status. |

### Usage: Extending AbstractActivableEntity

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use UbeeDev\LibBundle\Entity\AbstractActivableEntity;

#[ORM\Entity]
class Promotion extends AbstractActivableEntity
{
    #[ORM\Column(type: 'string')]
    private string $code;

    #[ORM\Column(type: 'integer')]
    private int $discountPercent;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getDiscountPercent(): int
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(int $discountPercent): self
    {
        $this->discountPercent = $discountPercent;
        return $this;
    }
}
```

```php
$promo = new Promotion();
$promo->setCode('SUMMER25');
$promo->setDiscountPercent(25);
$promo->setActive(true);

$promo->isActive(); // true

// Query only active promotions
$activePromos = $repository->findBy(['active' => true]);
```

---

## AbstractDateTime

**Namespace:** `UbeeDev\LibBundle\Entity\AbstractDateTime`

An abstract class extending PHP's native `\DateTime` with comparison and formatting methods. Implements `\JsonSerializable`. The default timezone is `Europe/Paris`.

This class also uses the `DateTimeTrait`, which provides a rich set of date manipulation utilities (adding seconds, minutes, hours, days, months; computing differences; timezone conversion, etc.).

### Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `DEFAULT_TIMEZONE` | `'Europe/Paris'` | Timezone used for formatting and display. |

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `isLater(DateTimeInterface, bool $strict = false)` | `bool` | Returns `true` if this date is later than (or equal to, when non-strict) the given date. |
| `isBefore(DateTimeInterface, bool $strict = false)` | `bool` | Returns `true` if this date is before (or equal to, when non-strict) the given date. |
| `isBetween(DateTimeInterface $start, DateTimeInterface $end)` | `bool` | Returns `true` if this date falls between `$start` and `$end` (inclusive). |
| `convertToString(bool $withDay = false)` | `string` | French-formatted date string. Without day: `"16 juillet 2019"`. With day: `"mardi 16 juillet 2019"`. |
| `startDuringGivenDay(DateTimeInterface)` | `bool` | Returns `true` if this date falls on the same calendar day as the given date. |
| `jsonSerialize()` | `string` | Returns the date in ISO 8601 format (e.g. `"2019-07-16T14:30:00+02:00"`). |

### Comparison Examples

```php
use UbeeDev\LibBundle\Entity\DateTime;

$now = new DateTime('now');
$tomorrow = new DateTime('tomorrow');
$yesterday = new DateTime('yesterday');

$now->isLater($yesterday);          // true (now >= yesterday)
$now->isLater($tomorrow);           // false
$now->isBefore($tomorrow);          // true (now <= tomorrow)
$now->isBefore($tomorrow, true);    // true (now < tomorrow, strict)

$now->isBetween($yesterday, $tomorrow); // true
$now->startDuringGivenDay(new DateTime('now')); // true
```

### French Formatting

```php
$date = new DateTime('2019-07-16 14:30:00');

$date->convertToString();           // "16 juillet 2019"
$date->convertToString(true);       // "mardi 16 juillet 2019"
```

---

## DateTime

**Namespace:** `UbeeDev\LibBundle\Entity\DateTime`

Extends `AbstractDateTime`. This is the standard datetime class used throughout the bundle and registered as a custom Doctrine DBAL type that replaces PHP's native `\DateTime`.

### Doctrine Registration

When you declare a column with `type: 'datetime'`, Doctrine automatically hydrates it as `UbeeDev\LibBundle\Entity\DateTime` instead of PHP's native `\DateTime`. This is configured via the custom type in `doctrine.yaml`:

```yaml
doctrine:
    dbal:
        types:
            datetime: UbeeDev\LibBundle\Doctrine\DBAL\Types\DateTimeType
```

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `__toString()` | `string` | Returns the date in ISO 8601 format (e.g. `"2019-07-16T14:30:00+02:00"`). |
| `jsonSerialize()` | `string` | Inherited from `AbstractDateTime`, same ISO 8601 format. |

### Examples

```php
use UbeeDev\LibBundle\Entity\DateTime;

$dt = new DateTime('2024-03-15 10:30:00');

echo $dt;                  // "2024-03-15T10:30:00+01:00"
json_encode($dt);          // "\"2024-03-15T10:30:00+01:00\""

// Use in entities -- just declare the column type
#[ORM\Column(type: 'datetime')]
private ?DateTime $publishedAt = null;
```

---

## Date

**Namespace:** `UbeeDev\LibBundle\Entity\Date`

Extends `AbstractDateTime`. Represents a date without time (time is always set to `00:00:00`). Registered as a custom Doctrine DBAL type.

### Doctrine Registration

```yaml
doctrine:
    dbal:
        types:
            date: UbeeDev\LibBundle\Doctrine\DBAL\Types\DateType
```

### Constructor

The constructor calls `setTime(0, 0, 0, 0)` to strip the time component:

```php
$date = new Date('2024-03-15 14:30:00');
echo $date; // "2024-03-15" (time is discarded)
```

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `__toString()` | `string` | Returns `'Y-m-d'` format (e.g. `"2024-03-15"`). |
| `jsonSerialize()` | `string` | Returns `'Y-m-d'` format. |

### Examples

```php
use UbeeDev\LibBundle\Entity\Date;

$date = new Date('2024-12-25');
echo $date;                // "2024-12-25"
json_encode($date);        // "\"2024-12-25\""

// Comparison methods inherited from AbstractDateTime
$christmas = new Date('2024-12-25');
$newYear = new Date('2025-01-01');
$christmas->isBefore($newYear); // true

// Use in entities
#[ORM\Column(type: 'date')]
private ?Date $birthDate = null;
```

---

## Media

**Namespace:** `UbeeDev\LibBundle\Entity\Media`

Abstract entity for file and image management. Extends `AbstractEntity`. Stores file metadata (filename, MIME type, size, privacy flag) along with optional image-specific fields (alt text, title, dimensions).

Because `Media` is a `MappedSuperclass`, you must create a concrete subclass in your application.

### Fields

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$filename` | `string` | -- | The stored filename (generated by `MediaManager`). |
| `$context` | `string` | -- | Storage context/subdirectory (e.g. `"avatars"`, `"documents"`). |
| `$contentType` | `string` | -- | MIME type (e.g. `"image/webp"`, `"application/pdf"`). |
| `$contentSize` | `int` | -- | File size in bytes. |
| `$private` | `bool` | `false` | Whether the file is stored in the private directory. |
| `$alt` | `?string` | `null` | Alt text for images (accessibility/SEO). |
| `$title` | `?string` | `null` | Title attribute for images. |
| `$width` | `?int` | `null` | Image width in pixels. |
| `$height` | `?int` | `null` | Image height in pixels. |

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `isImage()` | `bool` | Returns `true` if `contentType` starts with `"image/"`. |
| `hasDimensions()` | `bool` | Returns `true` if both `width` and `height` are set. |
| `getDimensionsString()` | `?string` | Returns `"800 x 600"` or `null` if dimensions are not set. |
| `getAltOrFallback()` | `string` | Returns `alt` text, or a human-readable version of the filename if `alt` is not set. |

### Creating a Concrete Media Entity

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use UbeeDev\LibBundle\Entity\Media as BaseMedia;

#[ORM\Entity]
class Media extends BaseMedia
{
    // Add any application-specific fields here
}
```

Register the class name as a parameter so `MediaManager` can instantiate it:

```yaml
# config/services.yaml
parameters:
    mediaClassName: App\Entity\Media
```

### Using Media with MediaManager

`MediaManager` handles file uploads, path resolution, WebP conversion, and PDF generation. It is available as a service and also exposed as a Twig global.

#### Uploading a File

```php
use UbeeDev\LibBundle\Service\MediaManager;
use Symfony\Component\HttpFoundation\File\File;

class ProductController
{
    public function __construct(private MediaManager $mediaManager) {}

    public function uploadImage(File $uploadedFile): Media
    {
        // Upload to the "products" context, public directory
        $media = $this->mediaManager->upload(
            uploadedFile: $uploadedFile,
            context: 'products',
            private: false,
        );

        // JPEG and PNG images are automatically converted to WebP
        // Image dimensions are automatically extracted

        $media->getFilename();       // e.g. "a1b2c3d4e5...uniqid.webp"
        $media->getContentType();    // "image/webp"
        $media->isImage();           // true
        $media->hasDimensions();     // true
        $media->getDimensionsString(); // "1200 x 800"

        return $media;
    }
}
```

#### Getting File Paths

```php
// Web-accessible path (public files only)
$webPath = $this->mediaManager->getWebPath($media);
// e.g. "/uploads/products/202403/a1b2c3...webp"

// Absolute filesystem path (public or private)
$absolutePath = $this->mediaManager->getRelativePath($media);
// e.g. "/var/www/app/public/uploads/products/202403/a1b2c3...webp"
```

#### Generating a PDF from HTML

```php
$media = $this->mediaManager->createPdfFromHtml(
    htmlContent: '<h1>Invoice #123</h1><p>Total: 99.00 EUR</p>',
    context: 'invoices',
    private: true,
);

$media->getContentType(); // "application/pdf"
$media->isPrivate();      // true
```

#### Deleting a Media

```php
// Delete file from disk only
$this->mediaManager->deleteAsset($media);

// Delete entity from database (does not remove the file)
$this->mediaManager->delete($media);
```

#### Using in Twig

`MediaManager` is registered as a global Twig variable:

```twig
<img
    src="{{ mediaManager.getWebPath(product.image) }}"
    alt="{{ product.image.altOrFallback }}"
    {% if product.image.hasDimensions %}
        width="{{ product.image.width }}"
        height="{{ product.image.height }}"
    {% endif %}
>
```

#### Image Metadata

```php
$media->setAlt('A red bicycle parked by the lake');
$media->setTitle('Product photo - Red Bicycle');

$media->getAltOrFallback();
// Returns "A red bicycle parked by the lake"

// When alt is null, the fallback is derived from the filename:
$media->setAlt(null);
$media->getAltOrFallback();
// e.g. "A1b2c3d4e5f6 uniqid" (humanized from filename)
```

---

## Address

**Namespace:** `UbeeDev\LibBundle\Entity\Address`

Abstract embeddable entity for postal addresses with French formatting. Extends `AbstractEntity` and implements `\JsonSerializable`. Since it is a `MappedSuperclass`, create a concrete subclass in your application.

### Fields

| Property | Type | Default | Constraints | Description |
|----------|------|---------|-------------|-------------|
| `$streetNumber` | `?string` | `null` | Max 10 characters | Street number (e.g. `"12"`). |
| `$street` | `string` | -- | Required, max 255 characters | Street name (e.g. `"Rue de la Paix"`). |
| `$complement` | `?string` | `null` | -- | Additional address information (e.g. `"Bldg A, Floor 3"`). |
| `$city` | `string` | -- | Required, max 32 characters | City name. |
| `$country` | `string` | `'France'` | Required, max 50 characters | Country name. |
| `$postalCode` | `string` | -- | Required, max 32 characters | Postal code. |

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `getAddressLine1()` | `string` | Returns `"12 Rue de la Paix"`. |
| `__toString()` | `string` | Full address: `"12 Rue de la Paix, 75001 Paris, France"`. |
| `jsonSerialize()` | `array` | Returns all fields as an associative array. Returns all-null values if the entity has no ID. |

### Examples

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use UbeeDev\LibBundle\Entity\Address as BaseAddress;

#[ORM\Entity]
class Address extends BaseAddress
{
}
```

```php
$address = new Address();
$address->setStreetNumber('12');
$address->setStreet('Rue de la Paix');
$address->setPostalCode('75002');
$address->setCity('Paris');

echo $address->getAddressLine1(); // "12 Rue de la Paix"
echo $address;                    // "12 Rue de la Paix, 75002 Paris, France"

// With complement
$address->setComplement('Apt 4B');
echo $address; // "12 Rue de la Paix, Apt 4B, 75002 Paris, France"

// JSON serialization
json_encode($address);
// {"streetNumber":"12","street":"Rue de la Paix","complement":"Apt 4B","city":"Paris","country":"France","postalCode":"75002"}
```

---

## FooterLink

**Namespace:** `UbeeDev\LibBundle\Entity\FooterLink`

Abstract entity for website footer links. Extends `AbstractEntity`. Since it is a `MappedSuperclass`, create a concrete subclass in your application.

### Fields

| Property | Type | Default | Constraints | Description |
|----------|------|---------|-------------|-------------|
| `$label` | `string` | -- | Required, max 30 characters | Link display text. |
| `$url` | `string` | -- | Required | Link URL. |
| `$position` | `int` | `0` | Required | Display order. |
| `$active` | `bool` | `false` | -- | Whether the link is visible. |

### Methods

| Method | Return | Description |
|--------|--------|-------------|
| `getLabel()` | `string` | Returns the link label. |
| `setLabel(string)` | `self` | Sets the link label. |
| `getUrl()` | `string` | Returns the link URL. |
| `setUrl(string)` | `self` | Sets the link URL. |
| `getPosition()` | `int` | Returns the display position. |
| `setPosition(int)` | `self` | Sets the display position. |
| `isActive()` | `bool` | Returns the active status. |
| `setActive(bool)` | `self` | Sets the active status. |

### Examples

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use UbeeDev\LibBundle\Entity\FooterLink as BaseFooterLink;

#[ORM\Entity]
class FooterLink extends BaseFooterLink
{
}
```

```php
$link = new FooterLink();
$link->setLabel('Privacy Policy');
$link->setUrl('/privacy');
$link->setPosition(1);
$link->setActive(true);

// Query active links ordered by position
$links = $repository->findBy(['active' => true], ['position' => 'ASC']);
```

---

## PostDeployExecution

**Namespace:** `UbeeDev\LibBundle\Entity\PostDeployExecution`

A concrete entity (not a `MappedSuperclass`) that tracks one-time post-deploy script executions. It does **not** extend `AbstractEntity` -- it has its own `$id` field and no automatic timestamps. The `name` field has a unique constraint to prevent duplicate executions.

### Fields

| Property | Type | Constraints | Description |
|----------|------|-------------|-------------|
| `$id` | `?int` | Auto-generated | Primary key. |
| `$name` | `?string` | Required, unique | Script identifier (e.g. `"migrate_user_roles_v2"`). |
| `$executedAt` | `?DateTime` | Required | When the script was executed. |
| `$executionTime` | `?int` | Required | Execution duration in seconds. |

### Examples

```php
use UbeeDev\LibBundle\Entity\PostDeployExecution;
use UbeeDev\LibBundle\Entity\DateTime;

$execution = new PostDeployExecution();
$execution->setName('migrate_user_roles_v2');
$execution->setExecutedAt(new DateTime('now'));
$execution->setExecutionTime(45); // 45 seconds

$entityManager->persist($execution);
$entityManager->flush();

// Check if a script has already been executed
$existing = $repository->findOneBy(['name' => 'migrate_user_roles_v2']);
if ($existing !== null) {
    // Script already executed, skip
}
```
