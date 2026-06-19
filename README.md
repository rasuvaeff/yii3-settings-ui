# rasuvaeff/yii3-settings-ui

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/yii3-settings-ui/v)](https://packagist.org/packages/rasuvaeff/yii3-settings-ui)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/yii3-settings-ui/downloads)](https://packagist.org/packages/rasuvaeff/yii3-settings-ui)
[![Build](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-settings-ui/php)](https://packagist.org/packages/rasuvaeff/yii3-settings-ui)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)

Admin UI panel for managing Yii3 runtime settings.

> Using an AI coding assistant? [llms.txt](llms.txt) contains a compact API reference you can share with the model.

A drop-in admin panel for [`rasuvaeff/yii3-settings`](https://github.com/rasuvaeff/yii3-settings):
list runtime settings in a sortable grid, edit them with per-type validation,
reset overrides back to config/default, and handle secret values safely (masked,
never rendered in plaintext).

## Requirements

- PHP 8.3+
- `rasuvaeff/yii3-settings` ^1.0 - definitions, `SettingsInspector`, `WritableSettingsProvider`
- `yiisoft/yii-view-renderer`, `yiisoft/router`, `yiisoft/user`
- `yiisoft/html`, `yiisoft/validator`, `yiisoft/form-model`, `yiisoft/data`, `yiisoft/yii-dataview`
- A concrete router implementation (e.g. `yiisoft/router-fastroute`) - provided by your application
- Bootstrap 5 CSS loaded by the host application (views use Bootstrap classes, no inline styles)

The list grid is rendered server-side from the application DI container
(`SettingsGridFactory`), so the host does **not** need to bootstrap
`WidgetFactory`.

## Installation

```bash
composer require rasuvaeff/yii3-settings-ui
```

## Usage

The package ships Yii3 config-plugin wiring (`di`, `params`). Add your params
to the merge chain:

```php
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;

return [
    'rasuvaeff/yii3-settings' => [
        'definitions' => [
            // Presentation metadata (label/group/help/choices/readonly) lives on the
            // definition itself — a single source of truth, no parallel map.
            'mail.from' => ['type' => 'string', 'default' => 'noreply@example.com', 'group' => 'Mail'],
            'orders.max_items' => ['type' => 'int', 'default' => 100, 'label' => 'Max items per order', 'group' => 'Orders'],
            'billing.stripe_key' => ['type' => 'string', 'secret' => true, 'label' => 'Stripe secret key', 'group' => 'Billing'],
        ],
    ],
    SettingsRoutes::PARAM_KEY => [
        'route_prefix' => '/admin/settings',
        'layout' => null,
        'views' => [
            'list' => '/abs/path/to/settings-list.php',
            'edit' => '/abs/path/to/settings-edit.php',
        ],
        'middlewares' => [
            'all' => [AuthMiddleware::class],
        ],
        // RequestBodyParser is added automatically to POST routes (update, reset).
        // Set 'body_parser' => false if your pipeline already applies it globally.
    ],
];
```

`layout` controls the shared wrapper. `views.list` and `views.edit` override only the corresponding templates; they do not replace the layout.

Bind the settings contracts to your provider. With `rasuvaeff/yii3-settings-db` ^1.0
this is automatic — its config-plugin binds `WritableSettingsProvider`,
`SettingsProvider` and `SettingsInspector` to the same `DbSettingsProvider`. For a
custom backend, bind them yourself:

```php
use Rasuvaeff\Yii3Settings\{SettingsInspector, WritableSettingsProvider};
use Rasuvaeff\Yii3SettingsDb\DbSettingsProvider;

return [
    WritableSettingsProvider::class => DbSettingsProvider::class,
    SettingsInspector::class => DbSettingsProvider::class,
];
```

## Routes

| Method | Path | Action | Default name |
|---|---|---|---|
| GET | `{prefix}` | `Yii\List\Action` | `settings/list` |
| GET | `{prefix}/{key}/edit` | `Yii\Edit\Action` | `settings/edit` |
| POST | `{prefix}/{key}` | `Yii\Update\Action` | `settings/update` |
| POST | `{prefix}/{key}/reset` | `Yii\Reset\Action` | `settings/reset` |

`middlewares.{all,list,edit,update,reset}` — add middlewares per slot without forking the routes. `RequestBodyParser` is added automatically to the POST routes (update, reset); set `'body_parser' => false` in params to opt out.

URLs and redirects are generated through the router (`UrlGeneratorInterface`) by route name; links stay correct under any prefix or subdomain. Override `route_names` in params when your app uses a different naming convention.

### Flat-route wiring

Wire the bundled `config/routes.php` explicitly in `configuration.php`:

```php
'routes' => 'vendor/rasuvaeff/yii3-settings-ui/config/routes.php',
```

The route prefix, names and middlewares are read from params (`SettingsRoutes::PARAM_KEY`).

### Group-based admin panel

Inside a `Group` (the typical approach for a shared-prefix admin area):

```php
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Yiisoft\Router\Group;

Group::create(prefix: '/admin')->routes(
    ...SettingsRoutes::fromParams($params),
);
```

`fromParams()` reads prefix, names, middlewares and body-parser opt-out from
`$params[SettingsRoutes::PARAM_KEY]`, so route registration and `SettingsUrls` URL generation
are always in sync.

For full control over names, use `create()` directly and add matching `route_names` to params:

```php
SettingsRoutes::create(
    prefix: '/settings',
    names: ['list' => 'admin/settings', 'edit' => 'admin/settings/edit'],
    middlewares: ['all' => [AuthMiddleware::class]],
)
```

## Authorization

The package does not enforce access control internally. Protect routes via `middlewares.all` (or per-route keys). The package provides `CurrentUser` injection for audit events only.

## Public API

| Class | Description |
|---|---|
| `SettingsRoutes` | Builds the 4 routes; `fromParams($params)` for group-based panels, `create()` for full control |
| `Yii\List\Action` | GET flat grid (Group/Key/Value/Type/Source), secrets masked |
| `Yii\Edit\Action` | GET type-aware edit form |
| `Yii\Update\Action` | POST validate + save, re-render on invalid input |
| `Yii\Reset\Action` | POST remove override -> config/default |
| `Form\SettingForm` | Submitted edit input (`present` + `value`) |
| `Validation\SettingValueValidator` | Validates + normalizes submitted input against the setting type (value-free error messages) |
| `Renderer\TemplateRendererInterface` | Rendering seam (testable actions) |
| `Renderer\ViewTemplateRenderer` | Default renderer over `WebViewRenderer` |
| `Event\SettingChanged` | PSR-14 event after update/reset (secret-safe, actor = current user ID) |
| `Exception\InvalidSettingValueException` | Type validation failure |

The `Service\*` responders/processors, `Service\SettingsUrls` and
`Service\SettingsGridFactory` are `@internal` — auto-wired through
`config/di.php`; the host references the `Yii\*\Action` handlers, not these.

Setting labels, groups, help text, choices and read-only flags come from
`SettingDefinition` (`rasuvaeff/yii3-settings`), not a UI-side map.

## Security

| Concern | Behaviour |
|---|---|
| Secret values | Never rendered: list shows `●●●● (set)` / `— (not set)`, edit shows an empty password field |
| Blank secret submit | Keeps the current value — `set()` is not called |
| Validation errors | Re-renders the edit page with HTTP 200 |
| Events | `SettingChanged` carries `null` value for secret keys and optional `actor` |
| Read-only settings | `Update`/`Reset` rejected with HTTP 403 |
| CSRF | Enforced by your application middleware; the form emits a hidden `_csrf` field when a `csrf_token` request attribute is present |
| Output | All values pass through `Yiisoft\Html\Html::encode()` / Html widgets / GridView encoding |

Crypto/at-rest encryption is handled by the settings packages, not here — the UI only ever sees masked state for secrets.

## Customising views

Override `views.list` and/or `views.edit` in params with absolute paths to your own templates. The templates receive the same variables as the bundled ones — see `resources/views/`.

The edit form uses input names scoped under `Setting[...]` (e.g. `Setting[value]`). Custom edit templates must preserve this scope for `SettingForm::fromParsedBody()` to work.

**Flash messages** are not built in — the package does not know about the host app's session. Subscribe to `SettingChanged` in your app to add flash notifications, cache invalidation, or audit trail entries.

## Why `SettingChanged` Exists

The package emits `SettingChanged` after update/reset so the host app can react without coupling itself to the UI actions. Typical uses are cache invalidation, audit logging, dependent reconfiguration, and observability hooks. The `actor` field carries the current user ID; `null` for guests.
