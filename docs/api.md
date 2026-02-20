# API Tools

This document covers the API request handling tools provided by the bundle: parameter sanitization, typed expectations, pagination, and response formatting.

The philosophy is declarative, type-safe parameter validation that replaces ad-hoc `$request->get()` calls with structured expectations. By defining what you expect up front, the sanitizer catches invalid input early and returns consistent error responses -- eliminating scattered validation logic from your controllers.

## Table of Contents

- [ApiManager](#apimanager)
  - [sanitizeParameters](#sanitizeparameters)
  - [sanitizePostParameters](#sanitizepostparameters)
  - [sanitizeQueryParameters](#sanitizequeryparameters)
  - [sanitizeFileParameters](#sanitizefileparameters)
  - [sanitizeHeaderParameters](#sanitizeheaderparameters)
  - [formatOutput](#formatoutput)
  - [paginatedApiResponse](#paginatedapiresponse)
  - [convertLocalDateTimesToDefaultTimezone](#convertlocaldatetimestodefaulttimezone)
  - [jsonSerializeData](#jsonserializedata)
  - [jsonWithTimezone](#jsonwithtimezone)
  - [checkHeadersParameters](#checkheadersparameters)
- [ParameterType](#parametertype)
- [ExpectationBuilder & Expect](#expectationbuilder--expect)
  - [StringExpectation](#stringexpectation)
  - [NumericExpectation](#numericexpectation)
  - [ArrayExpectation](#arrayexpectation)
  - [EntityExpectation](#entityexpectation)
  - [FileExpectation](#fileexpectation)
  - [EnumExpectation](#enumexpectation)
  - [BasicExpectation](#basicexpectation)
- [Pagination](#pagination)
  - [Paginator](#paginator)
  - [PaginatorFactory](#paginatorfactory)
  - [PaginatedResult](#paginatedresult)
- [Error Handling](#error-handling)
  - [InvalidArgumentException](#invalidargumentexception)
  - [FileValidationException](#filevalidationexception)

---

## ApiManager

Validates, sanitizes, and transforms API request parameters. Delegates to the internal `OptionsResolver` for type coercion, required-field enforcement, allowed-value checks, and strict-mode filtering. Also provides paginated JSON responses and timezone-aware serialization.

**Class:** `UbeeDev\LibBundle\Service\ApiManager`

### sanitizeParameters

Sanitizes a raw associative array against a set of typed expectations. This is the general-purpose method -- use it when you already have the data as an array.

```php
use UbeeDev\LibBundle\Builder\Expect;
use UbeeDev\LibBundle\Config\ParameterType;

/** @var \UbeeDev\LibBundle\Service\ApiManager $api */

$data = ['name' => 'Jane', 'age' => '28'];

$sanitized = $api->sanitizeParameters($data, [
    'name' => Expect::string(),
    'age'  => Expect::int(),
]);

// $sanitized = ['name' => 'Jane', 'age' => 28]
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$data` | `array` | -- | Raw input data to sanitize. |
| `$sanitizingExpectations` | `array` | -- | Map of parameter names to expectations (see [ExpectationBuilder](#expectationbuilder--expect)). |
| `$allowedValues` | `array` | `[]` | Map of parameter names to arrays of allowed values. Throws if the sanitized value is not in the list. |
| `$defaultValues` | `array` | `[]` | Map of parameter names to default values used when the input is empty or null. |
| `$strictMode` | `bool` | `true` | When `true`, extra keys not defined in expectations are stripped from the output. |

---

### sanitizePostParameters

Reads parameters from the request body (`$request->request`) and sanitizes them. Signature and behavior are identical to `sanitizeParameters`, except the data source is the `Request` object.

```php
// POST /api/users  { "email": "jane@example.com", "role": "admin" }

$params = $api->sanitizePostParameters($request, [
    'email' => Expect::email(),
    'role'  => Expect::enum(UserRole::class),
]);

// $params = ['email' => Email('jane@example.com'), 'role' => UserRole::ADMIN]
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$request` | `Request` | -- | The Symfony HTTP request. |
| `$sanitizingExpectations` | `array` | -- | Map of parameter names to expectations. |
| `$allowedValues` | `array` | `[]` | Map of parameter names to arrays of allowed values. |
| `$defaultValues` | `array` | `[]` | Map of parameter names to default values. |
| `$strictMode` | `bool` | `true` | Strip extra keys not defined in expectations. |

---

### sanitizeQueryParameters

Reads parameters from the query string (`$request->query`) and sanitizes them.

```php
// GET /api/products?category=electronics&page=2&sort=price

$params = $api->sanitizeQueryParameters($request, [
    'category' => Expect::string(),
    'sort'     => Expect::string()->optional(),
], allowedValues: [
    'sort' => ['price', 'name', 'date'],
], defaultValues: [
    'sort' => 'date',
]);

// $params = ['category' => 'electronics', 'sort' => 'price']
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$request` | `Request` | -- | The Symfony HTTP request. |
| `$sanitizingExpectations` | `array` | -- | Map of parameter names to expectations. |
| `$allowedValues` | `array` | `[]` | Map of parameter names to arrays of allowed values. |
| `$defaultValues` | `array` | `[]` | Map of parameter names to default values. |
| `$strictMode` | `bool` | `true` | Strip extra keys not defined in expectations. |

---

### sanitizeFileParameters

Reads uploaded files from `$request->files` and sanitizes them.

```php
// POST /api/upload  (multipart form with file field "document")

$params = $api->sanitizeFileParameters($request, [
    'document' => Expect::file()->extensions(['.pdf', '.docx']),
]);

// $params = ['document' => UploadedFile instance]
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$request` | `Request` | -- | The Symfony HTTP request. |
| `$sanitizingExpectations` | `array` | -- | Map of parameter names to expectations. |
| `$allowedValues` | `array` | `[]` | Map of parameter names to arrays of allowed values. |
| `$defaultValues` | `array` | `[]` | Map of parameter names to default values. |
| `$strictMode` | `bool` | `true` | Strip extra keys not defined in expectations. |

---

### sanitizeHeaderParameters

Reads HTTP headers from `$request->headers` and sanitizes them. Each header value is unwrapped from its array container (the first element is used).

```php
$params = $api->sanitizeHeaderParameters($request, [
    'x-api-version' => Expect::string()->optional(),
    'x-client-id'   => Expect::string(),
], defaultValues: [
    'x-api-version' => 'v1',
]);

// $params = ['x-api-version' => 'v2', 'x-client-id' => 'mobile-app']
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$request` | `Request` | -- | The Symfony HTTP request. |
| `$sanitizingExpectations` | `array` | -- | Map of parameter names to expectations. |
| `$allowedValues` | `array` | `[]` | Map of parameter names to arrays of allowed values. |
| `$defaultValues` | `array` | `[]` | Map of parameter names to default values. |
| `$strictMode` | `bool` | `true` | Strip extra keys not defined in expectations. |

---

### formatOutput

Serializes output values according to their declared types. Converts `Date`, `DateTime`, `Money`, `int`, and entity objects to their primitive representations.

```php
use UbeeDev\LibBundle\Config\ParameterType;

$output = [
    'start' => new DateTime('2026-03-15 10:00:00'),
    'price' => Money::EUR(4999),
    'user'  => $userEntity,
];

$formatted = $api->formatOutput($output, [
    'start' => ParameterType::DATETIME,
    'price' => ParameterType::MONEY,
    'user'  => ParameterType::ENTITY,
]);

// $formatted = [
//     'start' => '2026-03-15T10:00:00+00:00',  (ISO 8601)
//     'price' => 4999,                           (integer cents)
//     'user'  => 42,                             (entity ID)
// ]
```

**Type conversion rules:**

| Type | Conversion |
|------|------------|
| `date` | `DateTime::format('Y-m-d')` |
| `datetime` | `DateTime::format('c')` (ISO 8601) |
| `money` | `(int) Money::getAmount()` (cents) |
| `int` | `(int)` cast |
| `entity` | Calls `->getId()` on the entity object |

---

### paginatedApiResponse

Executes a paginated Doctrine query and returns a `JsonResponse` with pagination metadata in the response headers.

```php
$qb = $userRepository->createQueryBuilder('u')
    ->where('u.active = true');

$response = $api->paginatedApiResponse($qb, $request, UserDto::class);

// Response body: JSON array of UserDto objects
// Response headers:
//   nbTotalResults: 142
//   nbDisplayedResults: 40   (cumulative through current page)
//   pageSize: 20
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$queryBuilder` | `QueryBuilder` | -- | Doctrine query builder to paginate. |
| `$request` | `Request` | -- | The HTTP request (reads `page` and `per` query parameters). |
| `$dtoClass` | `?string` | `null` | Optional DTO class. Each result is passed to `new $dtoClass($entity, ...$params)`. |
| `$params` | `array` | `[]` | Extra parameters passed to the DTO constructor after the entity. |
| `$timezone` | `?string` | `null` | When set, all `DateTime` values in the response are converted to this timezone. |
| `$orderBy` | `array` | `[]` | Map of `field => direction` to append `ORDER BY` clauses to the query. |

**Response headers:**

| Header | Description |
|--------|-------------|
| `nbTotalResults` | Total number of matching records. |
| `nbDisplayedResults` | Cumulative number of results through the current page. |
| `pageSize` | Number of items per page. |

With ordering and timezone:

```php
$response = $api->paginatedApiResponse(
    $qb,
    $request,
    EventDto::class,
    [$mediaManager],
    timezone: 'Europe/Paris',
    orderBy: ['e.startDate' => 'ASC', 'e.name' => 'ASC']
);
```

---

### convertLocalDateTimesToDefaultTimezone

Converts an array of date strings from a local timezone to the application default timezone, optionally applying specific times.

```php
$params = [
    'startDate' => '2026-03-15',
    'endDate'   => '2026-03-20',
];
$times = [
    'startDate' => '09:00:00',
    'endDate'   => '18:00:00',
];

$converted = $api->convertLocalDateTimesToDefaultTimezone($params, $times, 'America/New_York');
// Each date is now a DateTime object in the default timezone (Europe/Paris)
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Map of parameter names to date strings. |
| `$times` | `array` | Map of parameter names to time strings (`H:i:s`). Falls back to the time from the date string if not provided. |
| `$timeZone` | `string` | The source timezone identifier (e.g. `'America/New_York'`). |

---

### jsonSerializeData

Serializes an iterable of entities implementing `JsonSerializable` into an array of plain arrays.

```php
$users = $userRepository->findAll();
$data = $api->jsonSerializeData($users);
// [['id' => 1, 'name' => 'Jane'], ['id' => 2, 'name' => 'John'], ...]

// With extra parameters passed to jsonSerialize()
$data = $api->jsonSerializeData($users, ['includeEmail' => true]);
```

---

### jsonWithTimezone

Returns a `JsonResponse` where all `DateTime` instances are converted to the given timezone. Works with arrays and objects implementing `\JsonSerializable`.

```php
$response = $api->jsonWithTimezone($event->jsonSerialize(), 'Asia/Tokyo');
```

---

### checkHeadersParameters

Validates an API token against the configured `APP_TOKEN`. Throws a 401 `HttpException` if the token does not match.

```php
$token = $request->headers->get('x-api-token');
$api->checkHeadersParameters($token);
// Throws HttpException(401, 'Wrong Token') if the token is invalid
```

---

## ParameterType

A PHP `BackedEnum` listing all supported parameter types. Each case maps to a string value used internally by the sanitizer.

**Class:** `UbeeDev\LibBundle\Config\ParameterType`

| Case | Value | PHP type after sanitization |
|------|-------|-----------------------------|
| `STRING` | `'string'` | `string` |
| `INT` | `'int'` | `int` |
| `FLOAT` | `'float'` | `float` |
| `BOOL` | `'bool'` | `bool` |
| `ARRAY` | `'array'` | `array` |
| `DATE` | `'date'` | `UbeeDev\LibBundle\Entity\Date` |
| `DATETIME` | `'datetime'` | `UbeeDev\LibBundle\Entity\DateTime` |
| `MONEY` | `'money'` | `Money\Money` |
| `ENUM` | `'enum'` | `BackedEnum` |
| `CUSTOM_ENUM` | `'customEnum'` | `UbeeDev\LibBundle\Config\CustomEnumInterface` |
| `EMAIL` | `'email'` | `UbeeDev\LibBundle\Model\Type\Email` |
| `NAME` | `'name'` | `UbeeDev\LibBundle\Model\Type\Name` |
| `URL` | `'url'` | `UbeeDev\LibBundle\Model\Type\Url` |
| `PHONE_NUMBER` | `'phoneNumber'` | `UbeeDev\LibBundle\Model\Type\PhoneNumber` |
| `ENTITY` | `'entity'` | `object` (Doctrine entity) |
| `FILE` | `'file'` | `Symfony\Component\HttpFoundation\File\UploadedFile` |

**Utility methods:**

```php
// Get all type values as a flat array
ParameterType::values();
// ['string', 'int', 'float', 'bool', 'array', 'date', 'datetime', ...]

// Check if a type requires a class parameter
ParameterType::ENUM->requiresClass();       // true
ParameterType::ENTITY->requiresClass();     // true
ParameterType::STRING->requiresClass();     // false

// Get the expected PHP type after sanitization
ParameterType::MONEY->getExpectedPhpType(); // 'Money\Money'

// Check if a type supports HTML stripping
ParameterType::STRING->supportsHtmlStripping(); // true
ParameterType::INT->supportsHtmlStripping();    // false
```

---

## ExpectationBuilder & Expect

A fluent builder for constructing typed parameter expectations. `Expect` is an alias for `ExpectationBuilder` -- they are interchangeable.

**Class:** `UbeeDev\LibBundle\Builder\ExpectationBuilder`
**Alias:** `UbeeDev\LibBundle\Builder\Expect`

Each factory method returns a specialized expectation subclass with type-specific options. All expectations support the common methods `required()`, `optional()`, and `toArray()`.

**Common methods on all expectations:**

| Method | Description |
|--------|-------------|
| `required(bool $required = true)` | Mark the parameter as required (default). Throws a validation error if the value is empty or null. |
| `optional()` | Shorthand for `required(false)`. The parameter may be absent or null. |
| `toArray()` | Converts the expectation to its internal array representation. Called automatically by the sanitizer. |

**Factory methods overview:**

| Factory method | Returns | Parameter type |
|----------------|---------|----------------|
| `Expect::string()` | `StringExpectation` | `STRING` |
| `Expect::int()` | `NumericExpectation` | `INT` |
| `Expect::float()` | `NumericExpectation` | `FLOAT` |
| `Expect::bool()` | `BasicExpectation` | `BOOL` |
| `Expect::array()` | `ArrayExpectation` | `ARRAY` |
| `Expect::date()` | `BasicExpectation` | `DATE` |
| `Expect::datetime()` | `BasicExpectation` | `DATETIME` |
| `Expect::money()` | `BasicExpectation` | `MONEY` |
| `Expect::email()` | `StringExpectation` | `EMAIL` |
| `Expect::name()` | `StringExpectation` | `NAME` |
| `Expect::url()` | `StringExpectation` | `URL` |
| `Expect::phoneNumber()` | `StringExpectation` | `PHONE_NUMBER` |
| `Expect::enum(string $enumClass)` | `EnumExpectation` | `ENUM` |
| `Expect::customEnum(string $enumClass)` | `EnumExpectation` | `CUSTOM_ENUM` |
| `Expect::entity(string $entityClass)` | `EntityExpectation` | `ENTITY` |
| `Expect::file()` | `FileExpectation` | `FILE` |

**Complete example:**

```php
use UbeeDev\LibBundle\Builder\Expect;

$params = $api->sanitizePostParameters($request, [
    'title'       => Expect::string(),
    'description' => Expect::string()->optional()->keepHtml(),
    'price'       => Expect::int()->range(0, 100000),
    'category'    => Expect::enum(Category::class),
    'author'      => Expect::entity(User::class)->by('slug'),
    'tags'        => Expect::array()->optional()->items([
        'name'  => Expect::string(),
        'color' => Expect::string()->optional(),
    ]),
    'cover'       => Expect::file()->extensions(['.jpg', '.png', '.webp']),
]);
```

---

### StringExpectation

Returned by `Expect::string()`, `Expect::email()`, `Expect::name()`, `Expect::url()`, and `Expect::phoneNumber()`.

**Class:** `UbeeDev\LibBundle\Builder\StringExpectation`

By default, HTML tags are stripped from string values during sanitization. Use `keepHtml()` to preserve them.

| Method | Description |
|--------|-------------|
| `stripHtml(bool $stripHtml = true)` | Explicitly enable HTML stripping (this is the default). |
| `keepHtml()` | Disable HTML stripping. The raw value is kept as-is. Shorthand for `stripHtml(false)`. |

```php
// HTML tags are stripped by default
$params = $api->sanitizeParameters(
    ['bio' => '<p>Hello <b>world</b></p>'],
    ['bio' => Expect::string()]
);
// $params = ['bio' => 'Hello world']

// Keep HTML content intact
$params = $api->sanitizeParameters(
    ['bio' => '<p>Hello <b>world</b></p>'],
    ['bio' => Expect::string()->keepHtml()]
);
// $params = ['bio' => '<p>Hello <b>world</b></p>']
```

---

### NumericExpectation

Returned by `Expect::int()` and `Expect::float()`.

**Class:** `UbeeDev\LibBundle\Builder\NumericExpectation`

| Method | Description |
|--------|-------------|
| `min(int\|float $min)` | Set a minimum allowed value. |
| `max(int\|float $max)` | Set a maximum allowed value. |
| `range(int\|float $min, int\|float $max)` | Set both minimum and maximum. Shorthand for `min($min)->max($max)`. |

```php
$params = $api->sanitizePostParameters($request, [
    'quantity'    => Expect::int()->min(1),
    'discount'    => Expect::float()->range(0, 100),
    'temperature' => Expect::float()->min(-50)->max(60)->optional(),
]);
```

---

### ArrayExpectation

Returned by `Expect::array()`.

**Class:** `UbeeDev\LibBundle\Builder\ArrayExpectation`

| Method | Description |
|--------|-------------|
| `items(array $itemsExpectation)` | Define the expected structure of array items. Each item in the array is validated against this nested expectation map. |

Supports both lists of objects and single associative objects. The nested expectations follow the same rules as top-level expectations.

```php
// List of structured objects
$params = $api->sanitizePostParameters($request, [
    'attendees' => Expect::array()->items([
        'name'  => Expect::name(),
        'email' => Expect::email(),
        'role'  => Expect::enum(AttendeeRole::class)->optional(),
    ]),
]);
// Input:  {"attendees": [{"name": "Jane", "email": "jane@example.com"}, ...]}
// Output: ['attendees' => [['name' => Name('Jane'), 'email' => Email('jane@example.com')], ...]]

// Single associative object
$params = $api->sanitizePostParameters($request, [
    'address' => Expect::array()->items([
        'street' => Expect::string(),
        'city'   => Expect::string(),
        'zip'    => Expect::string(),
    ]),
]);
// Input:  {"address": {"street": "123 Main St", "city": "Paris", "zip": "75001"}}

// Optional array with default empty
$params = $api->sanitizePostParameters($request, [
    'filters' => Expect::array()->optional(),
]);
// If absent, defaults to []
```

---

### EntityExpectation

Returned by `Expect::entity(string $entityClass)`.

**Class:** `UbeeDev\LibBundle\Builder\EntityExpectation`

Looks up a Doctrine entity by a given field value. The entity class is set at construction time.

| Method | Description | Default |
|--------|-------------|---------|
| `keyParam(string $keyParam)` | The entity field to search by. | `'id'` |
| `by(string $fieldName)` | Alias for `keyParam()`. | -- |
| `extraParams(array $extraParams)` | Additional `findOneBy` criteria merged with the key parameter. | `[]` |

```php
// Look up by ID (default)
$params = $api->sanitizePostParameters($request, [
    'user' => Expect::entity(User::class),
]);
// Calls: $userRepository->findOneBy(['id' => $value])

// Look up by slug
$params = $api->sanitizePostParameters($request, [
    'category' => Expect::entity(Category::class)->by('slug'),
]);
// Calls: $categoryRepository->findOneBy(['slug' => $value])

// Look up with extra criteria
$params = $api->sanitizePostParameters($request, [
    'product' => Expect::entity(Product::class)
        ->by('sku')
        ->extraParams(['active' => true]),
]);
// Calls: $productRepository->findOneBy(['sku' => $value, 'active' => true])
```

---

### FileExpectation

Returned by `Expect::file()`.

**Class:** `UbeeDev\LibBundle\Builder\FileExpectation`

Validates an `UploadedFile` instance. Optionally restricts allowed file extensions and MIME types.

| Method | Description |
|--------|-------------|
| `extensions(array $extensions)` | Allowed file extensions. Leading dots are optional -- both `'.csv'` and `'csv'` work. Case-insensitive. |
| `mimetypes(array $mimetypes)` | Allowed MIME types. |

```php
// Accept only spreadsheet files
$params = $api->sanitizeFileParameters($request, [
    'import_file' => Expect::file()
        ->extensions(['.csv', '.xlsx', '.xls'])
        ->mimetypes(['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
]);

// Accept any file (no extension/mime restrictions)
$params = $api->sanitizeFileParameters($request, [
    'attachment' => Expect::file(),
]);

// Optional file upload
$params = $api->sanitizeFileParameters($request, [
    'avatar' => Expect::file()->extensions(['.jpg', '.png', '.webp'])->optional(),
]);
```

---

### EnumExpectation

Returned by `Expect::enum(string $enumClass)` and `Expect::customEnum(string $enumClass)`.

**Class:** `UbeeDev\LibBundle\Builder\EnumExpectation`

The enum class is set at construction time. `enum()` expects a PHP `BackedEnum`; `customEnum()` expects an implementation of `CustomEnumInterface`.

No additional methods beyond the common ones (`required()`, `optional()`).

```php
// Standard PHP BackedEnum
enum UserRole: string {
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';
}

$params = $api->sanitizePostParameters($request, [
    'role' => Expect::enum(UserRole::class),
]);
// Input:  {"role": "admin"}
// Output: ['role' => UserRole::ADMIN]

// CustomEnumInterface
$params = $api->sanitizePostParameters($request, [
    'status' => Expect::customEnum(OrderStatus::class),
]);
```

The sanitizer uses `tryFrom()` internally, so invalid values resolve to `null` (which triggers a `validation.invalid` error if the parameter is required).

---

### BasicExpectation

Returned by `Expect::bool()`, `Expect::date()`, `Expect::datetime()`, and `Expect::money()`.

**Class:** `UbeeDev\LibBundle\Builder\BasicExpectation`

No additional methods beyond the common ones (`required()`, `optional()`).

```php
$params = $api->sanitizePostParameters($request, [
    'active'    => Expect::bool(),
    'birthDate' => Expect::date()->optional(),
    'startAt'   => Expect::datetime(),
    'price'     => Expect::money(),
]);
```

**Type coercion rules:**

| Type | Input | Result |
|------|-------|--------|
| `bool` | `"true"` or `"1"` | `true` |
| `bool` | Any other value | `false` |
| `date` | Date string (e.g. `"2026-03-15"`) | `Date` object |
| `datetime` | Date string or Unix timestamp | `DateTime` object (in default timezone) |
| `money` | Integer amount in cents (e.g. `"4999"`) | `Money::EUR(4999)` |

---

## Pagination

### Paginator

Paginates Doctrine query results using `page` and `per` query parameters. Wraps the Doctrine `Paginator` for count-aware pagination.

**Class:** `UbeeDev\LibBundle\Service\Paginator`

#### getPaginatedQueryResult

Applies pagination to a `QueryBuilder`, executes the query, and returns a `PaginatedResult`. The page size is capped at a maximum of 50 items. The default page size is controlled by the `PAGE_SIZE` environment variable (default: `30`).

```php
/** @var \UbeeDev\LibBundle\Service\Paginator $paginator */

// GET /api/users?page=2&per=20
$qb = $userRepository->createQueryBuilder('u')
    ->orderBy('u.createdAt', 'DESC');

$result = $paginator->getPaginatedQueryResult($qb, $request);
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$queryBuilder` | `QueryBuilder` | -- | The Doctrine query builder to paginate. |
| `$request` | `Request` | -- | Reads `page` (default: `1`) and `per` (default: configured `PAGE_SIZE`) from query parameters. |
| `$dtoClass` | `?string` | `null` | When provided, each entity is wrapped as `new $dtoClass($entity, ...$params)`. |
| `$params` | `array` | `[]` | Extra arguments passed to the DTO constructor after the entity. |

**Query parameters:**

| Query param | Default | Max | Description |
|-------------|---------|-----|-------------|
| `page` | `1` | -- | The page number (1-indexed). |
| `per` | `PAGE_SIZE` env var | `50` | Number of items per page. |

With a DTO class:

```php
$result = $paginator->getPaginatedQueryResult($qb, $request, UserDto::class);
// Each item in getCurrentPageResults() is a UserDto instance

// With extra constructor parameters
$result = $paginator->getPaginatedQueryResult($qb, $request, UserDto::class, [$mediaManager]);
// Each DTO is constructed as: new UserDto($entity, $mediaManager)
```

---

### PaginatorFactory

Internal factory that creates a Doctrine `Paginator` instance from a `QueryBuilder`. Used by `Paginator` -- you rarely need to call it directly.

**Class:** `UbeeDev\LibBundle\Service\PaginatorFactory`

#### initPaginator

```php
/** @var \UbeeDev\LibBundle\Service\PaginatorFactory $factory */

$doctrinePaginator = $factory->initPaginator($queryBuilder);
```

---

### PaginatedResult

Immutable value object holding the result of a paginated query. Provides all the metadata needed to build pagination controls in a frontend or API response.

**Class:** `UbeeDev\LibBundle\Model\PaginatedResult`

#### Constructor

```php
new PaginatedResult(
    array $currentPageResults,
    int   $nbTotalResults,
    int   $nbCumulativeResults,
    int   $pageNumber,
    int   $pageSize
);
```

#### Methods

| Method | Return type | Description |
|--------|-------------|-------------|
| `getCurrentPageResults()` | `array` | The items on the current page. |
| `getNbTotalResults()` | `int` | Total number of matching records across all pages. |
| `getNbCumulativeResults()` | `int` | Cumulative number of results through the end of the current page. |
| `getPageNumber()` | `int` | Current page number (1-indexed). |
| `getPageSize()` | `int` | Configured items per page. |
| `getCurrentPageSize()` | `int` | Actual number of items on the current page (may be less than `pageSize` on the last page). |
| `getLastPageNumber()` | `int` | The last page number. |
| `getPreviousPageNumber()` | `?int` | Previous page number, or `null` if on the first page. |
| `getNextPageNumber()` | `?int` | Next page number, or `null` if on the last page. |
| `isFirstPage()` | `bool` | Whether the current page is the first page. |
| `isLastPage()` | `bool` | Whether the current page is the last page. |
| `hasPreviousPage()` | `bool` | Whether a previous page exists. |
| `hasNextPage()` | `bool` | Whether a next page exists. |

**Example:**

```php
$result = $paginator->getPaginatedQueryResult($qb, $request);

$items       = $result->getCurrentPageResults(); // [User, User, ...]
$total       = $result->getNbTotalResults();      // 142
$currentPage = $result->getPageNumber();          // 2
$pageSize    = $result->getPageSize();            // 20
$lastPage    = $result->getLastPageNumber();      // 8
$hasNext     = $result->hasNextPage();            // true
$hasPrev     = $result->hasPreviousPage();        // true
$nextPage    = $result->getNextPageNumber();      // 3
$prevPage    = $result->getPreviousPageNumber();  // 1
$cumulative  = $result->getNbCumulativeResults(); // 40
$pageItems   = $result->getCurrentPageSize();     // 20
```

---

## Error Handling

### InvalidArgumentException

Thrown by the sanitizer when one or more parameters fail validation. Carries a structured error map and the original input data.

**Class:** `UbeeDev\LibBundle\Exception\InvalidArgumentException`

Implements `\JsonSerializable` for direct use in JSON error responses.

| Method | Return type | Description |
|--------|-------------|-------------|
| `getErrors()` | `array` | Map of parameter names to error message keys. |
| `getData()` | `mixed` | The original unsanitized input data. |
| `jsonSerialize()` | `array` | Returns `['message' => '...', 'errors' => [...]]`. |

**Error message keys:**

| Key | Meaning |
|-----|---------|
| `validation.required` | A required parameter is missing, empty, or null. |
| `validation.invalid` | The value could not be coerced to the expected type. |
| `validation.not_allowed_value` | The value is not in the `allowedValues` list. |
| `validation.file.extension_invalid` | The uploaded file has a disallowed extension. |
| `validation.file.mime_type_invalid` | The uploaded file has a disallowed MIME type. |

```php
use UbeeDev\LibBundle\Exception\InvalidArgumentException;

try {
    $params = $api->sanitizePostParameters($request, [
        'email' => Expect::email(),
        'role'  => Expect::enum(UserRole::class),
    ]);
} catch (InvalidArgumentException $e) {
    return new JsonResponse($e, 400);
    // {"message": "Parameters fail the sanitizing expectations.", "errors": {"email": "validation.required"}}
}
```

Nested array errors include the item index:

```php
// If attendees[1].email is missing:
// $e->getErrors() = ['attendees' => [1 => ['email' => 'validation.required']]]
```

---

### FileValidationException

Thrown when a file upload fails extension or MIME type validation.

**Class:** `UbeeDev\LibBundle\Exception\FileValidationException`

This exception is caught internally by the sanitizer and converted to the appropriate error key (`validation.file.extension_invalid` or `validation.file.mime_type_invalid`) in the error map.

---

## Sanitization Behavior

This section summarizes how the sanitizer processes parameters.

### Strict mode

When `$strictMode` is `true` (the default), any parameters in the input that are not declared in the expectations map are silently removed from the output. Set `$strictMode` to `false` to preserve all input keys.

### Empty values

Values that are empty strings (`""`), the literal string `"null"`, or PHP `null` are treated as absent. If a default value is configured, it is used. If no default is set and the parameter is required, a `validation.required` error is raised. Empty arrays for `array`-type parameters also trigger `validation.required` when the parameter is required.

### HTML stripping

By default, all string values have HTML tags stripped via `strip_tags()` before type coercion. This applies to `string`, `email`, `name`, and `url` types. Use `keepHtml()` on a `StringExpectation` to disable this behavior. Non-string types and file uploads are not affected.

### Type coercion

Strings are trimmed before processing. Float values accept commas as decimal separators (`"1,5"` becomes `1.5`). Boolean values treat `"true"` and `"1"` as `true`; all other values become `false`. DateTime values accept both date strings and Unix timestamps (integers).

### Legacy format support

The sanitizer accepts expectations in three formats:

1. **ExpectationBuilder** (recommended): `Expect::string()->optional()`
2. **ParameterType enum**: `ParameterType::STRING`
3. **Plain string**: `'string'`

All three are normalized internally.

```php
// These three are equivalent for a required string parameter:
$expectations = ['name' => Expect::string()];
$expectations = ['name' => ParameterType::STRING];
$expectations = ['name' => 'string'];
```
