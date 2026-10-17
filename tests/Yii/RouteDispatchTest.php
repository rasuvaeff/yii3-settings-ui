<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Yii;

use Nyholm\Psr7\ServerRequest;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rasuvaeff\Yii3Settings\SettingsInspector;
use Rasuvaeff\Yii3Settings\WritableSettingsProvider;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Renderer\TemplateRendererInterface;
use Rasuvaeff\Yii3SettingsUi\Service\EditSettingResponder;
use Rasuvaeff\Yii3SettingsUi\Service\ResetSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Service\UpdateSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Rasuvaeff\Yii3SettingsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\ParameterEchoRenderer;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RouterDispatchContainer;
use Rasuvaeff\Yii3SettingsUi\Yii\Edit\Action as EditAction;
use Rasuvaeff\Yii3SettingsUi\Yii\Reset\Action as ResetAction;
use Rasuvaeff\Yii3SettingsUi\Yii\Update\Action as UpdateAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlMatcher;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollector;

/**
 * Regression test for #15: real requests dispatched through yiisoft/router
 * with a plain MiddlewareDispatcher — the middleware factory carries NO
 * ParametersResolverInterface (the yiisoft/input-http wiring the yiisoft/app
 * template ships). The actions must read the `{key}` route argument from the
 * injected CurrentRoute, not from hydrator-attribute parameter resolution;
 * with the attribute every route with an argument failed because the action
 * was never invocable.
 */
#[Test]
#[Covers(EditAction::class)]
#[Covers(UpdateAction::class)]
#[Covers(ResetAction::class)]
final class RouteDispatchTest extends ActionTestCase
{
    public function editRouteArgumentReachesTheTemplateWithoutParametersResolver(): void
    {
        $response = $this->dispatch(new ServerRequest('GET', '/admin/settings/mail.from/edit'));

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::string((string) $response->getBody())->contains('mail.from');
    }

    public function unknownEditKeyIsNotFound(): void
    {
        $response = $this->dispatch(new ServerRequest('GET', '/admin/settings/missing.key/edit'));

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
    }

    public function updateRouteWritesTheSettingCarriedByTheRouteArgument(): void
    {
        $provider = new RecordingWritableProvider();
        $response = $this->dispatch(
            new ServerRequest(
                'POST',
                '/admin/settings/orders.max_items',
                ['Content-Type' => 'application/json'],
                '{"Setting":{"value":"250"}}',
            ),
            provider: $provider,
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($provider->setCalls['orders.max_items'] ?? null, 250);
    }

    public function resetRouteRemovesTheOverrideCarriedByTheRouteArgument(): void
    {
        $provider = new RecordingWritableProvider(values: ['mail.from' => 'admin@example.com']);
        $response = $this->dispatch(
            new ServerRequest('POST', '/admin/settings/mail.from/reset'),
            provider: $provider,
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::contains($provider->removeCalls, 'mail.from');
    }

    private function dispatch(
        ServerRequestInterface $request,
        ?RecordingWritableProvider $provider = null,
    ): ResponseInterface {
        $provider ??= new RecordingWritableProvider();
        $currentRoute = new CurrentRoute();
        $renderer = new ParameterEchoRenderer(responseFactory: $this->http);

        $container = new RouterDispatchContainer([
            CurrentRoute::class => $currentRoute,
            TemplateRendererInterface::class => $renderer,
            SettingsInspector::class => $this->inspector(),
            ResponseFactoryInterface::class => $this->http,
            WritableSettingsProvider::class => $provider,
            EditSettingResponder::class => $this->editResponder($renderer),
            UpdateSettingProcessor::class => $this->updateProcessor(provider: $provider, renderer: $renderer),
            ResetSettingProcessor::class => $this->resetProcessor(provider: $provider),
        ]);
        $container->add(ContainerInterface::class, $container);

        $collector = new RouteCollector();

        foreach (SettingsRoutes::create() as $route) {
            $collector->addRoute($route);
        }

        $router = new Router(
            matcher: new UrlMatcher(new RouteCollection($collector)),
            responseFactory: $this->http,
            middlewareFactory: new MiddlewareFactory($container),
            currentRoute: $currentRoute,
        );

        return $router->process($request, new readonly class implements RequestHandlerInterface {
            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('No route matched the dispatched request');
            }
        });
    }
}
