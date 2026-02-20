# Testing

This bundle provides a comprehensive set of test helpers for PHPUnit and Behat, covering entity factories, database cleanup, email verification, HTTP mocking, time freezing, and browser-based acceptance testing.

## Table of Contents

- [Factory](#factory)
- [RandomTrait](#randomtrait)
- [Cleaner](#cleaner)
- [ContextState](#contextstate)
- [ValidationReporter](#validationreporter)
- [FakeEmailProvider](#fakeemailprovider)
- [EmailProducerStub](#emailproducerstub)
- [HttpMock](#httpmock)
- [DateTimeMock / DateMock](#datetimemock--datemock)
- [CommonContext (Behat)](#commoncontext-behat)
- [PHPUnitHelper](#phpunithelper)
- [MonologCISlackHandler](#monologcislackhandler)

---

## Factory

`Factory` implements `FactoryInterface` and uses Faker to generate entities for tests. It relies on `RandomTrait` for random data and `DateTimeTrait` for date helpers. Faker is configured with the `fr_FR` locale.

```php
use UbeeDev\LibBundle\Tests\Helper\Factory;

// Constructor requires EntityManager, Kernel, and ParameterBag
$factory = new Factory($entityManager, $kernel, $parameterBag);

// Build a Media entity without persisting it
$media = $factory->buildMedia(['context' => 'photos']);

// Create a Media entity (build + persist + flush)
$media = $factory->createMedia(['context' => 'photos']);

// Build any entity by passing an instance, custom properties, and defaults
// Properties are applied via setXxx() or addXxx() methods on the entity
$entity = $factory->buildEntity(new User(), ['name' => 'John'], $defaultProperties);

// Build or create by resource name (delegates to buildXxx / createXxx methods)
$entity = $factory->buildOrCreate('User', false, ['name' => 'John']);  // build
$entity = $factory->buildOrCreate('User', true, ['name' => 'John']);   // create

// Generate a string of exactly N characters
$text = $factory->generateCharacters(10);

// Generate a unique number with a specific digit count
$number = $factory->generateUniqueNumber(6); // e.g. 394821
```

When `buildEntity` encounters a property that maps to a Doctrine `ManyToOne` association and the value is an array (not yet an entity instance), it automatically calls the corresponding `buildXxx` method to create the related entity.

---

## RandomTrait

`RandomTrait` provides Faker-based random data generators. It can be used in any test class that has a `$faker` property (a `Faker\Generator` instance).

```php
use UbeeDev\LibBundle\Tests\Helper\RandomTrait;

class MyTest
{
    use RandomTrait;

    protected \Faker\Generator $faker;

    public function __construct()
    {
        $this->faker = \Faker\Factory::create('fr_FR');
    }
}
```

**Available methods:**

| Category | Methods |
|---|---|
| Identity | `randomName()`, `randomFirstName()`, `randomLastName()`, `randomUsername()` |
| Contact | `randomPhoneNumber()`, `randomEmail()`, `randomDomain()` |
| Address | `randomAddressLine()`, `randomPostCode()`, `randomCity()`, `randomStreetNumber()`, `randomStreetName()` |
| Web | `randomUrl()`, `randomPassword()`, `randomToken()` |
| Social | `randomFacebookLink()`, `randomFacebookEventLink()`, `randomFacebookId()`, `randomYoutubeLink()`, `randomYoutubeEmbedLink()` |
| Finance | `randomIban()`, `randomPrice()` |
| Text | `randomTitle($wordCount)`, `randomSentence($wordCount)`, `randomSentences($count)`, `randomParagraph($nbSentences)` |
| Misc | `randomBool()`, `randomId()`, `getRandomId()`, `getRandomIpv4()`, `randomGtmId()`, `randomFunction()`, `generateImage($dir)` |

---

## Cleaner

`Cleaner` implements `CleanerInterface` and handles database purging and filesystem cleanup between tests.

```php
use UbeeDev\LibBundle\Tests\Helper\Cleaner;

$cleaner = new Cleaner($entityManager, $currentEnv);

// Purge a single table (DELETE FROM)
$cleaner->purgeTable('users');

// Purge all tables except migration_versions
// Uses Doctrine ORMPurger in DELETE mode
$cleaner->purgeAllTables();

// Remove and recreate a folder
$cleaner->cleanFolder('/tmp/uploads');
```

`purgeAllTables()` uses `Doctrine\Common\DataFixtures\Purger\ORMPurger` with `PURGE_MODE_DELETE` and excludes the `migration_versions` table to preserve migration state.

---

## ContextState

`ContextState` implements `ContextStateInterface` and provides a simple key-value store for sharing state between Behat contexts. It is essentially a global object, so use it with caution.

```php
use UbeeDev\LibBundle\Tests\Helper\ContextState;

$contextState = new ContextState();

// Store a value
$contextState->setState('lastCreatedUser', $user);

// Retrieve a value (returns null if key does not exist)
$user = $contextState->getState('lastCreatedUser');
```

In `CommonContext`, state is accessed via convenience methods:

```php
$this->setState('key', $value);
$value = $this->getState('key');
```

---

## ValidationReporter

`ValidationReporter` wraps Symfony's `ValidatorInterface` to simplify asserting validation errors in tests.

```php
use UbeeDev\LibBundle\Tests\Helper\ValidationReporter;

$reporter = new ValidationReporter($validator);

// Optionally set validation groups (defaults to ['Default'])
$reporter->setValidationGroups(['Default', 'registration']);

// Get a report with error details
$result = $reporter->report($entity);
// Returns:
// [
//     'err'     => ConstraintViolationListInterface,
//     'count'   => 2,
//     'message' => 'name: This value should not be blank.'
// ]

// Get the raw ConstraintViolationList
$violations = $reporter->validate($entity);
```

---

## FakeEmailProvider

`FakeEmailProvider` implements `EmailProviderInterface` and writes emails to the filesystem instead of sending them. This allows Behat and PHPUnit tests to verify that emails were sent with the correct content.

```php
use UbeeDev\LibBundle\Tests\Helper\FakeEmailProvider;

$provider = new FakeEmailProvider($testToken, $parameterBag);

$provider->sendMail(
    from: 'noreply@example.com',
    to: ['user@example.com'],
    body: '<h1>Welcome</h1>',
    subject: 'Welcome aboard',
);
```

**Storage details:**

- Emails are stored in `var/fake-emails/{TEST_TOKEN}/`
- Filename format: `{recipientEmail}{sha256(subject)}`
- Email data is serialized with PHP's `serialize()` and contains: `from`, `body`, `subject`, `replyTo`, `contentType`, `attachments`

Configure it as the email provider in your test environment via `services.yaml` under `when@test`.

---

## EmailProducerStub

`EmailProducerStub` extends the real `EmailProducer` and replaces the RabbitMQ-based email sending with `FakeEmailProvider`. Instead of publishing a message to the queue, it writes the email directly to disk.

```php
use UbeeDev\LibBundle\Tests\Helper\EmailProducerStub;

// Typically configured as a service override in when@test
$stub = new EmailProducerStub($fakeEmailProvider, $rabbitProducer, $currentEnv);

// Calling sendMail writes to filesystem instead of publishing to RabbitMQ
$stub->sendMail(
    from: 'noreply@example.com',
    to: ['user@example.com'],
    text: '<p>Hello</p>',
    subject: 'Test Subject',
);
```

---

## HttpMock

`HttpMock` implements Symfony's `HttpClientInterface` and allows you to mock HTTP responses in tests. When a request matches a stored mock, it returns the mocked response. Otherwise, it falls back to the real HTTP client.

```php
use UbeeDev\LibBundle\Tests\Helper\HttpMock;

$httpMock = new HttpMock($parameterBag, $realHttpClient, $testToken);

// Register a mock response
$httpMock->mockData(
    method: 'GET',
    uri: 'https://api.example.com/users/1',
    expectedReturnedData: ['id' => 1, 'name' => 'John'],
    optionsSent: []  // optional: match specific request options
);

// When the same request is made, the mock is returned
$response = $httpMock->request('GET', 'https://api.example.com/users/1');
$data = $response->getContent(); // '{"id":1,"name":"John"}'

// Read mock data directly
$data = $httpMock->getData('GET', 'https://api.example.com/users/1');

// Clear all mock data
$httpMock->clearData();
```

**Storage details:**

- Mock files are stored in `var/http-mock/{TEST_TOKEN}/`
- Filename format: `{sha1(method + url + json(options))}.json`
- Options are sorted by key before hashing to ensure consistent file paths

---

## DateTimeMock / DateMock

`DateTimeMock` and `DateMock` extend the bundle's `DateTime` and `Date` classes with [slope-it/clock-mock](https://github.com/slope-it/clock-mock) support (requires the `ext-uopz` PHP extension). They allow you to freeze time in tests.

### In PHPUnit (recommended)

Tests extending `AbstractWebTestCase` can use the built-in `mockTime()` and `resetMockTime()` methods:

```php
class MyServiceTest extends AbstractWebTestCase
{
    public function testExpiredSubscription(): void
    {
        // Freeze time to a specific date
        $this->mockTime('2024-06-15T10:30:00+02:00');

        $now = new DateTime(); // 2024-06-15 10:30:00
        $today = new Date();   // 2024-06-15 00:00:00

        $this->assertTrue($subscription->isExpired());
    }

    public function testFutureDate(): void
    {
        $this->mockTime('2030-01-01T00:00:00+01:00');

        $now = new DateTime();       // 2030-01-01 00:00:00
        $tomorrow = new DateTime('+1 day'); // 2030-01-02 00:00:00

        $this->resetMockTime(); // optional, also called automatically in tearDown
    }
}
```

`mockTime()` handles all the boilerplate: it freezes `ClockMock`, and registers `DateTimeMock`/`DateMock` via `uopz` so that any `new DateTime()` or `new Date()` in the tested code returns the frozen time.

### Manual usage (without AbstractWebTestCase)

```php
use UbeeDev\LibBundle\Tests\Helper\DateTimeMock;
use UbeeDev\LibBundle\Tests\Helper\DateMock;
use UbeeDev\LibBundle\Entity\DateTime;
use UbeeDev\LibBundle\Entity\Date;
use SlopeIt\ClockMock\ClockMock;

// Freeze time
ClockMock::freeze(new DateTime('2024-06-15 10:30:00'));

// Replace the real classes with mocks via uopz
uopz_set_mock(DateTime::class, DateTimeMock::class);
uopz_set_mock(Date::class, DateMock::class);

// Now any `new DateTime()` or `new Date()` uses the frozen time
$now = new DateTime(); // 2024-06-15 10:30:00

// Reset when done
ClockMock::reset();
uopz_unset_mock(DateTime::class);
uopz_unset_mock(Date::class);
```

### In Behat

Time mocking is handled by the `CommonContext` step `the current time is "datetime"` which takes care of freezing and resetting automatically via `@BeforeScenario` hooks.

---

## CommonContext (Behat)

`CommonContext` extends Mink's `MinkContext` and provides 100+ step definitions for browser-based acceptance testing with Selenium2. It injects `Factory`, `Cleaner`, `ContextState`, `SlackManager`, and other services via its constructor.

### Navigation

```gherkin
When I click on "Dashboard"
When I click on text "Submit"
When I hover on text "Menu item"
When I click on tab "Settings"
When I switch to the opened tab
When I switch to the main tab
When I switch to the in page tab "Details"
When I switch to the iframe
```

### Assertions

```gherkin
Then I should see a success message
Then I should see a success message with "Record saved."
Then I should not see a error message
Then I should see a error message with "Invalid input."
Then the absolute url should match "/users/42"
Then I should approx. see "some text"
Then I should see a "pdf" document
Given The text "Welcome" should be displayed
Given The text "Error" should not be displayed
Then the text "hello" should be part of the HTML
Then the text "secret" should not be part of the HTML
```

### Data Tables

```gherkin
Then I should see following data displayed in this order:
  | Name  | Email             |
  | Alice | alice@example.com |
  | Bob   | bob@example.com   |

Then I should see following data displayed:
  | Name  | Email             |
  | Alice | alice@example.com |

Then the following "category" options should be displayed:
  | label    | selected |
  | Option A | true     |
  | Option B |          |
```

### Forms

```gherkin
When I fill in "startDate" date with "today"
When I fill in "eventDate" datetime with "+3 days"
Then I fill in "startTime" timepicker with "14:30"
Then I search and select "Paris" from "city"
Then the fields should have following values:
  | label | value      |
  | Name  | John       |
  | Email | j@test.com |
```

### Buttons and Elements

```gherkin
Then The "Submit" button should be disabled
Then The "Submit" button should not be disabled
Given I set browser window size to "1920" x "1080"
Then I scroll to the page top
Then I scroll to the page bottom
```

### Email Testing

```gherkin
Then I should receive an email on "user@example.com" with subject "Welcome"
Then I should receive an "my-project" email on "user@example.com" with subject "Welcome"
Then the email subject should be "Welcome aboard"
Then the email text should contains "Click here to verify"
Then the email sender should be "noreply@example.com"
When I click on the link "Verify Email" in the email
```

### Time Mocking

```gherkin
Given the current time is "2024-06-15 10:30:00"
```

This step freezes time for both PHP (via `ClockMock` + `uopz`) and writes a mock time file for JavaScript to consume.

### Cookies and Sessions

```gherkin
Given I clear the cookie "session_id"
Then I should have the cookie "tracking"
Then I should not have the cookie "tracking"
Then I should have the cookie "theme" with "dark"
Then I drop the session
```

### Screenshots

```gherkin
When I take screenshot
```

Screenshots are automatically captured on test failure via the `@AfterStep` hook. In CI environments (when `IS_CI=true`), screenshots are uploaded to a Slack channel thread.

### File Upload

```gherkin
When I drop "document.pdf" on the drop zone
```

### Debugging

```gherkin
Then I dump the first 20 lines
Then I dump the first 10 body lines
Then I dump the http headers
When I wait for 3 seconds
```

### Setup Hooks

| Hook | Behavior |
|---|---|
| `@BeforeScenario` | Clears the session (restart if started) |
| `@BeforeScenario` | Resets `ClockMock` and removes time mock files |
| `@BeforeScenario @clearEmails` | Purges fake email directory |
| `@BeforeScenario @resizeWindowToMobile` | Resizes window to iPhone 6 (375x667) |
| `@AfterScenario @resizeWindowToMobile` | Resets window to desktop (1920x1080) |
| `@AfterStep` | Takes screenshot on failure, uploads to Slack in CI |
| `@AfterStep` | Pauses on failure when `DEBUG` env var is set |

### Utility Methods

The context also exposes utility methods that can be called from child contexts:

```php
// Generate a route path
$path = $this->pathFromRouteName('app_user_show', ['id' => 42]);

// Access shared state between contexts
$this->setState('lastUser', $user);
$user = $this->getState('lastUser');

// Get the upload file path (adapts to Selenium vs Goutte driver)
$file = $this->getUploadFile('document.pdf');

// Spin: retry a callback until it returns true or timeout
$this->spin(function () {
    return $this->getPage()->find('css', '.loaded') !== null;
}, 30, 'Element never appeared');

// Access sub-services
$this->getFactory();
$this->getCleaner();
$this->getPage();
$this->getKernel();
```

---

## PHPUnitHelper

`PHPUnitHelper` is a trait that provides a replacement for the deprecated `withConsecutive` method removed in PHPUnit 10.

```php
use UbeeDev\LibBundle\Tests\Helper\PHPUnitHelper;

class MyTest extends TestCase
{
    use PHPUnitHelper;

    public function testConsecutiveCalls(): void
    {
        $mock = $this->createMock(MyService::class);

        $mock->expects($this->exactly(3))
            ->method('process')
            ->with(...self::withConsecutive(
                ['first_arg_call1', 'second_arg_call1'],
                ['first_arg_call2', 'second_arg_call2'],
                ['first_arg_call3', 'second_arg_call3'],
            ))
            ->willReturnOnConsecutiveCalls('result1', 'result2', 'result3');
    }
}
```

Each argument list must have the same number of arguments. Arguments can be raw values (compared with `assertEquals`) or PHPUnit `Constraint` instances (compared with `assertThat`).

---

## MonologCISlackHandler

`MonologCISlackHandler` extends `SlackHandler` (which extends Monolog's `SlackHandler`) to post test failure logs to a Slack thread during CI runs.

```yaml
# services.yaml (when@test)
UbeeDev\LibBundle\Tests\Helper\MonologCISlackHandler:
    arguments:
        $isCi: '%env(IS_CI)%'
        $slackNotificationTs: '%env(SLACK_NOTIFICATION_TS)%'
        $token: '%env(SLACK_TOKEN)%'
        $channel: '%env(SLACK_CHANNEL)%'
```

**Behavior:**

- Only active when the `IS_CI` environment variable is set to `true`
- Posts log messages as replies to a Slack thread identified by `SLACK_NOTIFICATION_TS`
- Inherits thread-aware posting from the parent `SlackHandler`, which injects `threadTs` into the Slack API payload
- Default log level is `Debug`
