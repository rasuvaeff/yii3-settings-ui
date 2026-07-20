# rasuvaeff/yii3-settings-ui

[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/yii3-settings-ui/v)](https://packagist.org/packages/rasuvaeff/yii3-settings-ui)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/yii3-settings-ui/downloads)](https://packagist.org/packages/rasuvaeff/yii3-settings-ui)
[![Build](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-settings-ui/php)](https://packagist.org/packages/rasuvaeff/yii3-settings-ui)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
[English version](README.md)

Админ-панель UI для управления runtime-настройками Yii3.

> Используете AI-ассистента? В [llms.txt](llms.txt) — компактный API-справочник,
> которым можно поделиться с моделью.

Drop-in админ-панель для [`rasuvaeff/yii3-settings`](https://github.com/rasuvaeff/yii3-settings):
листинг runtime-настроек в сортируемой сетке, их редактирование с
постпроверкой по типу, сброс переопределений обратно к config/default и
безопасная работа с секретными значениями (маскируются, никогда не
рендерятся открытым текстом).

## Требования

- PHP 8.3+
- `rasuvaeff/yii3-settings` ^1.0 — определения, `SettingsInspector`, `WritableSettingsProvider`
- `yiisoft/yii-view-renderer`, `yiisoft/router`, `yiisoft/user`
- `yiisoft/html`, `yiisoft/validator`, `yiisoft/form-model`, `yiisoft/data`, `yiisoft/yii-dataview`
- Конкретная реализация роутера (например, `yiisoft/router-fastroute`) — предоставляется приложением
- Bootstrap 5 CSS, подключаемый хост-приложением (views используют классы Bootstrap, без inline-стилей)

Сетка списка рендерится на стороне сервера из DI-контейнера приложения
(`SettingsGridFactory`), поэтому хосту **не** нужно бутстрапить
`WidgetFactory`.

## Установка

```bash
composer require rasuvaeff/yii3-settings-ui
```

## Использование

Пакет поставляет Yii3 config-plugin-связку (`di`, `params`). Добавьте свои
params в merge-цепочку:

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

`layout` управляет общей обёрткой. `views.list` и `views.edit` переопределяют
только соответствующие шаблоны; они не заменяют layout.

Привяжите контракты настроек к своему провайдеру. С `rasuvaeff/yii3-settings-db`
^1.0 это происходит автоматически — его config-plugin привязывает
`WritableSettingsProvider`, `SettingsProvider` и `SettingsInspector` к одному
`DbSettingsProvider`. Для кастомного бэкенда привяжите их сами:

```php
use Rasuvaeff\Yii3Settings\{SettingsInspector, WritableSettingsProvider};
use Rasuvaeff\Yii3SettingsDb\DbSettingsProvider;

return [
    WritableSettingsProvider::class => DbSettingsProvider::class,
    SettingsInspector::class => DbSettingsProvider::class,
];
```

## Маршруты

| Метод | Путь | Action | Имя по умолчанию |
|---|---|---|---|
| GET | `{prefix}` | `Yii\List\Action` | `settings/list` |
| GET | `{prefix}/{key}/edit` | `Yii\Edit\Action` | `settings/edit` |
| POST | `{prefix}/{key}` | `Yii\Update\Action` | `settings/update` |
| POST | `{prefix}/{key}/reset` | `Yii\Reset\Action` | `settings/reset` |

`middlewares.{all,list,edit,update,reset}` — добавляйте middleware по слотам без
форка маршрутов. `RequestBodyParser` автоматически добавляется к POST-маршрутам
(update, reset); чтобы отказаться, задайте `'body_parser' => false` в params.

URL и редиректы генерируются через роутер (`UrlGeneratorInterface`) по имени
маршрута; ссылки остаются корректными под любым префиксом или поддоменом.
Переопределите `route_names` в params, если приложение использует другую
схему именования.

### Плоская привязка маршрутов

Подключите bundled `config/routes.php` явно в `configuration.php`:

```php
'routes' => 'vendor/rasuvaeff/yii3-settings-ui/config/routes.php',
```

Префикс маршрутов, имена и middleware читаются из params
(`SettingsRoutes::PARAM_KEY`).

### Админ-панель на группе

Внутри `Group` (типичный подход для административной зоны с общим префиксом):

```php
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Yiisoft\Router\Group;

Group::create(prefix: '/admin')->routes(
    ...SettingsRoutes::fromParams($params),
);
```

`fromParams()` читает префикс, имена, middleware и опцию отключения body-parser
из `$params[SettingsRoutes::PARAM_KEY]`, поэтому регистрация маршрутов и
генерация URL в `SettingsUrls` всегда синхронизированы.

Для полного контроля над именами используйте `create()` напрямую и добавьте
соответствующие `route_names` в params:

```php
SettingsRoutes::create(
    prefix: '/settings',
    names: ['list' => 'admin/settings', 'edit' => 'admin/settings/edit'],
    middlewares: ['all' => [AuthMiddleware::class]],
)
```

## Авторизация

Пакет не реализует контроль доступа внутри себя. Защищайте маршруты через
`middlewares.all` (или ключи per-route). Пакет предоставляет инъекцию
`CurrentUser` только для audit-событий.

## Публичный API

| Класс | Описание |
|---|---|
| `SettingsRoutes` | Строит 4 маршрута; `fromParams($params)` — для группы, `create()` — для полного контроля |
| `Yii\List\Action` | GET плоская сетка (Group/Key/Value/Type/Source), секреты маскированы |
| `Yii\Edit\Action` | GET форма редактирования с учётом типа |
| `Yii\Update\Action` | POST валидация + сохранение, повторный рендер при некорректном вводе |
| `Yii\Reset\Action` | POST удаление переопределения -> config/default |
| `Form\SettingForm` | Введённые данные редактирования (`present` + `value`) |
| `Validation\SettingValueValidator` | Валидирует и нормализует отправленный ввод по типу настройки (сообщения об ошибках без значений) |
| `Renderer\TemplateRendererInterface` | Шов рендеринга (тестируемые actions) |
| `Renderer\ViewTemplateRenderer` | Дефолтный рендерер поверх `WebViewRenderer` |
| `Event\SettingChanged` | PSR-14 событие после update/reset (secret-safe, actor = id текущего пользователя) |
| `Exception\InvalidSettingValueException` | Ошибка валидации типа |

Респондеры/процессоры `Service\*`, `Service\SettingsUrls` и
`Service\SettingsGridFactory` — `@internal`, автосвязываются через
`config/di.php`; хост обращается к `Yii\*\Action`-хендлерам, а не к ним.

Лейблы, группы, help-текст, choices и readonly-флаги настроек берутся из
`SettingDefinition` (`rasuvaeff/yii3-settings`), а не из UI-стороны карты.

## Безопасность

| Аспект | Поведение |
|---|---|
| Секретные значения | Никогда не рендерятся: список показывает `●●●● (set)` / `— (not set)`, редактирование показывает пустое password-поле |
| Пустой submit секрета | Сохраняет текущее значение — `set()` не вызывается |
| Ошибки валидации | Повторный рендер страницы редактирования с HTTP 200 |
| События | `SettingChanged` несёт `null`-значение для секретных ключей и опциональный `actor` |
| Настройки только для чтения | `Update`/`Reset` отклоняются с HTTP 403 |
| CSRF | Инфорсится middleware приложения; форма эмитит скрытое поле `_csrf`, если в запросе есть атрибут `csrf_token` |
| Вывод | Все значения проходят через `Yiisoft\Html\Html::encode()` / Html-виджеты / GridView-кодировку |

Крипто / шифрование at-rest обрабатывается в settings-пакетах, а не здесь — UI
видит только маскированное состояние секретов.

## Кастомизация views

Переопределите `views.list` и/или `views.edit` в params абсолютными путями к
своим шаблонам. Шаблоны получают те же переменные, что и bundled — см.
`resources/views/`.

Форма редактирования использует имена инпутов в скоупе `Setting[...]`
(например, `Setting[value]`). Кастомные edit-шаблоны обязаны сохранять этот
скоуп, иначе `SettingForm::fromParsedBody()` не заработает.

**Flash-сообщения** не встроены — пакет ничего не знает о сессии хоста.
Подпишитесь на `SettingChanged` в приложении, чтобы добавить flash-уведомления,
инвалидацию кэша или записи audit-трейла.

## Зачем нужен `SettingChanged`

Пакет эмитит `SettingChanged` после update/reset, чтобы хост-приложение могло
реагировать, не связывая себя с UI-actions. Типичные сценарии — инвалидация
кэша, audit-логирование, зависимая реконфигурация и observability-хуки. Поле
`actor` несёт id текущего пользователя; `null` — для гостей.
