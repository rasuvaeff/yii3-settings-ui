# AGENTS.md — yii3-settings-ui

Guidance for AI agents working on this package. Read before changing code.

## What this is

Web admin panel for `rasuvaeff/yii3-settings`. PSR-15 actions render a typed
edit UI on top of `SettingsInspector` (read-model: source + masking) and
`WritableSettingsProvider` (writes) — both usually implemented by
`rasuvaeff/yii3-settings-db`. Namespace: `Rasuvaeff\Yii3SettingsUi`.

Public API: `Yii\List\Action`, `Yii\Edit\Action`, `Yii\Update\Action`, `Yii\Reset\Action`
(PSR-15 handlers), `SettingsRoutes`, `Form\SettingForm` (a `FormModel`),
`Validation\SettingValueValidator`, `Renderer\TemplateRendererInterface` +
`ViewTemplateRenderer`, `Event\SettingChanged`, `Exception\InvalidSettingValueException`.
Config-plugin groups: `di`, `params`, `routes`.
Internals (`@internal`): `Renderer\EditPageRenderer`, `Service\SettingsGridFactory`,
`Validation\SettingValueRules`, `Validation\SettingValueNormalizer`,
`View\SettingPresenter`, and the `Service\*` responders/processors/`SettingsUrls`.
Crypto lives in the settings packages; the UI never touches plaintext secrets.

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **Secrets never leak.** A secret's plaintext must never reach HTML, a URL, a
   redirect, a flash message, a log, or a `SettingChanged` event. Secret edit
   fields render empty; a blank submit keeps the current value (no write). Any
   change near secret rendering/validation must keep `ViewRenderingTest` green.
4. **Preserve the public contract.** Update README + tests with any API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
docker run --rm -v "$PWD":/app -w /app composer:2 composer release-check
```

Or with Make:

```bash
make build
make cs-fix
make psalm
make test
make test-coverage
make mutation
make release-check
```

`composer.lock` is gitignored (library).
`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## Invariants & gotchas

- The package is RBAC-agnostic. Routes (`config/routes.php`) carry no auth; the
  host app wraps them in its own auth/RBAC middleware via `SettingsRoutes`'s
  `middlewares` option. Processors only enforce domain rules: unknown key → 404,
  read-only setting rejects Update/Reset → 403.
- Validation runs through `SettingValueValidator`: yiisoft/validator rules
  built by `SettingValueRules` (value-free messages so secret input never leaks)
  decide accept/reject; `SettingValueNormalizer` casts the accepted value to the
  `SettingDefinition` type. Invalid input re-renders the edit page (HTTP 200)
  and does **not** write.
- `Array` settings: the edit textarea is JSON; it is `json_decode`d before
  `set()`. Never pass the raw textarea string to the provider.
- Rendering goes through `TemplateRendererInterface`; `WebViewRenderer` is final
  and must not be referenced directly from actions (untestable + needs explicit
  view path/layout). `ViewTemplateRenderer` pins the bundled view path.
- The list view is a `GridView` (yiisoft/yii-dataview) rendered server-side by
  `Service\SettingsGridFactory`, which constructs `new GridView($container)`
  with the application DI container (injected via `config/di.php`) and passes
  the pre-rendered HTML to the template as `gridHtml`. The host does **not**
  need to bootstrap `WidgetFactory`. The edit view uses `yiisoft/html` widgets.
  Both views apply **Bootstrap 5** classes only (no inline styles) — the host
  app must provide Bootstrap CSS. The list template also receives the raw
  `settings` (list<SettingPresenter>) so a custom view can build its own grid.
- CSRF is the application middleware's responsibility; the edit form emits a
  hidden `_csrf` field when a `csrf_token` request attribute is present.
- `yiisoft/router` needs a concrete router implementation
  (e.g. `yiisoft/router-fastroute`) — provided by the host app, and as a dev
  dependency here.
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`,
  explicit types.

- `examples/` is part of the public contract: keep scripts runnable and update
  `examples/README.md` when example usage changes.

## When you finish

- Update `README.md` (and `examples/` if usage changed); update `CHANGELOG.md`
  when releasing.
- Re-run `composer build`; if the change affects the public API or release
  process, also run `make release-check`. Paste the output.
