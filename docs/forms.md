# Forms

This document covers the form types, data transformers, anti-robot protection, and form themes provided by the UbeeDev LibBundle.

These components extend Symfony's form system with reusable UI elements (action buttons, custom HTML blocks), value-object transformers for the bundle's domain types, and pluggable bot-detection strategies that can be swapped without changing your form code.

## Table of Contents

- [Form Types](#form-types)
  - [LibButtonType](#libbuttontype)
  - [DeleteButtonType](#deletebuttontype)
  - [CustomHtmlType](#customhtmltype)
- [Form Transformers](#form-transformers)
  - [HtmlNameTransformer](#htmlnametransformer)
  - [UrlTransformer](#urltransformer)
- [Anti-Robot Protection](#anti-robot-protection)
  - [HoneypotVerifier](#honeypotverifier)
  - [TurnstileVerifier](#turnstileverifier)
  - [Twig Functions](#twig-functions)
  - [Switching the Default Verifier](#switching-the-default-verifier)
- [Form Themes](#form-themes)

---

## Form Types

### LibButtonType

An enhanced button rendered from a hidden field, with support for icons, confirmation dialogs, execute-once protection, and page refresh after action.

- **Class:** `UbeeDev\LibBundle\Form\Type\LibButtonType`
- **Parent:** Symfony `HiddenType`
- **Template:** `@UbeeDevLib/Form/Type/lib_button.html.twig`

#### Options

| Option                 | Type   | Default | Description                                             |
|------------------------|--------|---------|---------------------------------------------------------|
| `icon`                 | string | `null`  | CSS icon class prepended to the label (e.g. `fa fa-save`) |
| `requires_confirmation`| bool   | `false` | Show a confirmation dialog before executing the action  |
| `execute_once`         | bool   | `false` | Disable the button after first click to prevent double-submit |
| `refresh`              | bool   | `false` | Refresh the page after the action completes             |

The type is unmapped (`mapped: false`) and not required (`required: false`) by default.

#### Example

```php
use UbeeDev\LibBundle\Form\Type\LibButtonType;

$builder->add('save', LibButtonType::class, [
    'label' => 'Save',
    'icon' => 'fa fa-save',
    'requires_confirmation' => true,
    'execute_once' => true,
]);
```

The rendered HTML adds CSS classes based on the enabled options:

- `js-lib-btn--requires-confirmation` when `requires_confirmation` is `true`
- `js-lib-btn--execute-once` when `execute_once` is `true`
- `js-lib-btn--refresh` when `refresh` is `true`

---

### DeleteButtonType

A delete button that renders a trash icon and fires an Ajax call to a configured delete route.

- **Class:** `UbeeDev\LibBundle\Form\Type\DeleteButtonType`
- **Parent:** Symfony `TextType`
- **Block prefix:** `delete_button`
- **Template:** `@UbeeDevLib/Form/Type/delete_button.html.twig`

The button automatically includes a `data-ajax-url` attribute pointing to the `ubee_dev_lib.admin.ajax_delete` route and renders an `fa fa-trash-o` icon.

#### Example

```php
use UbeeDev\LibBundle\Form\Type\DeleteButtonType;

$builder->add('delete', DeleteButtonType::class);
```

---

### CustomHtmlType

Use this type to inject read-only content, help text, or custom UI elements into a Symfony form without creating a full form type. It renders an arbitrary Twig template inside the form layout; the template path and its variables are passed through the field's `attr` option.

- **Class:** `UbeeDev\LibBundle\Form\Type\CustomHtmlType`
- **Parent:** Symfony `TextType`
- **Block prefix:** `custom_html`
- **Template:** `@UbeeDevLib/Form/Type/custom_html.html.twig`

#### Example

```php
use UbeeDev\LibBundle\Form\Type\CustomHtmlType;

$builder->add('info_panel', CustomHtmlType::class, [
    'attr' => [
        'dataHtml' => 'path/to/your/template.html.twig',
        'data' => [
            'title' => 'Important Notice',
            'message' => 'This section is read-only.',
        ],
    ],
]);
```

The `custom_html_widget` block includes the template specified in `attr.dataHtml` and passes `attr.data` as template variables.

---

## Form Transformers

The bundle provides two data transformers that convert between value objects and strings for use in Symfony forms. These are needed when your entity properties use value objects like `HtmlName` or `Url`, so the form can convert between the object and the plain string shown in the input field.

### HtmlNameTransformer

Transforms between the `HtmlName` value object and a plain string.

- **Class:** `UbeeDev\LibBundle\Form\Transformer\HtmlNameTransformer`
- **Implements:** `Symfony\Component\Form\DataTransformerInterface`

| Method             | Input            | Output           |
|--------------------|------------------|------------------|
| `transform()`      | `HtmlName\|null` | `?string`        |
| `reverseTransform()`| `string\|null`  | `?HtmlName`      |

#### Example

```php
use UbeeDev\LibBundle\Form\Transformer\HtmlNameTransformer;

$builder->add('name', TextType::class);
$builder->get('name')->addModelTransformer(new HtmlNameTransformer());
```

### UrlTransformer

Transforms between the `Url` value object and a plain string.

- **Class:** `UbeeDev\LibBundle\Form\Transformer\UrlTransformer`
- **Implements:** `Symfony\Component\Form\DataTransformerInterface`

| Method             | Input         | Output      |
|--------------------|---------------|-------------|
| `transform()`      | `Url\|null`   | `?string`   |
| `reverseTransform()`| `string\|null`| `?Url`     |

#### Example

```php
use UbeeDev\LibBundle\Form\Transformer\UrlTransformer;

$builder->add('website', UrlType::class);
$builder->get('website')->addModelTransformer(new UrlTransformer());
```

---

## Anti-Robot Protection

The bundle provides two anti-robot verification strategies through the `AntiRobotVerifierInterface`. The `AntiRobotVerifierFactory` manages the available verifiers and selects the active one.

- **Factory:** `UbeeDev\LibBundle\Service\AntiRobot\AntiRobotVerifierFactory`
- **Interface:** `UbeeDev\LibBundle\Service\AntiRobot\AntiRobotVerifierInterface`

The interface defines three methods:

| Method             | Return  | Description                                     |
|--------------------|---------|-------------------------------------------------|
| `verify()`         | `bool`  | Returns `true` if the request is from a human   |
| `getName()`        | `string`| Returns the verifier identifier (e.g. `honeypot`)|
| `getTemplateData()`| `array` | Returns data needed by the frontend template    |

### HoneypotVerifier

The default verifier. It uses hidden form fields and timing checks to detect bots, with no external dependencies.

- **Class:** `UbeeDev\LibBundle\Service\AntiRobot\HoneypotVerifier`
- **Name:** `honeypot`

Detection rules (via `FormManager`):

1. The hidden `as_first` field must be present in the submitted data.
2. If JavaScript is enabled (indicated by the presence of `as_second`), both `as_first` and `as_second` must be empty, and the `execution_time` must be a number greater than 2 seconds.
3. The submitted data must not contain HTML tags.

#### Template Setup

Include the anti-spam hidden fields in your Twig template:

```twig
{{ include('@UbeeDevLib/Components/_anti-spam.html.twig') }}
```

This renders a hidden input (`as_first`) that bots tend to fill in. A companion JavaScript file adds the `as_second` and `execution_time` fields dynamically.

#### Controller Usage

```php
use UbeeDev\LibBundle\Service\AntiRobot\AntiRobotVerifierFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

public function submit(Request $request, AntiRobotVerifierFactory $factory): Response
{
    $verifier = $factory->getVerifier(); // defaults to 'honeypot'

    if (!$verifier->verify($request, [])) {
        throw new \RuntimeException('Bot detected');
    }

    // process form...
}
```

### TurnstileVerifier

Uses Cloudflare Turnstile to verify that the user is human. Requires an HTTP client and two environment variables.

- **Class:** `UbeeDev\LibBundle\Service\AntiRobot\TurnstileVerifier`
- **Name:** `turnstile`

#### Required Environment Variables

```env
TURNSTILE_SECRET_KEY=your-secret-key
TURNSTILE_SITE_KEY=your-site-key
```

#### Controller Usage

```php
use UbeeDev\LibBundle\Service\AntiRobot\AntiRobotVerifierFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

public function submit(Request $request, AntiRobotVerifierFactory $factory): Response
{
    $verifier = $factory->getVerifier('turnstile');

    $parameters = $request->request->all();
    if (!$verifier->verify($request, $parameters)) {
        throw new \RuntimeException('Turnstile verification failed');
    }

    // process form...
}
```

The verifier reads the `cf-turnstile-response` key from the `$parameters` array and validates it against the Cloudflare API at `https://challenges.cloudflare.com/turnstile/v0/siteverify`.

### Twig Functions

The `AntiRobotExtension` registers two Twig functions:

| Function                | Return   | Description                                              |
|-------------------------|----------|----------------------------------------------------------|
| `anti_robot_verifier()` | `string` | Returns the name of the active verifier (e.g. `honeypot`)|
| `anti_robot_data()`     | `array`  | Returns template data from the active verifier           |

Both functions accept an optional verifier name argument. When omitted, the default verifier is used.

```twig
{% set verifier = anti_robot_verifier() %}
{% set data = anti_robot_data() %}

{% if verifier == 'turnstile' %}
    <script src="{{ data.script_url }}" async defer></script>
    <div class="cf-turnstile" data-sitekey="{{ data.site_key }}"></div>
{% else %}
    {{ include('@UbeeDevLib/Components/_anti-spam.html.twig') }}
{% endif %}
```

### Switching the Default Verifier

The default verifier is `honeypot`. To switch it, set the `ANTI_ROBOT_DEFAULT_VERIFIER` environment variable:

```env
ANTI_ROBOT_DEFAULT_VERIFIER=turnstile
```

You can also list all registered verifiers programmatically:

```php
$factory->getAvailableVerifiers(); // e.g. ['honeypot', 'turnstile']
```

---

## Form Themes

The bundle registers two Twig form themes in `config/packages/twig.yaml`:

```yaml
twig:
  form_themes:
    - '@UbeeDevLib/Form/Type/lib_button.html.twig'
    - '@UbeeDevLib/Form/Type/custom_html.html.twig'
```

These themes provide the widget blocks for `LibButtonType` (`lib_button_widget`) and `CustomHtmlType` (`custom_html_widget`). The `DeleteButtonType` template (`@UbeeDevLib/Form/Type/delete_button.html.twig`) is loaded via the type's `getFormTheme()` method rather than through the global form themes configuration.
