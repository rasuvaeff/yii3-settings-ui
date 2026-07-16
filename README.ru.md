# rasuvaeff/yii3-settings-ui
[![Latest Stable Version](https://poser.pugx.org/rasuvaeff/yii3-settings-ui/v)](https://packagist.org/packages/rasuvaeff/yii3-settings-ui)
[![Total Downloads](https://poser.pugx.org/rasuvaeff/yii3-settings-ui/downloads)](https://packagist.org/packages/rasuvaeff/yii3-settings-ui)
[![Build](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/build.yml/badge.svg)](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/build.yml)
[![Static analysis](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/static-analysis.yml)
[![Psalm level](https://img.shields.io/badge/psalm-level_1-blue.svg)](https://github.com/rasuvaeff/yii3-settings-ui/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-settings-ui/php)](https://packagist.org/packages/rasuvaeff/yii3-settings-ui)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-blue.svg)](LICENSE.md)
Панель пользовательского интерфейса администратора для управления настройками среды выполнения Yii3.

 > Используете помощника по программированию с искусственным интеллектом? [llms.txt](llms.txt) содержит компактную ссылку на API, которой вы можете поделиться с моделью. @@ЛИНИЯ@@
A drop-in admin panel for [`rasuvaeff/yii3-settings`](https://github.com/rasuvaeff/yii3-settings):
перечислять настройки времени выполнения в сортируемой сетке, редактировать их с проверкой каждого типа,
 сбрасывать переопределения обратно в конфигурацию/по умолчанию и безопасно обрабатывать секретные значения (маскированные,
 никогда не отображаются в виде открытого текста). @@ЛИНИЯ@@
## Требования
- PHP 8.3+
 - `rasuvaeff/yii3-settings` ^1.0 - определения, `SettingsInspector`, `WritableSettingsProvider`
 - `yiisoft/yii-view-renderer`, `yiisoft/router`, `yiisoft/user`
 - `yiisoft/html`, `yiisoft/validator`, `yiisoft/form-model`, `yiisoft/data`, `yiisoft/yii-dataview`
 - Конкретная реализация маршрутизатора (например, `yiisoft/router-fastroute`) - предоставляется вашим приложением
 - Bootstrap 5 CSS, загружаемый хост-приложением (представления используют классы Bootstrap, нет встроенных стилей)

 Сетка списка отображается на стороне сервера из контейнера DI приложения
 (`SettingsGridFactory`), поэтому хосту **не** требуется загружать
 `WidgetFactory`. @@ЛИНИЯ@@
## Установка
```bash
composer require rasuvaeff/yii3-settings-ui
```
## Использование
В пакет входит подключение конфигурационного плагина Yii3 (`di`, `params`). Добавьте свои параметры
 в цепочку слияния:

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
`layout` управляет общей оболочкой. «views.list» и «views.edit» переопределяют только соответствующие шаблоны; они не заменяют макет.

 Привяжите контракты настроек к своему провайдеру. С `rasuvaeff/yii3-settings-db` ^1.0
 это происходит автоматически — его конфигурационный плагин связывает `WritableSettingsProvider`,
 `SettingsProvider` и `SettingsInspector` с одним и тем же `DbSettingsProvider`. Для пользовательского бэкэнда
 свяжите их самостоятельно:

```php
use Rasuvaeff\Yii3Settings\{SettingsInspector, WritableSettingsProvider};
use Rasuvaeff\Yii3SettingsDb\DbSettingsProvider;

return [
    WritableSettingsProvider::class => DbSettingsProvider::class,
    SettingsInspector::class => DbSettingsProvider::class,
];
```
## Маршруты
| Метод | Путь | Действие | Имя по умолчанию |
 |---|---|---|---|
 | ПОЛУЧИТЬ | `{префикс}` | `Yii\Список\Действие` | `настройки/список` |
 | ПОЛУЧИТЬ | `{префикс}/{ключ}/редактировать` | `Yii\Редактировать\Действие` | `настройки/редактировать` |
 | ПОСТ | `{префикс}/{ключ}` | `Yii\Обновление\Действие` | `настройки/обновление` |
 | ПОСТ | `{префикс}/{ключ}/сброс` | `Yii\Сброс\Действие` | `настройки/сброс` |

 `middlewares.{all,list,edit,update,reset}` — добавьте промежуточное ПО для каждого слота, не разветвляя маршруты. `RequestBodyParser` автоматически добавляется в маршруты POST (обновление, сброс); установите `'body_parser' => false` в параметрах, чтобы отказаться.

 URL-адреса и перенаправления генерируются через маршрутизатор («UrlGeneratorInterface») по имени маршрута; ссылки остаются корректными под любым префиксом или поддоменом. Переопределите `route_names` в параметрах, если ваше приложение использует другое соглашение об именах. @@ЛИНИЯ@@
### Плоская проводка
Подключите связанный `config/routes.php` явно в `configuration.php`:

```php
'routes' => 'vendor/rasuvaeff/yii3-settings-ui/config/routes.php',
```
Префикс маршрута, имена и промежуточное ПО считываются из параметров (`SettingsRoutes::PARAM_KEY`). @@ЛИНИЯ@@
### Групповая админ-панель
Внутри группы (типичный подход для административной области с общим префиксом):

```php
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Yiisoft\Router\Group;

Group::create(prefix: '/admin')->routes(
    ...SettingsRoutes::fromParams($params),
);
```
`fromParams()` считывает префикс, имена, промежуточное ПО и отказ от парсера тела из
 `$params[SettingsRoutes::PARAM_KEY]`, поэтому регистрация маршрута и генерация URL `SettingsUrls`
 всегда синхронизируются.

 Для полного контроля над именами используйте create() напрямую и добавьте соответствующие `route_names` к параметрам:

```php
SettingsRoutes::create(
    prefix: '/settings',
    names: ['list' => 'admin/settings', 'edit' => 'admin/settings/edit'],
    middlewares: ['all' => [AuthMiddleware::class]],
)
```
## Авторизация
Пакет не обеспечивает внутренний контроль доступа. Защитите маршруты с помощью «middlewares.all» (или ключей для каждого маршрута). Пакет обеспечивает внедрение CurrentUser только для событий аудита. @@ЛИНИЯ@@
## Публичный API
| Класс | Описание |
 |---|---|
 | `НастройкиМаршруты` | Строит 4 маршрута; `fromParams($params)` для групповых панелей,`create()` для полного управления |
 | `Yii\Список\Действие` | GET плоская сетка (группа/ключ/значение/тип/источник), секреты скрыты |
 | `Yii\Редактировать\Действие` | GET форма редактирования с учетом типа |
 | `Yii\Обновление\Действие` | POST проверка + сохранение, повторная обработка при неверном вводе |
 | `Yii\Сброс\Действие` | POST удалить переопределение -> config/default |
 | `Форма\SettingForm` | Отправленные данные редактирования (`настоящее` + `значение`) |
 | `Проверка\SettingValueValidator` | Проверяет + нормализует отправленные входные данные по типу настройки (сообщения об ошибках без значений) |
 | `Рендерер\TemplateRendererInterface` | Рендеринг шва (тестируемые действия) |
 | `Рендерер\ViewTemplateRenderer` | Средство рендеринга по умолчанию через `WebViewRenderer` |
 | `Событие\SettingChanged` | Событие PSR-14 после обновления/сброса (секретно, актер = текущий идентификатор пользователя) |
 | `Exception\InvalidSettingValueException` | Ошибка проверки типа |

 Ответчики/процессоры `Service\*`, `Service\SettingsUrls` и
 `Service\SettingsGridFactory` являются `@internal` — автоматически подключаются через
 `config/di.php`; хост ссылается на обработчики `Yii\*\Action`, а не на них.

 Метки настроек, группы, текст справки, варианты выбора и флаги только для чтения берутся из
 `SettingDefinition` (`rasuvaeff/yii3-settings`), а не из карты пользовательского интерфейса. @@ЛИНИЯ@@
## Безопасность
| Концерн | Поведение |
 |---|---|
 | Тайные ценности | Никогда не отображается: в списке отображается `͵ƒƒ (установлено)` / `— (не установлено)`, при редактировании отображается пустое поле пароля |
 | Пустой секрет отправить | Сохраняет текущее значение — `set()` не вызывается |
 | Ошибки проверки | Повторно отображает страницу редактирования с помощью HTTP 200 |
 | События | `SettingChanged` содержит значение `null` для секретных ключей и необязательного `actor` |
 | Настройки только для чтения | `Обновление`/`Сброс` отклонено с помощью HTTP 403 |
 | CSRF | Применяется промежуточным программным обеспечением вашего приложения; форма выдает скрытое поле `_csrf`, когда присутствует атрибут запроса `csrf_token` |
 | Выход | Все значения проходят через `Yiisoft\Html\Html::encode()` / виджеты Html / кодировку GridView |

 Криптографическое шифрование/шифрование в состоянии покоя обрабатывается пакетами настроек, а не здесь — пользовательский интерфейс видит только замаскированное состояние секретов. @@ЛИНИЯ@@
## Настройка представлений
Переопределите в параметрах `views.list` и/или `views.edit` абсолютные пути к вашим собственным шаблонам. Шаблоны получают те же переменные, что и встроенные — см. «resources/views/».

 В форме редактирования используются имена входных данных, ограниченные областью `Setting[...]` (например, `Setting[value]`). Пользовательские шаблоны редактирования должны сохранять эту область действия, чтобы SettingForm::fromParsedBody() работал.

 **Flash-сообщения** не встроены — пакет не знает о сеансе хост-приложения. Подпишитесь на SettingChanged в своем приложении, чтобы добавлять флэш-уведомления, аннулирование кеша или записи контрольного журнала. @@ЛИНИЯ@@
## Почему существует «SettingChanged»
Пакет выдает `SettingChanged` после обновления/сброса, чтобы ведущее приложение могло реагировать, не связываясь с действиями пользовательского интерфейса. Типичное использование — аннулирование кэша, ведение журнала аудита, зависимая реконфигурация и перехватчики наблюдения. Поле `actor` содержит текущий идентификатор пользователя; `null` для гостей.
