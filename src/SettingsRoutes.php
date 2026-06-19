<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi;

use Rasuvaeff\Yii3SettingsUi\Yii\Edit\Action as EditAction;
use Rasuvaeff\Yii3SettingsUi\Yii\List\Action as ListAction;
use Rasuvaeff\Yii3SettingsUi\Yii\Reset\Action as ResetAction;
use Rasuvaeff\Yii3SettingsUi\Yii\Update\Action as UpdateAction;
use Yiisoft\Request\Body\RequestBodyParser;
use Yiisoft\Router\Route;

/**
 * Builds the four settings routes.
 *
 * **Flat-routes (auto via config-plugin)** — add to `configuration.php`:
 * ```php
 * 'routes' => 'vendor/rasuvaeff/yii3-settings-ui/config/routes.php',
 * ```
 *
 * **Group-based admin panel (most common):**
 * ```php
 * Group::create(prefix: '/admin')->routes(
 *     ...SettingsRoutes::fromParams($params),
 * );
 * ```
 *
 * POST routes (update, reset) automatically include `RequestBodyParser`.
 * Set `'body_parser' => false` in params to opt out when your pipeline already applies it.
 *
 * @api
 */
final class SettingsRoutes
{
    public const string PARAM_KEY = 'rasuvaeff/yii3-settings-ui';

    public const string LIST = 'settings/list';
    public const string EDIT = 'settings/edit';
    public const string UPDATE = 'settings/update';
    public const string RESET = 'settings/reset';

    private const string DEFAULT_PREFIX = '/admin/settings';

    /**
     * @param array{list?: string, edit?: string, update?: string, reset?: string} $names
     * @param array{
     *     all?: array<array-key, mixed>,
     *     list?: array<array-key, mixed>,
     *     edit?: array<array-key, mixed>,
     *     update?: array<array-key, mixed>,
     *     reset?: array<array-key, mixed>,
     * } $middlewares
     * @param bool $withBodyParser Add RequestBodyParser to POST routes automatically.
     *
     * @return list<Route>
     */
    public static function create(
        string $prefix = self::DEFAULT_PREFIX,
        array $names = [],
        array $middlewares = [],
        bool $withBodyParser = true,
    ): array {
        $all = self::normalizeList($middlewares['all'] ?? []);
        $bp = $withBodyParser ? [RequestBodyParser::class] : [];

        return [
            self::build(
                Route::get($prefix),
                [...$all, ...self::normalizeList($middlewares['list'] ?? [])],
                ListAction::class,
                self::name($names, 'list', self::LIST),
            ),
            self::build(
                Route::get($prefix . '/{key}/edit'),
                [...$all, ...self::normalizeList($middlewares['edit'] ?? [])],
                EditAction::class,
                self::name($names, 'edit', self::EDIT),
            ),
            self::build(
                Route::post($prefix . '/{key}'),
                [...$all, ...$bp, ...self::normalizeList($middlewares['update'] ?? [])],
                UpdateAction::class,
                self::name($names, 'update', self::UPDATE),
            ),
            self::build(
                Route::post($prefix . '/{key}/reset'),
                [...$all, ...$bp, ...self::normalizeList($middlewares['reset'] ?? [])],
                ResetAction::class,
                self::name($names, 'reset', self::RESET),
            ),
        ];
    }

    /**
     * Creates routes from the application params array, reading config from $params[self::PARAM_KEY].
     *
     * Typical use inside a Group-based admin router:
     * ```php
     * Group::create(prefix: '/admin')->routes(...SettingsRoutes::fromParams($params));
     * ```
     *
     * @param array<string, mixed> $params
     * @return list<Route>
     */
    public static function fromParams(array $params): array
    {
        /** @var array<string, mixed> $config */
        $config = $params[self::PARAM_KEY] ?? [];

        /** @var mixed $rawPrefix */
        $rawPrefix = $config['route_prefix'] ?? null;
        /** @var mixed $rawNames */
        $rawNames = $config['route_names'] ?? null;
        /** @var mixed $rawMiddlewares */
        $rawMiddlewares = $config['middlewares'] ?? null;
        /** @var mixed $rawBodyParser */
        $rawBodyParser = $config['body_parser'] ?? null;

        /** @var array{list?: string, edit?: string, update?: string, reset?: string} $names */
        $names = \is_array($rawNames) ? $rawNames : [];
        /** @var array{all?: array<array-key, mixed>, list?: array<array-key, mixed>, edit?: array<array-key, mixed>, update?: array<array-key, mixed>, reset?: array<array-key, mixed>} $middlewares */
        $middlewares = \is_array($rawMiddlewares) ? $rawMiddlewares : [];

        return self::create(
            prefix: \is_string($rawPrefix) ? $rawPrefix : self::DEFAULT_PREFIX,
            names: $names,
            middlewares: $middlewares,
            withBodyParser: $rawBodyParser !== false,
        );
    }

    /**
     * @param array<array-key, mixed> $middlewares
     * @param class-string $action
     */
    private static function build(Route $route, array $middlewares, string $action, string $name): Route
    {
        foreach ($middlewares as $middleware) {
            \assert(\is_string($middleware) || \is_callable($middleware) || \is_array($middleware));
            $route = $route->middleware($middleware);
        }

        return $route->action($action)->name($name);
    }

    /**
     * @param array{list?: string, edit?: string, update?: string, reset?: string} $names
     */
    private static function name(array $names, string $key, string $default): string
    {
        $name = $names[$key] ?? null;

        return \is_string($name) && $name !== '' ? $name : $default;
    }

    /**
     * @return list<mixed>
     */
    private static function normalizeList(mixed $value): array
    {
        return \is_array($value) ? array_values($value) : [];
    }
}
