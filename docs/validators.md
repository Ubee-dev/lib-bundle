# Validators

This bundle provides a set of custom Symfony validation constraints that can be used as PHP 8 attributes on your entity properties and classes.

All constraints live under the `UbeeDev\LibBundle\Validator\Constraints` namespace.

---

## File

Validates a `Media` entity for file size, MIME type, and extension.

**Options:**

| Option              | Type                | Default                                    | Description                          |
|---------------------|---------------------|--------------------------------------------|--------------------------------------|
| `maxSize`           | `string\|int\|null` | `null`                                     | Max file size (e.g. `'5M'`, `'500K'`, `1048576`) |
| `binaryFormat`      | `?bool`             | `null`                                     | Whether to use binary units (KiB/MiB) |
| `mimeTypes`         | `array`             | `[]`                                       | Array of allowed MIME types          |
| `extensions`        | `array`             | `[]`                                       | Array of allowed file extensions     |
| `maxSizeMessage`    | `string`            | `'ubee_dev_lib.media.max_size.invalid'`    | Error message for size violations    |
| `mimeTypesMessage`  | `string`            | `'ubee_dev_lib.media.mime_type.invalid'`   | Error message for MIME type violations |
| `extensionsMessage` | `string`            | `'ubee_dev_lib.media.extension.invalid'`   | Error message for extension violations |

The `maxSize` option accepts human-readable suffixes: `K` (kilobytes), `Ki` (kibibytes), `M` (megabytes), `Mi` (mebibytes), `G` (gigabytes), `Gi` (gibibytes), or a plain integer in bytes.

**Usage:**

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;

class Product
{
    #[UbeeAssert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        extensions: ['jpg', 'jpeg', 'png', 'webp'],
    )]
    private ?Media $photo = null;
}
```

With custom error messages:

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;

class Document
{
    #[UbeeAssert\File(
        maxSize: '10M',
        mimeTypes: ['application/pdf'],
        extensions: ['pdf'],
        maxSizeMessage: 'The file is too large. Maximum allowed size is {{ limit }} {{ suffix }}.',
        mimeTypesMessage: 'The file type {{ type }} is not allowed. Allowed types: {{ types }}.',
        extensionsMessage: 'The extension {{ extension }} is not allowed. Allowed: {{ extensions }}.',
    )]
    private ?Media $attachment = null;
}
```

---

## AllowedContentType

Validates that a `Media` entity's content type is in the allowed list. Uses regex matching against the MIME type.

**Options:**

| Option             | Type     | Default                                 | Description                        |
|--------------------|----------|-----------------------------------------|------------------------------------|
| `allowedMimeTypes` | `array`  | `[]`                                    | Array of allowed MIME type patterns |
| `message`          | `string` | `'media.content_type.invalid_mime_type'`| Error message                      |

**Usage:**

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;

class Article
{
    #[UbeeAssert\AllowedContentType(
        allowedMimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
    )]
    private ?Media $coverImage = null;
}
```

You can also use regex patterns for MIME types:

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;

class Gallery
{
    #[UbeeAssert\AllowedContentType(
        allowedMimeTypes: ['image/.*'],
        message: 'Only image files are accepted. Allowed types: {{ mime_type }}.',
    )]
    private ?Media $image = null;
}
```

---

## PhoneNumber

Class-level constraint that validates a phone number combined with a country calling code. The entity must implement `UbeeDev\LibBundle\Model\PhoneNumberInterface`, which requires `getPhoneNumber()` and `getCountryCallingCode()` methods.

Uses the `libphonenumber` library for validation.

**Options:**

| Option       | Type     | Default                                | Description                              |
|--------------|----------|----------------------------------------|------------------------------------------|
| `errorPath`  | `string` | `'phoneNumber'`                        | The property path where the error appears |
| `message`    | `?string`| `null` (falls back to `'ubee_dev_lib.phone_number.invalid'`) | Custom error message |
| `type`       | `string` | `PhoneNumber::ANY`                     | Phone number type to validate against    |

**Targets:** `CLASS`, `PROPERTY`

**Available phone number types:**

- `PhoneNumber::ANY` (default)
- `PhoneNumber::FIXED_LINE`
- `PhoneNumber::MOBILE`
- `PhoneNumber::PAGER`
- `PhoneNumber::PERSONAL_NUMBER`
- `PhoneNumber::PREMIUM_RATE`
- `PhoneNumber::SHARED_COST`
- `PhoneNumber::TOLL_FREE`
- `PhoneNumber::UAN`
- `PhoneNumber::VOIP`
- `PhoneNumber::VOICEMAIL`

**Usage (class-level):**

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;
use UbeeDev\LibBundle\Model\PhoneNumberInterface;

#[UbeeAssert\PhoneNumber(errorPath: 'phoneNumber')]
class Contact implements PhoneNumberInterface
{
    private ?int $countryCallingCode = null;

    private ?string $phoneNumber = null;

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getCountryCallingCode(): ?int
    {
        return $this->countryCallingCode;
    }

    public function setCountryCallingCode(?int $countryCallingCode): static
    {
        $this->countryCallingCode = $countryCallingCode;
        return $this;
    }
}
```

---

## DateStartDuringGivenDay

Validates that a date/time value falls within the same day as another property on the entity.

**Options:**

| Option            | Type     | Default                                               | Description                                      |
|-------------------|----------|-------------------------------------------------------|--------------------------------------------------|
| `message`         | `string` | `'UbeeDev_interview.date.start_during_given_day'`     | Error message                                    |
| `mode`            | `string` | `'stric'`                                             | Comparison mode                                  |
| `propertyPath`    | `string` | *(required)*                                          | The property name to compare against (must have a getter) |
| `includeMidnight` | `bool`   | `false`                                               | Whether midnight (00:00:00) is considered valid   |

The validator reads the comparison date by calling `get{PropertyPath}()` on the root entity.

**Usage:**

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;

class Appointment
{
    private ?\DateTimeInterface $day = null;

    #[UbeeAssert\DateStartDuringGivenDay(
        propertyPath: 'day',
        includeMidnight: true,
        message: 'The start time must be during the selected day.',
    )]
    private ?\DateTimeInterface $startTime = null;

    public function getDay(): ?\DateTimeInterface
    {
        return $this->day;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }
}
```

---

## MoneyGreaterThan

Extends Symfony's `AbstractComparison` constraint. Validates that a `Money\Money` object is greater than a given value. The comparison is performed against `Money::EUR()` using the amount in minor units (cents).

**Options:**

Inherits all options from Symfony's `AbstractComparison` (`value`, `groups`, `payload`, etc.).

| Option    | Type     | Default                                   | Description        |
|-----------|----------|-------------------------------------------|--------------------|
| `value`   | `mixed`  | *(required)*                              | The value to compare against (in minor units) |
| `message` | `string` | `'ubee_dev_lib.money.greather_than'`      | Error message      |

**Usage:**

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;
use Money\Money;

class Order
{
    #[UbeeAssert\MoneyGreaterThan(value: 0)]
    private ?Money $total = null;

    public function getTotal(): ?Money
    {
        return $this->total;
    }
}
```

The value `0` means the `Money` object must represent a positive amount (greater than EUR 0.00).

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;
use Money\Money;

class Invoice
{
    /** Amount must be greater than 10.00 EUR (1000 minor units) */
    #[UbeeAssert\MoneyGreaterThan(value: 1000)]
    private ?Money $amount = null;
}
```

---

## ConstraintVideoProviderUrl

Validates that a URL belongs to one or more allowed video providers (YouTube, Vimeo, Facebook).

**Options:**

| Option      | Type     | Default                            | Description                                  |
|-------------|----------|------------------------------------|----------------------------------------------|
| `providers` | `array`  | `['youtube', 'vimeo', 'facebook']` | Array of allowed provider names              |
| `message`   | `string` | `'The URL "{{ url }}" is not a valid video URL for the allowed providers.'` | Error message |

**Supported providers:** `youtube`, `vimeo`, `facebook`

**Usage:**

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;

class VideoPost
{
    #[UbeeAssert\ConstraintVideoProviderUrl(
        providers: ['youtube', 'vimeo'],
    )]
    private ?string $videoUrl = null;
}
```

Allow all three providers:

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;

class MediaContent
{
    #[UbeeAssert\ConstraintVideoProviderUrl(
        providers: ['youtube', 'vimeo', 'facebook'],
        message: 'Please provide a valid video URL from YouTube, Vimeo, or Facebook.',
    )]
    private ?string $videoUrl = null;
}
```

---

## ConstraintYoutubeUrl

Validates that a string is a valid YouTube URL.

**Options:**

| Option                  | Type     | Default                                             | Description       |
|-------------------------|----------|-----------------------------------------------------|-------------------|
| `validationFailMessage` | `string` | `'This value is not a valid Youtube URL.'`          | Error message     |

**Usage:**

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;

class Tutorial
{
    #[UbeeAssert\ConstraintYoutubeUrl]
    private ?string $youtubeUrl = null;
}
```

With a custom message:

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;

class Lesson
{
    #[UbeeAssert\ConstraintYoutubeUrl(validationFailMessage: 'Please enter a valid YouTube video URL.')]
    private ?string $videoLink = null;
}
```

---

## ConstraintVimeoUrl

Validates that a string is a valid Vimeo URL.

**Options:**

| Option                  | Type     | Default                                          | Description       |
|-------------------------|----------|--------------------------------------------------|-------------------|
| `validationFailMessage` | `string` | `'This is not a valid Vimeo URL.'`               | Error message     |

**Usage:**

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;

class Presentation
{
    #[UbeeAssert\ConstraintVimeoUrl]
    private ?string $vimeoUrl = null;
}
```

---

## ConstraintFacebookEmbedUrl

Validates that a string is a valid Facebook embed URL.

**Options:**

| Option                  | Type     | Default                                            | Description       |
|-------------------------|----------|----------------------------------------------------|-------------------|
| `validationFailMessage` | `string` | `'This is not a valid Facebook URL.'`              | Error message     |

**Usage:**

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;

class SocialPost
{
    #[UbeeAssert\ConstraintFacebookEmbedUrl]
    private ?string $facebookVideoUrl = null;
}
```

---

## Validator Service

`UbeeDev\LibBundle\Validator\Validator` is a fluent validation helper that wraps Symfony's `ValidatorInterface`. It collects violations from one or more entities and throws an `InvalidArgumentException` containing all errors at once.

**Methods:**

| Method                                                | Return | Description                                                  |
|-------------------------------------------------------|--------|--------------------------------------------------------------|
| `addValidation(object $entity, ?string $prefix = null)` | `self` | Adds an entity to validate, with an optional key prefix for errors |
| `validate()`                                           | `void` | Runs validation on all added entities; throws `InvalidArgumentException` if any errors |
| `setMessage(string $message)`                          | `self` | Sets a custom message on the thrown exception                |

When `validate()` is called, it iterates over every entity added via `addValidation()`, collects all constraint violations, and if any are found, throws `UbeeDev\LibBundle\Exception\InvalidArgumentException`. The exception provides a `getErrors()` method returning an associative array of property paths to error messages.

**Usage:**

```php
use UbeeDev\LibBundle\Validator\Validator;
use UbeeDev\LibBundle\Exception\InvalidArgumentException;

class ProductService
{
    public function __construct(
        private Validator $validator,
    ) {}

    public function createProduct(Product $product): void
    {
        try {
            $this->validator
                ->setMessage('Product validation failed')
                ->addValidation($product)
                ->validate();
        } catch (InvalidArgumentException $e) {
            // $e->getMessage() => 'Product validation failed'
            // $e->getErrors()  => ['name' => 'This value should not be blank.', ...]
        }
    }
}
```

**Validating multiple entities with prefixes:**

```php
use UbeeDev\LibBundle\Validator\Validator;
use UbeeDev\LibBundle\Exception\InvalidArgumentException;

class OrderService
{
    public function __construct(
        private Validator $validator,
    ) {}

    public function createOrder(Order $order, Address $billingAddress, Address $shippingAddress): void
    {
        try {
            $this->validator
                ->setMessage('Order validation failed')
                ->addValidation($order)
                ->addValidation($billingAddress, 'billing')
                ->addValidation($shippingAddress, 'shipping')
                ->validate();
        } catch (InvalidArgumentException $e) {
            // $e->getErrors() =>
            // [
            //     'total'   => 'The total must be greater than 0.',
            //     'billing' => ['street' => 'This value should not be blank.'],
            //     'shipping' => ['zipCode' => 'This value is too short.'],
            // ]
        }
    }
}
```

**Full entity example combining multiple constraints:**

```php
use UbeeDev\LibBundle\Validator\Constraints as UbeeAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Money\Money;

#[UbeeAssert\PhoneNumber(errorPath: 'phoneNumber')]
class Shop implements PhoneNumberInterface
{
    #[Assert\NotBlank]
    private ?string $name = null;

    #[UbeeAssert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png'],
        extensions: ['jpg', 'jpeg', 'png'],
    )]
    #[UbeeAssert\AllowedContentType(
        allowedMimeTypes: ['image/jpeg', 'image/png'],
    )]
    private ?Media $logo = null;

    #[UbeeAssert\MoneyGreaterThan(value: 0)]
    private ?Money $minimumOrderAmount = null;

    #[UbeeAssert\ConstraintVideoProviderUrl(
        providers: ['youtube', 'vimeo'],
    )]
    private ?string $promoVideoUrl = null;

    private ?int $countryCallingCode = null;
    private ?string $phoneNumber = null;

    // ... getters, setters, PhoneNumberInterface methods
}
```
