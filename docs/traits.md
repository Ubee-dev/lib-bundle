# Traits Reference

This document covers all traits provided by `UbeeDev\LibBundle\Traits`. Each trait is documented with its methods, signatures, and practical PHP code examples.

Traits in this bundle provide reusable behavior that can be mixed into your entities, services, or controllers via PHP's `use` statement. They encapsulate common operations (date manipulation, money formatting, phone parsing, etc.) so you don't have to rewrite the same logic across projects. Use a trait when you need its functionality in a class that already extends another base class and cannot use inheritance.

---

## Table of Contents

- [DateTimeTrait](#datetimetrait)
- [MoneyTrait](#moneytrait)
- [PhoneNumberTrait](#phonenumbertrait)
- [StringTrait](#stringtrait)
- [VideoTrait](#videotrait)
- [EntityManagerTrait](#entitymanagertrait)
- [ProcessTrait](#processtrait)

---

## DateTimeTrait

**Namespace:** `UbeeDev\LibBundle\Traits\DateTimeTrait`

Provides utility methods for creating, comparing, formatting, and manipulating dates and times. Uses `UbeeDev\LibBundle\Entity\DateTime` and `UbeeDev\LibBundle\Entity\Date` as its core date classes. Both extend `AbstractDateTime`, which defines the default timezone constant `Europe/Paris`.

**Why use this over native PHP DateTime?** It bundles convenience methods for operations that are verbose with plain PHP -- age/period calculations, safe month addition (avoiding end-of-month overflow), French locale formatting, and custom relative date expressions like `15-{+2 months}` -- into a single reusable trait.

This trait internally uses `StringTrait`.

```php
use UbeeDev\LibBundle\Traits\DateTimeTrait;
use UbeeDev\LibBundle\Entity\DateTime;
use UbeeDev\LibBundle\Entity\Date;
use UbeeDev\LibBundle\Entity\AbstractDateTime;

class EventService
{
    use DateTimeTrait;
}

$service = new EventService();
```

### getSecondsBetweenDates

Returns the difference in seconds between two dates (`$date1 - $date2`).

```php
getSecondsBetweenDates(DateTimeInterface $date1, DateTimeInterface $date2): int
```

```php
$date1 = new DateTime('2024-01-01 12:00:00');
$date2 = new DateTime('2024-01-01 11:00:00');

$seconds = $service->getSecondsBetweenDates($date1, $date2);
// 3600
```

### convertHoursToSeconds

Converts a number of hours to seconds.

```php
convertHoursToSeconds($nbHours): float|int
```

```php
$seconds = $service->convertHoursToSeconds(2);
// 7200

$seconds = $service->convertHoursToSeconds(0.5);
// 1800.0
```

### addSecondsToDate

Returns a new `DateTime` with the given number of seconds added. Negative values subtract seconds.

```php
addSecondsToDate(DateTime $date, int $nbSeconds): DateTime
```

```php
$date = new DateTime('2024-01-01 12:00:00');

$result = $service->addSecondsToDate($date, 90);
// 2024-01-01 12:01:30

$result = $service->addSecondsToDate($date, -30);
// 2024-01-01 11:59:30
```

### addMinutesToDate

Returns a new date with the given number of minutes added.

```php
addMinutesToDate(DateTimeInterface $date, int $nbMinutes): DateTimeInterface
```

```php
$date = new DateTime('2024-01-01 12:00:00');

$result = $service->addMinutesToDate($date, 45);
// 2024-01-01 12:45:00
```

### addHoursToDate

Returns a new date with the given number of hours added.

```php
addHoursToDate(DateTimeInterface $date, int $nbHours): DateTimeInterface
```

```php
$date = new DateTime('2024-01-01 12:00:00');

$result = $service->addHoursToDate($date, 3);
// 2024-01-01 15:00:00
```

### addDaysToDate

Returns a new date with the given number of days added.

```php
addDaysToDate(DateTimeInterface $date, int $nbDays): DateTimeInterface
```

```php
$date = new DateTime('2024-01-15 10:00:00');

$result = $service->addDaysToDate($date, 7);
// 2024-01-22 10:00:00
```

### addMonthsToDate

Returns a new `DateTime` with the given number of months added. Handles end-of-month overflow safely (e.g., adding 1 month to January 31 yields February 28/29, not March 3).

```php
addMonthsToDate(DateTimeInterface $date, int $nbMonths): DateTime
```

```php
$date = new DateTime('2024-01-31 14:30:00');

$result = $service->addMonthsToDate($date, 1);
// 2024-02-29 14:30:00 (2024 is a leap year)

$result = $service->addMonthsToDate($date, -2);
// 2023-11-30 14:30:00
```

### dateStartDuringGivenDay

Checks whether two dates fall on the same calendar day.

```php
dateStartDuringGivenDay(DateTimeInterface $date, DateTimeInterface $givenDay): bool
```

```php
$date = new DateTime('2024-03-15 09:30:00');
$day  = new DateTime('2024-03-15 22:00:00');

$service->dateStartDuringGivenDay($date, $day);
// true

$otherDay = new DateTime('2024-03-16 09:30:00');
$service->dateStartDuringGivenDay($date, $otherDay);
// false
```

### dateTime

Creates a `DateTime` instance. Supports standard datetime strings, relative expressions, and a special custom format like `15-{+2 months}` or `05/{+1 year}`. Seconds are always zeroed out.

```php
dateTime(?string $datetimeString = 'now', $timezone = 'Europe/Paris'): DateTime
```

```php
// Current date and time (seconds zeroed)
$now = $service->dateTime();

// From a specific date string
$date = $service->dateTime('2024-06-15 14:30:00');

// Relative expressions
$tomorrow = $service->dateTime('+1 day');

// Special format: day 15 of the month 5 months from now
$custom = $service->dateTime('15-{+5 months}');

// With slash separator
$custom2 = $service->dateTime('05/{+1 year}');

// With a different timezone
$utcDate = $service->dateTime('now', 'UTC');
```

### date

Creates a `Date` instance (time is always set to `00:00:00`).

```php
date(?string $datetimeString = null): Date
```

```php
$today = $service->date();

$specific = $service->date('2024-06-15');
// 2024-06-15 00:00:00
```

### convertDateTimeToString

Converts a date string to a French-formatted human-readable string (e.g., "16 juillet 2019"). If the input contains a `/` separator, returns the date in `d/m/Y` format instead.

```php
convertDateTimeToString(string $date, bool $withDay = false, bool $withYear = true): string
```

```php
$result = $service->convertDateTimeToString('2019-07-16');
// "16 juillet 2019"

$result = $service->convertDateTimeToString('2019-07-16', withDay: true);
// "mardi 16 juillet 2019"

$result = $service->convertDateTimeToString('2019-07-16', withYear: false);
// "16 juillet"

$result = $service->convertDateTimeToString('16/07/2019');
// "16/07/2019"

$result = $service->convertDateTimeToString('16/07/2019', withYear: false);
// "16/07"
```

### convertJsonDateTimeToFormattedDate

Takes a JSON string containing date components and formatting instructions, then returns a formatted date string. The JSON object can contain `format`, `day`, `month`, `year`, `hour`, and `type` keys.

```php
convertJsonDateTimeToFormattedDate(string $json): string
```

```php
$json = json_encode([
    'format' => 'Y-m-d',
    'day'    => '15',
    'month'  => '6',
    'year'   => '2024',
]);

$result = $service->convertJsonDateTimeToFormattedDate($json);
// "2024-06-15"

// Using relative month offset
$json = json_encode([
    'format' => 'string', // outputs French formatted string
    'day'    => '1',
    'month'  => '+3 months',
]);

$result = $service->convertJsonDateTimeToFormattedDate($json);
// "1 septembre 2024" (if current month is June 2024)

// Using IntlDateFormatter pattern
$json = json_encode([
    'format' => 'EEEE d MMMM yyyy',
    'type'   => 'strftime',
    'day'    => '15',
    'month'  => '7',
    'year'   => '2024',
]);

$result = $service->convertJsonDateTimeToFormattedDate($json);
// "Lundi 15 Juillet 2024"
```

### isToday

Checks whether the given date is today.

```php
isToday(DateTimeInterface $dateTime): bool
```

```php
$now = new DateTime();
$service->isToday($now);
// true

$yesterday = new DateTime('-1 day');
$service->isToday($yesterday);
// false
```

### isPast

Checks whether the given date is in the past.

```php
isPast(DateTimeInterface $dateTime): bool
```

```php
$past = new DateTime('2020-01-01');
$service->isPast($past);
// true

$future = new DateTime('+1 year');
$service->isPast($future);
// false
```

### isFuture

Checks whether the given date is in the future.

```php
isFuture(DateTimeInterface $dateTime): bool
```

```php
$future = new DateTime('+1 year');
$service->isFuture($future);
// true

$past = new DateTime('2020-01-01');
$service->isFuture($past);
// false
```

### formatDate

Formats a date using a standard PHP date format string. Returns `null` if the input is `null`.

```php
formatDate(?DateTimeInterface $dateTime, string $format = 'Y-m-d'): ?string
```

```php
$date = new DateTime('2024-07-16 14:30:00');

$service->formatDate($date);
// "2024-07-16"

$service->formatDate($date, 'd/m/Y H:i');
// "16/07/2024 14:30"

$service->formatDate(null);
// null
```

### diffBetweenDates

Returns the absolute difference between two dates in the specified unit.

```php
diffBetweenDates(DateTimeInterface $date1, DateTimeInterface $date2, string $type): int
```

Supported `$type` values: `years`, `months`, `days`, `hours`, `minutes`, `seconds`.

```php
$date1 = new DateTime('2024-06-15 14:30:00');
$date2 = new DateTime('2022-03-10 10:00:00');

$service->diffBetweenDates($date1, $date2, 'years');
// 2

$service->diffBetweenDates($date1, $date2, 'months');
// 27

$service->diffBetweenDates($date1, $date2, 'days');
// 828

$date3 = new DateTime('2024-06-15 10:00:00');
$date4 = new DateTime('2024-06-15 14:30:00');

$service->diffBetweenDates($date4, $date3, 'hours');
// 4

$service->diffBetweenDates($date4, $date3, 'minutes');
// 270
```

### convertLocalDateTimeToDefaultTimezone

Converts a date from a given local timezone to the default timezone (`Europe/Paris`). Optionally sets a specific time in `HH:MM:SS` format before converting.

```php
convertLocalDateTimeToDefaultTimezone(
    DateTimeInterface $date,
    string $timeZone,
    ?string $time = null
): DateTimeInterface
```

```php
$date = new DateTime('2024-06-15 10:00:00');

// Convert from New York time to Europe/Paris
$parisDate = $service->convertLocalDateTimeToDefaultTimezone($date, 'America/New_York');

// Convert with a specific time set in the source timezone
$parisDate = $service->convertLocalDateTimeToDefaultTimezone(
    $date,
    'America/New_York',
    '09:00:00'
);
// Sets 09:00:00 in New York time, then converts to Europe/Paris

// Throws Exception if time format is not HH:MM:SS
$service->convertLocalDateTimeToDefaultTimezone($date, 'UTC', '09:00');
// Exception: "If you pass time, you must give hour:minute:second"
```

### getEarliestDate

Returns a clone of the earlier of two `AbstractDateTime` instances.

```php
getEarliestDate(AbstractDateTime $date1, AbstractDateTime $date2): AbstractDateTime
```

```php
$date1 = new DateTime('2024-01-01');
$date2 = new DateTime('2024-06-15');

$earliest = $service->getEarliestDate($date1, $date2);
// Clone of $date1 (2024-01-01)
```

### getLatestDate

Returns a clone of the later of two `AbstractDateTime` instances.

```php
getLatestDate(AbstractDateTime $date1, AbstractDateTime $date2): AbstractDateTime
```

```php
$date1 = new DateTime('2024-01-01');
$date2 = new DateTime('2024-06-15');

$latest = $service->getLatestDate($date1, $date2);
// Clone of $date2 (2024-06-15)
```

### computeOffsetForGivenTimezone

Returns the UTC offset string for a given timezone (e.g., `+01:00` or `-05:00`).

```php
computeOffsetForGivenTimezone(string $timezone): string
```

```php
$offset = $service->computeOffsetForGivenTimezone('Europe/Paris');
// "+01:00" (or "+02:00" during summer time)

$offset = $service->computeOffsetForGivenTimezone('America/New_York');
// "-05:00" (or "-04:00" during DST)

$offset = $service->computeOffsetForGivenTimezone('UTC');
// "+00:00"
```

### convertDateToString

Formats an `AbstractDateTime` using `IntlDateFormatter` with the `fr_FR` locale and a given ICU pattern.

```php
convertDateToString(AbstractDateTime $date, string $pattern): string
```

```php
$date = new DateTime('2024-07-16 14:30:00');

$service->convertDateToString($date, 'd MMMM y');
// "16 juillet 2024"

$service->convertDateToString($date, 'EEEE d MMMM y');
// "mardi 16 juillet 2024"

$service->convertDateToString($date, 'MMMM');
// "juillet"
```

---

## MoneyTrait

**Namespace:** `UbeeDev\LibBundle\Traits\MoneyTrait`

Provides formatting utilities for `Money\Money` objects from the [moneyphp/money](https://github.com/moneyphp/money) library. Amounts are stored in cents internally.

**Why use this?** It gives you consistent, locale-aware formatting of `Money\Money` value objects into human-readable strings (e.g. `1250` cents becomes `"12,50 EUR"`) and handles the cents-to-euros float conversion, so you don't repeat `IntlMoneyFormatter` boilerplate everywhere.

```php
use UbeeDev\LibBundle\Traits\MoneyTrait;
use Money\Money;

class PricingService
{
    use MoneyTrait;
}

$service = new PricingService();
```

### formatMoney

Formats a `Money` object as a human-readable string using the `FR_fr` locale with IntlMoneyFormatter.

```php
formatMoney(Money $money): string
```

```php
$money = Money::EUR(1250); // 12.50 EUR in cents

$result = $service->formatMoney($money);
// "12,50 EUR"

$money = Money::USD(9999);
$result = $service->formatMoney($money);
// "99,99 USD"
```

### formatMoneyToFloat

Converts a `Money` object to a float by dividing the amount in cents by 100.

```php
formatMoneyToFloat(Money $money): float|int
```

```php
$money = Money::EUR(1250);

$result = $service->formatMoneyToFloat($money);
// 12.5

$money = Money::EUR(10000);
$result = $service->formatMoneyToFloat($money);
// 100
```

---

## PhoneNumberTrait

**Namespace:** `UbeeDev\LibBundle\Traits\PhoneNumberTrait`

Provides phone number formatting and parsing methods using [giggsey/libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php).

**Why use this?** It wraps `libphonenumber` behind simple methods so you get consistent international and national phone formatting across the app without manually instantiating `PhoneNumberUtil`, parsing, and formatting each time.

```php
use UbeeDev\LibBundle\Traits\PhoneNumberTrait;

class ContactService
{
    use PhoneNumberTrait;
}

$service = new ContactService();
```

### getFormattedPhoneNumber

Combines a country calling code and a local phone number into a formatted international number.

```php
getFormattedPhoneNumber(int|string|null $countryCallingCode, ?string $phoneNumber): ?string
```

```php
$result = $service->getFormattedPhoneNumber(33, '612345678');
// "+33 6 12 34 56 78"

$result = $service->getFormattedPhoneNumber('1', '2025551234');
// "+1 202 555 1234"

$result = $service->getFormattedPhoneNumber(33, null);
// null
```

### getNationalFormattedNumber

Formats a phone number in national format (e.g., with spaces, without the country code prefix). The second parameter `$countryCallingCode` defaults to `33` (France).

```php
getNationalFormattedNumber(string $phoneNumber, int|string $countryCallingCode = 33): ?string
```

```php
$result = $service->getNationalFormattedNumber('612345678');
// "06 12 34 56 78"

$result = $service->getNationalFormattedNumber('612345678', 33);
// "06 12 34 56 78"
```

### getCountryCodeFromFormattedNumber

Extracts the country calling code from a fully formatted international phone number.

```php
getCountryCodeFromFormattedNumber(string $formattedNumber): ?int
```

```php
$result = $service->getCountryCodeFromFormattedNumber('+33 6 12 34 56 78');
// 33

$result = $service->getCountryCodeFromFormattedNumber('+1 202 555 1234');
// 1
```

### getLocalNumberFromFormattedNumber

Extracts the national (local) number from a fully formatted international phone number.

```php
getLocalNumberFromFormattedNumber(string $formattedNumber): ?string
```

```php
$result = $service->getLocalNumberFromFormattedNumber('+33 6 12 34 56 78');
// "612345678"

$result = $service->getLocalNumberFromFormattedNumber('+1 202 555 1234');
// "2025551234"
```

---

## StringTrait

**Namespace:** `UbeeDev\LibBundle\Traits\StringTrait`

Provides string manipulation utilities including slugification, empty value replacement, and date extraction from text.

**Why use this?** It offers unicode-safe slugification (via a custom transliterator that handles accents and special characters) and text normalization utilities used across entities, plus date-expression parsing that powers the custom `15-{+2 months}` syntax used throughout the bundle.

```php
use UbeeDev\LibBundle\Traits\StringTrait;

class TextService
{
    use StringTrait;
}

$service = new TextService();
```

### slugify

Converts a string into a URL-friendly slug. Uses a custom transliterator to handle special characters and accents, then lowercases the result. The separator is empty by default, so words are concatenated without hyphens.

```php
slugify(string $text): string
```

```php
$service->slugify('Hello World');
// "helloworld"

$service->slugify('Les écoles de Paris');
// "lesecolesdeparis"

$service->slugify('  Spaces & Special! Characters  ');
// "spacesspecialcharacters"
```

### replaceEmptyValue

Returns `$replace` if `$value` is empty (falsy, but not `0`). Returns `$value` otherwise.

```php
replaceEmptyValue(mixed $value, mixed $replace): mixed
```

```php
$service->replaceEmptyValue('', 'default');
// "default"

$service->replaceEmptyValue(null, 'fallback');
// "fallback"

$service->replaceEmptyValue('hello', 'default');
// "hello"

$service->replaceEmptyValue(0, 'default');
// 0 (zero is preserved, not treated as empty)
```

### convertMatchedDateToFormattedDate

Finds a date expression in a string and returns it formatted as a resolved date string. Supports custom formats like `15-{+5 months}`, `15/{-2 years}`, `{today}/{+3 months}`, standard date strings, and relative expressions.

```php
convertMatchedDateToFormattedDate(string $text): ?string
```

```php
// Custom format with dash separator returns Y-m-d
$service->convertMatchedDateToFormattedDate('15-{+5 months}');
// "2024-11-15" (if current month is June 2024)

// Custom format with slash separator returns Y/m/d
$service->convertMatchedDateToFormattedDate('15/{+2 months}');
// "2024/08/15"

// Relative keywords
$service->convertMatchedDateToFormattedDate('{today}/{+3 months}');
// "2024/09/15" (resolved based on today)

// Standard date string pass-through
$service->convertMatchedDateToFormattedDate('The date is 2024-08-16 in the text');
// "2024-08-16"

// Relative expression pass-through
$service->convertMatchedDateToFormattedDate('+3 months');
// "+3 months"

// No date found
$service->convertMatchedDateToFormattedDate('no date here');
// null
```

### extractStringDateFromString

Extracts a raw date expression substring from a string without resolving or formatting it. Supports the same patterns as `convertMatchedDateToFormattedDate`.

```php
extractStringDateFromString(string $text): ?string
```

```php
$service->extractStringDateFromString('Event on 15-{+2 months} at 10am');
// "15-{+2 months}"

$service->extractStringDateFromString('Due: 2024-08-16');
// "2024-08-16"

$service->extractStringDateFromString('Reminder in +3 days');
// "+3 days"

$service->extractStringDateFromString('No date info');
// null
```

---

## VideoTrait

**Namespace:** `UbeeDev\LibBundle\Traits\VideoTrait`

Provides methods for working with YouTube, Vimeo, and Facebook video URLs -- extracting IDs, generating embed URLs, retrieving thumbnail URLs, and building lazy-loaded iframe HTML.

```php
use UbeeDev\LibBundle\Traits\VideoTrait;

class VideoService
{
    use VideoTrait;
}

$service = new VideoService();
```

### extractYoutubeVideoIdFromUrl

Extracts the video ID from a YouTube URL. Supports `youtube.com/watch`, `youtu.be`, `youtube.com/embed`, and `youtube.com/live` formats. Throws an `Exception` if the URL is not a valid YouTube URL.

```php
extractYoutubeVideoIdFromUrl(string $url): string
```

```php
$service->extractYoutubeVideoIdFromUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
// "dQw4w9WgXcQ"

$service->extractYoutubeVideoIdFromUrl('https://youtu.be/dQw4w9WgXcQ');
// "dQw4w9WgXcQ"

$service->extractYoutubeVideoIdFromUrl('https://www.youtube.com/embed/dQw4w9WgXcQ');
// "dQw4w9WgXcQ"

$service->extractYoutubeVideoIdFromUrl('https://www.youtube.com/live/dQw4w9WgXcQ');
// "dQw4w9WgXcQ"
```

### isYoutubeUrl

Checks whether a URL matches a YouTube video URL pattern.

```php
isYoutubeUrl(string $url): bool
```

```php
$service->isYoutubeUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
// true

$service->isYoutubeUrl('https://vimeo.com/123456789');
// false
```

### isVimeoUrl

Checks whether a URL matches a Vimeo video URL pattern.

```php
isVimeoUrl(string $url): bool
```

```php
$service->isVimeoUrl('https://vimeo.com/123456789');
// true

$service->isVimeoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
// false
```

### getYoutubeEmbedUrl

Generates a YouTube embed URL from a standard YouTube URL. Merges default options (`rel=0`, `modestbranding=1`) with any query parameters from the original URL and custom options. Returns the original URL unchanged if it is not a valid YouTube URL.

```php
getYoutubeEmbedUrl(string $url, array $options = []): string
```

```php
$service->getYoutubeEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
// "https://www.youtube.com/embed/dQw4w9WgXcQ?modestbranding=1&rel=0"

$service->getYoutubeEmbedUrl(
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ['autoplay' => 1]
);
// "https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&modestbranding=1&rel=0"
```

### getYoutubeThumbUrl

Returns the thumbnail URL for a YouTube video given its video ID and quality level.

```php
getYoutubeThumbUrl(string $youtubeVideoId, string $quality): string
```

Supported `$quality` values:
- `default` -- returns `maxresdefault.jpg`
- `medium` -- returns `mqdefault.jpg`
- `high` -- returns `hqdefault.jpg`

Throws `InvalidArgumentException` for unsupported quality values.

```php
$service->getYoutubeThumbUrl('dQw4w9WgXcQ', 'high');
// "//img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg"

$service->getYoutubeThumbUrl('dQw4w9WgXcQ', 'medium');
// "//img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg"

$service->getYoutubeThumbUrl('dQw4w9WgXcQ', 'default');
// "//img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg"
```

### getYoutubeThumbUrlFromYoutubeUrl

Convenience method that extracts the video ID from a YouTube URL and returns its thumbnail URL.

```php
getYoutubeThumbUrlFromYoutubeUrl(string $url, string $quality): string
```

```php
$service->getYoutubeThumbUrlFromYoutubeUrl(
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'high'
);
// "//img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg"
```

### extractFacebookVideoIdFromEmbedCode

Extracts the Facebook video ID from a Facebook video embed HTML snippet. Throws an `Exception` if the embed code is not valid.

```php
extractFacebookVideoIdFromEmbedCode(string $embedCode): string
```

```php
$embedCode = '<iframe src="https://www.facebook.com/plugins/video.php?href=https://www.facebook.com/MyPage/videos/1234567890"></iframe>';

$service->extractFacebookVideoIdFromEmbedCode($embedCode);
// "1234567890"
```

### getFacebookThumbUrl

Returns the Facebook Graph API thumbnail URL for a given Facebook video ID.

```php
getFacebookThumbUrl(string $facebookVideoId): string
```

```php
$service->getFacebookThumbUrl('1234567890');
// "https://graph.facebook.com/1234567890/picture"
```

### getFacebookEmbedUrl

Extracts the Facebook video URL from embed HTML and optionally appends query options.

```php
getFacebookEmbedUrl(string $url, array $options = []): string
```

```php
$embedCode = '<iframe src="https://www.facebook.com/plugins/video.php?href=https://www.facebook.com/MyPage/videos/1234567890&show_text=0"></iframe>';

$service->getFacebookEmbedUrl($embedCode);
// "https://www.facebook.com/MyPage"

$service->getFacebookEmbedUrl($embedCode, ['autoplay' => 1]);
// "https://www.facebook.com/MyPage&autoplay=1"
```

### isFacebookEmbedUrl

Checks whether a URL or embed code matches a Facebook video embed pattern.

```php
isFacebookEmbedUrl(string $url): bool
```

```php
$url = 'https://www.facebook.com/plugins/video.php?href=https://www.facebook.com/MyPage/videos/1234567890';

$service->isFacebookEmbedUrl($url);
// true

$service->isFacebookEmbedUrl('https://www.youtube.com/watch?v=abc');
// false
```

### getVimeoEmbedUrl

Generates a Vimeo embed URL from a standard Vimeo URL. Handles both regular videos and live events. When no options are provided, default parameters `portrait=0&title=0&byline=0` are appended.

```php
getVimeoEmbedUrl(string $url, array $options = []): string
```

```php
$service->getVimeoEmbedUrl('https://vimeo.com/123456789');
// "https://player.vimeo.com/video/123456789?portrait=0&title=0&byline=0"

$service->getVimeoEmbedUrl('https://vimeo.com/123456789', ['autoplay' => 1]);
// "https://player.vimeo.com/video/123456789?autoplay=1"

// Live event URL
$service->getVimeoEmbedUrl('https://vimeo.com/event/987654321');
// "https://vimeo.com/event/987654321?portrait=0&title=0&byline=0"
```

### extractVimeoVideoIdFromUrl

Extracts the video or event ID from a Vimeo URL. Throws an `Exception` if the URL is not valid.

```php
extractVimeoVideoIdFromUrl(string $url): string
```

```php
$service->extractVimeoVideoIdFromUrl('https://vimeo.com/123456789');
// "123456789"

$service->extractVimeoVideoIdFromUrl('https://player.vimeo.com/video/123456789');
// "123456789"

$service->extractVimeoVideoIdFromUrl('https://vimeo.com/event/987654321');
// "987654321"
```

### isVimeoLiveEventUrl

Checks whether a Vimeo URL is a live event URL.

```php
isVimeoLiveEventUrl(string $url): bool
```

```php
$service->isVimeoLiveEventUrl('https://vimeo.com/event/987654321');
// true

$service->isVimeoLiveEventUrl('https://vimeo.com/123456789');
// false
```

### getLazyIframeHtml

Returns an HTML string for a lazy-loaded video embed. Works with both YouTube and Vimeo URLs. For YouTube videos, includes a thumbnail URL; for Vimeo, the thumbnail is left to the frontend lazy-loading implementation.

```php
getLazyIframeHtml(string $videoUrl): string
```

```php
$html = $service->getLazyIframeHtml('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
// Returns:
// <div class="video-embed">
//   <div class="ratio ratio-16x9">
//     <div class="js-lazyframe"
//          data-vendor="youtube"
//          data-video-id="dQw4w9WgXcQ"
//          data-src="https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&modestbranding=1&rel=0"
//          data-thumbnail="//img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg">
//     </div>
//   </div>
// </div>

$html = $service->getLazyIframeHtml('https://vimeo.com/123456789');
// Returns:
// <div class="video-embed">
//   <div class="ratio ratio-16x9">
//     <div class="js-lazyframe"
//          data-vendor="vimeo"
//          data-video-id="123456789"
//          data-src="https://player.vimeo.com/video/123456789?autoplay=1"
//          data-thumbnail="">
//     </div>
//   </div>
// </div>
```

---

## EntityManagerTrait

**Namespace:** `UbeeDev\LibBundle\Traits\EntityManagerTrait`

Provides a utility for resetting the Doctrine entity manager state.

```php
use UbeeDev\LibBundle\Traits\EntityManagerTrait;
use Doctrine\ORM\EntityManagerInterface;

class ImportService
{
    use EntityManagerTrait;
}

$service = new ImportService();
```

### ensureDatabaseConnectedAndEmCacheIsCleared

Clears the entity manager's identity map (first-level cache). Use this in long-running processes or batch operations to prevent stale entities and memory issues.

```php
ensureDatabaseConnectedAndEmCacheIsCleared(EntityManagerInterface $em): void
```

```php
// Typical usage in a batch import loop
foreach ($records as $record) {
    $service->ensureDatabaseConnectedAndEmCacheIsCleared($entityManager);

    // Process record with a clean identity map
    $entity = new Product();
    $entity->setName($record['name']);
    $entityManager->persist($entity);
    $entityManager->flush();
}
```

---

## ProcessTrait

**Namespace:** `UbeeDev\LibBundle\Traits\ProcessTrait`

Provides a method to execute shell commands and capture their output.

**When to use this?** Use it when you need a simple wrapper for running a shell command and capturing stdout as an array of lines, without pulling in the full Symfony Process component boilerplate. It calls `exec()` directly, so it is best suited for trusted, non-interactive commands.

```php
use UbeeDev\LibBundle\Traits\ProcessTrait;

class DeployService
{
    use ProcessTrait;
}

$service = new DeployService();
```

### executeCommand

Executes a shell command via `exec()` and returns the output as an array of lines.

```php
executeCommand(string $command): array
```

```php
$output = $service->executeCommand('ls -la /var/www');
// ['total 12', 'drwxr-xr-x 3 www-data ...', ...]

$output = $service->executeCommand('whoami');
// ['www-data']

$output = $service->executeCommand('php -v');
// ['PHP 8.2.0 (cli) ...', 'Copyright ...', ...]
```
