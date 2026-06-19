<?php

declare(strict_types=1);

/**
 * Runnable demo of the yii3-settings-ui services driven over PSR-7, using
 * in-memory doubles instead of a database. Run it with:
 *
 *   docker run --rm -v "$PWD":/app -w /app composer:2 php examples/basic-usage.php
 *
 * It demonstrates: listing, a valid update, rejected invalid input (HTTP 200 re-render),
 * and the secret keep-current behaviour (a blank secret submit does not write).
 */

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingsInspector;
use Rasuvaeff\Yii3Settings\SettingState;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3Settings\WritableSettingsProvider;
use Rasuvaeff\Yii3SettingsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3SettingsUi\Renderer\ViewTemplateRenderer;
use Rasuvaeff\Yii3SettingsUi\Service\ListSettingsResponder;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsGridFactory;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsUrls;
use Rasuvaeff\Yii3SettingsUi\Service\UpdateSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueValidator;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Injector\Injector;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Validator\Validator;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\DataView\Filter\Factory\FilterFactoryInterface;
use Yiisoft\Yii\DataView\Filter\Factory\LikeFilterFactory;
use Yiisoft\Yii\DataView\ValuePresenter\SimpleValuePresenter;
use Yiisoft\Yii\DataView\ValuePresenter\ValuePresenterInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

require __DIR__ . '/../vendor/autoload.php';

/** In-memory provider that also acts as the read-model. */
$store = new class implements WritableSettingsProvider, SettingsInspector {
    /** @var array<string, SettingDefinition> */
    public array $definitions;

    /** @var array<string, mixed> */
    public array $values = [];

    public function __construct()
    {
        $this->definitions = [
            'orders.max_items' => new SettingDefinition('orders.max_items', SettingType::Int, 100),
            'billing.stripe_key' => new SettingDefinition('billing.stripe_key', SettingType::String, secret: true),
        ];
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? $this->definitions[$key]->default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->values[$key]);
    }

    public function describe(string $key): SettingState
    {
        $definition = $this->definitions[$key];
        $hasOverride = array_key_exists($key, $this->values);

        return new SettingState(
            key: $key,
            effectiveValue: $definition->isSecret() ? null : $this->get($key),
            hasStoredOverride: $hasOverride,
            source: $hasOverride ? 'db' : 'default',
            isSecret: $definition->isSecret(),
            isWritable: !$definition->readonly,
        );
    }

    public function describeAll(): array
    {
        return array_map(fn (string $key): SettingState => $this->describe($key), array_keys($this->definitions));
    }
};

$psr17 = new Psr17Factory();
$renderer = new ViewTemplateRenderer(
    new WebViewRenderer(
        responseFactory: $psr17,
        streamFactory: $psr17,
        aliases: new Aliases(),
        view: new WebView(),
    ),
);

$prefix = '/admin/settings';
$definitions = $store->definitions;
$validator = new SettingValueValidator();

/** Minimal router stand-in: maps the settings route names to paths under $prefix. */
$urlGenerator = new class ($prefix) implements UrlGeneratorInterface {
    public function __construct(private readonly string $prefix)
    {
    }

    public function generate(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null): string
    {
        $key = (string) ($arguments['key'] ?? '');

        return match ($name) {
            SettingsRoutes::EDIT => $this->prefix . '/' . rawurlencode($key) . '/edit',
            SettingsRoutes::UPDATE => $this->prefix . '/' . rawurlencode($key),
            SettingsRoutes::RESET => $this->prefix . '/' . rawurlencode($key) . '/reset',
            default => $this->prefix,
        };
    }

    public function generateAbsolute(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null, ?string $scheme = null, ?string $host = null): string
    {
        return $this->generate($name, $arguments);
    }

    public function generateFromCurrent(array $replacedArguments, array $queryParameters = [], ?string $hash = null, ?string $fallbackRouteName = null): string
    {
        return $this->prefix;
    }

    public function getUriPrefix(): string
    {
        return '';
    }

    public function setUriPrefix(string $name): void
    {
    }

    public function setDefaultArgument(string $name, bool|float|int|string|Stringable|null $value): void
    {
    }
};
$urls = new SettingsUrls($urlGenerator);

/**
 * Minimal PSR-11 container so the list's GridView can autowire its column
 * renderers (the host app normally supplies its own DI container via config/di.php).
 */
$container = new class implements ContainerInterface {
    private ?Injector $injector = null;

    /** @var array<string, object> */
    private array $explicit;

    public function __construct()
    {
        $this->explicit = [
            ContainerInterface::class => $this,
            ValidatorInterface::class => new Validator(),
            ValuePresenterInterface::class => new SimpleValuePresenter(),
            FilterFactoryInterface::class => new LikeFilterFactory(),
        ];
    }

    public function get(string $id): mixed
    {
        return $this->explicit[$id] ?? ($this->injector ??= new Injector($this))->make($id);
    }

    public function has(string $id): bool
    {
        return isset($this->explicit[$id])
            || (class_exists($id) && (new ReflectionClass($id))->isInstantiable());
    }
};

$editPage = new EditPageRenderer(
    renderer: $renderer,
    inspector: $store,
    urls: $urls,
    definitions: $definitions,
);
$list = new ListSettingsResponder(
    renderer: $renderer,
    settingsInspector: $store,
    urls: $urls,
    gridFactory: new SettingsGridFactory($container),
    definitions: $definitions,
);
$update = new UpdateSettingProcessor(
    settingsProvider: $store,
    responseFactory: $psr17,
    validator: $validator,
    editPage: $editPage,
    urls: $urls,
    definitions: $definitions,
);

$post = static fn (string $key, string $value) => $psr17
    ->createServerRequest('POST', $prefix . '/' . $key)
    ->withParsedBody(['Setting' => ['value' => $value]]);

echo 'LIST: HTTP ' . $list->respond()->getStatusCode() . "\n";

echo 'UPDATE int "250": HTTP ' . $update->process('orders.max_items', $post('orders.max_items', '250'))->getStatusCode()
    . ' => stored ' . var_export($store->get('orders.max_items'), true) . "\n";

echo 'UPDATE int "abc": HTTP ' . $update->process('orders.max_items', $post('orders.max_items', 'abc'))->getStatusCode()
    . ' (rejected, value unchanged: ' . var_export($store->get('orders.max_items'), true) . ")\n";

$store->set('billing.stripe_key', 'sk_live_existing');
echo 'UPDATE secret "": HTTP ' . $update->process('billing.stripe_key', $post('billing.stripe_key', ''))->getStatusCode()
    . ' (kept current secret: ' . (array_key_exists('billing.stripe_key', $store->values) ? 'yes' : 'no') . ")\n";

echo 'UPDATE secret "sk_new": HTTP ' . $update->process('billing.stripe_key', $post('billing.stripe_key', 'sk_new'))->getStatusCode()
    . ' (secret rotated)' . "\n";
