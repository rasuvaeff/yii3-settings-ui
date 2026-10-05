<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rasuvaeff\Understudy\Arg;
use Rasuvaeff\Understudy\Captor;
use Rasuvaeff\Understudy\Invocation;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingsInspector;
use Rasuvaeff\Yii3Settings\SettingState;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3Settings\WritableSettingsProvider;
use Rasuvaeff\Yii3SettingsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3SettingsUi\Renderer\TemplateRendererInterface;
use Rasuvaeff\Yii3SettingsUi\Service\EditSettingResponder;
use Rasuvaeff\Yii3SettingsUi\Service\ListSettingsResponder;
use Rasuvaeff\Yii3SettingsUi\Service\ResetSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsGridFactory;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsUrls;
use Rasuvaeff\Yii3SettingsUi\Service\UpdateSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\TestContainer;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueValidator;
use Testo\Lifecycle\BeforeTest;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\User\CurrentUser;

use function Rasuvaeff\Understudy\when;

abstract class ActionTestCase
{
    protected Psr17Factory $http;

    private Captor $renderParameters;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->http = new Psr17Factory();
    }

    /**
     * @return array<string, SettingDefinition>
     */
    protected function definitions(): array
    {
        return [
            'mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String, default: 'noreply@example.com'),
            'orders.max_items' => new SettingDefinition(key: 'orders.max_items', type: SettingType::Int, default: 100),
            'mail.enabled' => new SettingDefinition(key: 'mail.enabled', type: SettingType::Bool, default: true),
            'app.features' => new SettingDefinition(key: 'app.features', type: SettingType::Array, default: ['search' => true]),
            'billing.stripe_key' => new SettingDefinition(key: 'billing.stripe_key', type: SettingType::String, secret: true),
            'app.locked' => new SettingDefinition(key: 'app.locked', type: SettingType::String, default: 'fixed', readonly: true),
        ];
    }

    protected function urls(): SettingsUrls
    {
        $generator = Understudy::for(UrlGeneratorInterface::class);
        when(fn() => $generator->generate(SettingsRoutes::LIST))->returns('/admin/settings');

        return new SettingsUrls(urlGenerator: $generator);
    }

    /**
     * @return array<string, SettingState>
     */
    protected function states(): array
    {
        return [
            'mail.from' => $this->state('mail.from', 'admin@example.com', true, 'db', false),
            'orders.max_items' => $this->state('orders.max_items', 100, false, 'default', false),
            'mail.enabled' => $this->state('mail.enabled', true, false, 'default', false),
            'app.features' => $this->state('app.features', ['search' => true], false, 'default', false),
            'billing.stripe_key' => $this->state('billing.stripe_key', null, true, 'db', true),
            'app.locked' => $this->state('app.locked', 'fixed', false, 'default', false),
        ];
    }

    protected function inspector(): SettingsInspector
    {
        $inspector = Understudy::for(SettingsInspector::class);
        $states = $this->states();

        foreach ($states as $key => $state) {
            when(fn() => $inspector->describe($key))->returns($state);
        }

        when(fn() => $inspector->describeAll())->returns(array_values($states));

        return $inspector;
    }

    /**
     * @param int|null|string|true|true[] $value
     *
     * @psalm-param 'admin@example.com'|'fixed'|100|array{search: true}|null|true $value
     */
    protected function state(string $key, array|string|int|bool|null $value, bool $override, string $source, bool $secret): SettingState
    {
        return new SettingState(
            key: $key,
            effectiveValue: $value,
            hasStoredOverride: $override,
            source: $source,
            isSecret: $secret,
            isWritable: true,
        );
    }

    protected function renderer(): TemplateRendererInterface
    {
        $renderer = Understudy::for(TemplateRendererInterface::class);
        $this->renderParameters = Arg::captor();
        when(fn() => $renderer->render(Arg::any(), $this->renderParameters->capture()))
            ->answers(fn(Invocation $call) => $this->http->createResponse(200));

        return $renderer;
    }

    /**
     * @return array<string, mixed>
     */
    protected function renderedParameters(): array
    {
        /** @var array<string, mixed> $parameters */
        $parameters = $this->renderParameters->last();

        return $parameters;
    }

    protected function editPage(TemplateRendererInterface $renderer): EditPageRenderer
    {
        return new EditPageRenderer(
            renderer: $renderer,
            inspector: $this->inspector(),
            urls: $this->urls(),
            definitions: $this->definitions(),
        );
    }

    protected function listResponder(TemplateRendererInterface $renderer): ListSettingsResponder
    {
        return new ListSettingsResponder(
            renderer: $renderer,
            settingsInspector: $this->inspector(),
            urls: $this->urls(),
            gridFactory: new SettingsGridFactory(new TestContainer()),
            definitions: $this->definitions(),
        );
    }

    protected function editResponder(TemplateRendererInterface $renderer): EditSettingResponder
    {
        return new EditSettingResponder(
            editPage: $this->editPage($renderer),
            responseFactory: $this->http,
            definitions: $this->definitions(),
        );
    }

    protected function updateProcessor(
        WritableSettingsProvider $provider,
        TemplateRendererInterface $renderer,
        ?CurrentUser $currentUser = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): UpdateSettingProcessor {
        return new UpdateSettingProcessor(
            settingsProvider: $provider,
            responseFactory: $this->http,
            validator: new SettingValueValidator(),
            editPage: $this->editPage($renderer),
            urls: $this->urls(),
            definitions: $this->definitions(),
            currentUser: $currentUser ?? $this->currentUser(null),
            eventDispatcher: $eventDispatcher ?? Understudy::for(EventDispatcherInterface::class),
        );
    }

    protected function resetProcessor(
        WritableSettingsProvider $provider,
        ?CurrentUser $currentUser = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): ResetSettingProcessor {
        return new ResetSettingProcessor(
            settingsProvider: $provider,
            responseFactory: $this->http,
            urls: $this->urls(),
            definitions: $this->definitions(),
            currentUser: $currentUser ?? $this->currentUser(null),
            eventDispatcher: $eventDispatcher ?? Understudy::for(EventDispatcherInterface::class),
        );
    }

    protected function currentUser(?string $id): CurrentUser
    {
        $currentUser = new CurrentUser(
            identityRepository: Understudy::for(IdentityRepositoryInterface::class),
            eventDispatcher: Understudy::for(EventDispatcherInterface::class),
        );

        if ($id !== null) {
            $currentUser->overrideIdentity(new readonly class ($id) implements IdentityInterface {
                public function __construct(private string $id) {}

                #[\Override]
                public function getId(): string
                {
                    return $this->id;
                }
            });
        }

        return $currentUser;
    }

    /**
     * @param array<string, mixed>|null $parsedBody
     */
    protected function request(string $method, ?array $parsedBody = null): ServerRequestInterface
    {
        $request = $this->http->createServerRequest($method, '/admin/settings');

        if ($parsedBody !== null) {
            $request = $request->withParsedBody($parsedBody);
        }

        return $request;
    }
}
