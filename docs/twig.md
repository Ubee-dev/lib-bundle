# Twig Extensions and Templates

This document covers all Twig filters, functions, globals, and template components provided by the UbeeDev LibBundle.

## Table of Contents

- [LibExtension](#libextension)
  - [Filters](#filters)
    - [asset_rev](#asset_rev)
    - [youtubeEmbedUrl](#youtubeembedurl)
    - [embedUrl](#embedurl)
    - [youtubeThumbnailUrl](#youtubethumbnailurl)
    - [slugify_ua_ids](#slugify_ua_ids)
    - [utmParams](#utmparams)
  - [Functions](#functions)
    - [removeKeys](#removekeys)
    - [parameter](#parameter)
    - [getMockedJSTimestamp](#getmockedjstimestamp)
    - [getCountries](#getcountries)
    - [formattedPhoneNumber](#formattedphonenumber)
    - [getEnvName](#getenvname)
  - [Globals](#globals)
    - [has_cookie_consent](#has_cookie_consent)
- [AntiRobotExtension](#antirobotextension)
  - [anti_robot_data](#anti_robot_data)
  - [anti_robot_verifier](#anti_robot_verifier)
- [Template Components](#template-components)
  - [Components](#components)
    - [Environment Pill](#environment-pill)
    - [Dynamic Modal Button](#dynamic-modal-button)
    - [Disable Animations in Test Env](#disable-animations-in-test-env)
    - [Mock Timestamp in Test Env](#mock-timestamp-in-test-env)
    - [Anti-Spam Honeypot](#anti-spam-honeypot)
  - [Layout](#layout)
    - [Dynamic Modal](#dynamic-modal)
    - [Gallery Modal](#gallery-modal)
  - [Markdown](#markdown)
    - [Markdown Preview](#markdown-preview)
    - [Markdown Preview Template](#markdown-preview-template)
  - [Partials](#partials)
    - [Delete Button](#delete-button)
  - [SVG](#svg)
    - [Collaborator](#collaborator)
    - [Student](#student)

---

## LibExtension

**Class:** `UbeeDev\LibBundle\Twig\LibExtension`

Provides general-purpose filters and functions for asset management, video embedding, URL handling, phone formatting, and more.

### Filters

#### asset_rev

Resolves an asset path through the `rev-manifest.json` file for cache-busting. When a minified version exists in the manifest and debug mode is off, it returns the minified path. Otherwise, it returns the versioned path prefixed with `dist/`.

```twig
<link rel="stylesheet" href="{{ 'css/app.css'|asset_rev }}">
<script src="{{ 'js/app.js'|asset_rev }}"></script>
```

The filter reads from `rev-manifest.json` in the public directory. In production (debug off), if `css/app.min.css` exists in the manifest, it is used automatically.

#### youtubeEmbedUrl

Converts a YouTube watch URL to an embeddable iframe URL. Adds `rel=0` and `modestbranding=1` by default.

| Parameter  | Type   | Default | Description                    |
|------------|--------|---------|--------------------------------|
| `url`      | string | --      | The YouTube URL to convert     |
| `autoplay` | bool   | `false` | Enable autoplay on the embed   |

```twig
{{ 'https://www.youtube.com/watch?v=abc123'|youtubeEmbedUrl }}
{# Output: https://www.youtube.com/embed/abc123?autoplay=0&modestbranding=1&rel=0 #}

{{ 'https://youtu.be/abc123'|youtubeEmbedUrl(true) }}
{# Output: https://www.youtube.com/embed/abc123?autoplay=1&modestbranding=1&rel=0 #}
```

Supported input formats:
- `https://www.youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`
- `https://www.youtube.com/live/VIDEO_ID`
- `https://www.youtube.com/embed/VIDEO_ID`

#### embedUrl

Converts any supported video URL (YouTube or Vimeo) to its embed URL. Detects the provider automatically.

| Parameter  | Type   | Default | Description                    |
|------------|--------|---------|--------------------------------|
| `url`      | string | --      | The video URL to convert       |
| `autoplay` | bool   | `false` | Enable autoplay on the embed   |

```twig
{# YouTube #}
{{ 'https://www.youtube.com/watch?v=abc123'|embedUrl }}
{# Output: https://www.youtube.com/embed/abc123?autoplay=0&modestbranding=1&rel=0 #}

{# Vimeo #}
{{ 'https://vimeo.com/123456789'|embedUrl }}
{# Output: https://player.vimeo.com/video/123456789?autoplay=0&byline=0&portrait=0&title=0 #}

{# Vimeo live event #}
{{ 'https://vimeo.com/event/123456789'|embedUrl }}
{# Output: https://vimeo.com/event/123456789?autoplay=0&byline=0&portrait=0&title=0 #}
```

Returns `null` if the URL is empty.

#### youtubeThumbnailUrl

Gets the thumbnail image URL for a YouTube video.

| Parameter | Type   | Default    | Description                              |
|-----------|--------|------------|------------------------------------------|
| `url`     | string | --         | The YouTube URL                          |
| `quality` | string | `'medium'` | Thumbnail quality: `default`, `medium`, or `high` |

```twig
<img src="{{ 'https://www.youtube.com/watch?v=abc123'|youtubeThumbnailUrl }}" alt="Video thumbnail">
{# Output: //img.youtube.com/vi/abc123/mqdefault.jpg #}

<img src="{{ 'https://www.youtube.com/watch?v=abc123'|youtubeThumbnailUrl('high') }}" alt="Video thumbnail">
{# Output: //img.youtube.com/vi/abc123/hqdefault.jpg #}

<img src="{{ 'https://www.youtube.com/watch?v=abc123'|youtubeThumbnailUrl('default') }}" alt="Video thumbnail">
{# Output: //img.youtube.com/vi/abc123/maxresdefault.jpg #}
```

#### slugify_ua_ids

Slugifies text for use as Google Analytics event IDs by replacing commas and spaces with underscores.

```twig
{{ 'click, signup'|slugify_ua_ids }}
{# Output: click__signup #}

{{ 'page view'|slugify_ua_ids }}
{# Output: page_view #}
```

#### utmParams

Appends the current request's UTM parameters to a URL. Useful for preserving UTM tracking across internal navigation.

```twig
<a href="{{ 'https://example.com/landing'|utmParams }}">Visit</a>
{# If current page has ?utm_source=google&utm_medium=cpc, output: #}
{# https://example.com/landing?utm_source=google&utm_medium=cpc #}
```

---

### Functions

#### removeKeys

Removes specified keys from an associative array. Useful for filtering form variables or cleaning up data before passing to templates.

| Parameter      | Type  | Description                    |
|----------------|-------|--------------------------------|
| `array`        | array | The source array               |
| `keysToRemove` | array | List of keys to remove         |

```twig
{% set cleaned = removeKeys(form.vars, ['id', 'name', 'full_name']) %}

{# Before: {id: 1, name: 'field', label: 'Email', full_name: 'form[field]'} #}
{# After:  {label: 'Email'} #}
```

#### parameter

Accesses any Symfony container parameter directly from a Twig template.

| Parameter | Type   | Description                     |
|-----------|--------|---------------------------------|
| `name`    | string | The parameter name to retrieve  |

```twig
{{ parameter('kernel.project_dir') }}
{# Output: /var/www/my-project #}

{{ parameter('kernel.environment') }}
{# Output: dev #}

{% if parameter('kernel.debug') %}
    <p>Debug mode is on</p>
{% endif %}
```

#### getMockedJSTimestamp

Returns a JavaScript-compatible timestamp (milliseconds) read from the mock time file, or `null` if no mock time is set. Used exclusively in the test environment to synchronize mocked PHP time (ClockMock) with JavaScript.

The mock time is read from `tests/assets/mockTime{TEST_TOKEN}.txt`.

```twig
{% set timestamp = getMockedJSTimestamp() %}

{% if timestamp is not null %}
    <script>
        console.log('Mocked time:', new Date({{ timestamp }}));
    </script>
{% endif %}
```

In practice, you should use the `_mock-timestamp-in-test-env.html.twig` component instead of calling this function directly (see [Mock Timestamp in Test Env](#mock-timestamp-in-test-env)).

#### getCountries

Returns a sorted list of countries from the bundled `countries.json` file. The list is sorted using the `fr_FR` locale collation and contains two keys: `national` and `international`.

```twig
{% set countries = getCountries() %}

<select name="country">
    <optgroup label="National">
        {% for code, name in countries.national %}
            <option value="{{ code }}">{{ name }}</option>
        {% endfor %}
    </optgroup>
    <optgroup label="International">
        {% for code, name in countries.international %}
            <option value="{{ code }}">{{ name }}</option>
        {% endfor %}
    </optgroup>
</select>
```

#### formattedPhoneNumber

Formats a phone number from an entity implementing `PhoneNumberInterface`. Combines the country calling code and phone number into a readable format.

| Parameter     | Type                   | Description                    |
|---------------|------------------------|--------------------------------|
| `phoneEntity` | `PhoneNumberInterface` | Entity with phone number data  |

```twig
{{ formattedPhoneNumber(user) }}
{# Output: +33 6 12 34 56 78 #}

{{ formattedPhoneNumber(contact) }}
{# Output: +1 555 123 4567 #}
```

The entity must implement `UbeeDev\LibBundle\Model\PhoneNumberInterface`, which requires `getCountryCallingCode()` and `getPhoneNumber()` methods.

#### getEnvName

Returns a display name for the current Symfony environment. Used by the environment pill component.

```twig
{{ getEnvName() }}
{# In dev: "local" #}
{# In prod/test: null #}
```

Currently only the `dev` environment returns a value (`"local"`). All other environments return `null`.

---

### Globals

#### has_cookie_consent

A boolean indicating whether the user has a `hasCookieConsent` cookie set.

```twig
{% if has_cookie_consent %}
    {# Load analytics scripts #}
    <script src="analytics.js"></script>
{% else %}
    {# Show cookie consent banner #}
    {{ include('_cookie_banner.html.twig') }}
{% endif %}
```

---

## AntiRobotExtension

**Class:** `UbeeDev\LibBundle\Twig\AntiRobotExtension`

Provides functions for the anti-robot verification system (honeypot or Cloudflare Turnstile).

### anti_robot_data

Returns template data for the specified anti-robot verifier. The data structure depends on the active verifier (honeypot fields, Turnstile site key, etc.).

| Parameter      | Type    | Default | Description                                      |
|----------------|---------|---------|--------------------------------------------------|
| `verifierName` | string  | `null`  | Verifier name (`'honeypot'` or `'turnstile'`). Falls back to default. |

```twig
{% set robot_data = anti_robot_data() %}
{# Uses the default verifier configured via ANTI_ROBOT_DEFAULT_VERIFIER #}

{% set robot_data = anti_robot_data('turnstile') %}
{# Explicitly use Turnstile regardless of default #}
```

### anti_robot_verifier

Returns the name of the active anti-robot verifier.

| Parameter      | Type    | Default | Description                                      |
|----------------|---------|---------|--------------------------------------------------|
| `verifierName` | string  | `null`  | Verifier name to resolve. Falls back to default.  |

```twig
{% set verifier = anti_robot_verifier() %}
{# Output: "honeypot" or "turnstile" #}

{% if anti_robot_verifier() == 'turnstile' %}
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
{% endif %}
```

---

## Template Components

All templates are namespaced under `@UbeeDevLib/` and can be included using the standard Twig `include` function or `{% include %}` tag.

### Components

#### Environment Pill

**Template:** `@UbeeDevLib/Components/_env-pill.html.twig`

Displays a colored pill badge indicating the current environment. Only renders when `getEnvName()` returns a non-null value (currently only in `dev`, displayed as "local").

```twig
{{ include('@UbeeDevLib/Components/_env-pill.html.twig') }}
```

Rendered output in dev:
```html
<span class="env-pill env-pill_local">local</span>
```

Place it in your base layout (e.g., in a fixed corner) so developers always know which environment they are on.

#### Dynamic Modal Button

**Template:** `@UbeeDevLib/Components/_btn_dynamic-modal.html.twig`

A button that triggers a dynamic modal with Ajax-loaded content. Requires the [Dynamic Modal](#dynamic-modal) layout component to be present on the page.

| Parameter     | Type   | Description                              |
|---------------|--------|------------------------------------------|
| `class`       | string | CSS class(es) for the button             |
| `modal_title` | string | Title shown in the modal header          |
| `modal_href`  | string | URL to fetch modal body content via Ajax |
| `text`        | string | Button inner HTML (rendered raw)         |

```twig
{{ include('@UbeeDevLib/Components/_btn_dynamic-modal.html.twig', {
    class: 'btn btn_primary',
    modal_title: 'User Details',
    modal_href: path('user_details_ajax', {id: user.id}),
    text: '<i class="fa fa-eye"></i> View'
}) }}
```

#### Disable Animations in Test Env

**Template:** `@UbeeDevLib/Components/_disable-animations-in-test-env.html.twig`

Injects a `<style>` block that disables all CSS transitions and animations. Only active in the `test` environment. This ensures stable and deterministic Behat screenshots by preventing mid-animation captures.

```twig
{# In your base layout's <head> #}
{{ include('@UbeeDevLib/Components/_disable-animations-in-test-env.html.twig') }}
```

In test, this outputs:
```html
<style>
    * {
        transition-property: none !important;
        animation: none !important;
        /* ...and all vendor-prefixed variants */
    }
</style>
```

In all other environments, nothing is rendered.

#### Mock Timestamp in Test Env

**Template:** `@UbeeDevLib/Components/_mock-timestamp-in-test-env.html.twig`

Injects JavaScript time mocking using the TimeShift library. Only active in the `test` environment and only when a mock time file exists. Synchronizes the browser's `Date` object with the PHP ClockMock timestamp so that both frontend and backend share the same mocked time.

```twig
{# In your base layout, before any JS that uses Date #}
{{ include('@UbeeDevLib/Components/_mock-timestamp-in-test-env.html.twig') }}
```

This component:
1. Reads the mocked timestamp via `getMockedJSTimestamp()`
2. Loads the TimeShift and Lodash libraries
3. Overrides the global `Date` constructor with a mocked version
4. Time advances naturally from the mocked starting point (not frozen)

#### Anti-Spam Honeypot

**Template:** `@UbeeDevLib/Components/_anti-spam.html.twig`

Renders a hidden honeypot field designed to catch spam bots. The field is visually hidden (via CSS) and should remain empty; if a bot fills it in, the submission can be rejected server-side.

```twig
<form method="post">
    {{ include('@UbeeDevLib/Components/_anti-spam.html.twig') }}

    {# Your actual form fields #}
    <input type="text" name="email" placeholder="Email">
    <button type="submit">Submit</button>
</form>
```

The wrapper `div#js-wrapper-as` should be hidden with CSS in your stylesheet.

---

### Layout

#### Dynamic Modal

**Template:** `@UbeeDevLib/Layout/_dynamic_modal.html.twig`

A reusable Bootstrap-style modal whose body is loaded via Ajax. Includes built-in error handling with a fallback error message. Works in tandem with the [Dynamic Modal Button](#dynamic-modal-button) component.

| Parameter | Type   | Description                    |
|-----------|--------|--------------------------------|
| `id`      | string | Unique ID for the modal        |

```twig
{# Include once in your base layout #}
{{ include('@UbeeDevLib/Layout/_dynamic_modal.html.twig', {id: 'details-modal'}) }}
```

Features:
- The modal title is set dynamically from the trigger button's `data-modal-title` attribute.
- The modal body is fetched from the URL in `data-modal-href`.
- On Ajax failure, an error message is displayed (translated via the `frontend` domain, key `ubee_dev_lib.default.server_error`).
- Provides a `{% block modal_footer %}` that can be overridden when extending the template.

#### Gallery Modal

**Template:** `@UbeeDevLib/Layout/_gallery_modal.html.twig`

A full-screen image gallery modal. Content is injected dynamically via JavaScript into the `.js-modal__content` container.

```twig
{# Include once in your base layout #}
{{ include('@UbeeDevLib/Layout/_gallery_modal.html.twig') }}
```

The modal uses the `js-modal_gallery` ID and the `bh-gallery-modal` CSS class for Behat test targeting.

---

### Markdown

#### Markdown Preview

**Template:** `@UbeeDevLib/markdown-preview.html.twig`

A standalone page that extends the markdown preview template. Used as the rendering target for live markdown preview (typically loaded in an iframe).

```twig
{# Usually accessed via a route, not included directly #}
{# The route renders this template with a "content" variable #}
```

#### Markdown Preview Template

**Template:** `@UbeeDevLib/markdown-preview-template.html.twig`

Base template for rendering parsed markdown content. Wraps the content in a `.rich-content` container and provides a `{% block stylesheets %}` for injecting custom styles.

```twig
{# Extend this template to add custom styles to the preview #}
{% extends '@UbeeDevLib/markdown-preview-template.html.twig' %}

{% block stylesheets %}
    <link rel="stylesheet" href="{{ asset('css/markdown.css') }}">
{% endblock %}
```

The template receives a `content` variable containing the pre-rendered HTML.

---

### Partials

#### Delete Button

**Template:** `@UbeeDevLib/Partials/_btn__delete.html.twig`

A standalone delete button that triggers an Ajax deletion via the `ubee_dev_lib.admin.ajax_delete` route.

| Parameter     | Type   | Default | Description                          |
|---------------|--------|---------|--------------------------------------|
| `entityId`    | mixed  | `''`    | The ID of the entity to delete       |
| `entityClass` | string | --      | The fully qualified class name       |

```twig
{{ include('@UbeeDevLib/Partials/_btn__delete.html.twig', {
    entityId: user.id,
    entityClass: 'App\\Entity\\User'
}) }}
```

The button uses the `js-delete-btn` CSS class and sends the entity ID and class to the backend via `data-` attributes. JavaScript handles the Ajax call and confirmation prompt.

---

### SVG

#### Collaborator

**Template:** `@UbeeDevLib/Svg/_collaborator.svg.twig`

An SVG illustration of a collaborator (177x177px). Uses `currentColor` so the color adapts to the parent element's text color.

| Parameter | Type   | Default            | Description              |
|-----------|--------|--------------------|--------------------------|
| `id`      | string | `'corrector-svg'`  | SVG element ID           |
| `class`   | string | `''`               | CSS class(es)            |

```twig
{{ include('@UbeeDevLib/Svg/_collaborator.svg.twig', {
    id: 'my-collaborator',
    class: 'illustration'
}) }}
```

#### Student

**Template:** `@UbeeDevLib/Svg/_student.svg.twig`

An SVG illustration of a student (177x177px). Uses `currentColor` for adaptive coloring.

| Parameter | Type   | Default          | Description              |
|-----------|--------|------------------|--------------------------|
| `id`      | string | `'student-svg'`  | SVG element ID           |
| `class`   | string | `''`             | CSS class(es)            |

```twig
{{ include('@UbeeDevLib/Svg/_student.svg.twig', {
    id: 'my-student',
    class: 'illustration'
}) }}
```
