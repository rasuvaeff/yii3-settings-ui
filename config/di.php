<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Rasuvaeff\Yii3Settings\ConfigSettingsProvider;
use Rasuvaeff\Yii3Settings\SettingsInspector;
use Rasuvaeff\Yii3Settings\WritableSettingsProvider;
use Rasuvaeff\Yii3SettingsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Rasuvaeff\Yii3SettingsUi\Renderer\TemplateRendererInterface;
use Rasuvaeff\Yii3SettingsUi\Renderer\ViewTemplateRenderer;
use Rasuvaeff\Yii3SettingsUi\Service\EditSettingResponder;
use Rasuvaeff\Yii3SettingsUi\Service\ListSettingsResponder;
use Rasuvaeff\Yii3SettingsUi\Service\ResetSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsGridFactory;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsUrls;
use Rasuvaeff\Yii3SettingsUi\Service\UpdateSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueValidator;
use Rasuvaeff\Yii3SettingsUi\Yii\Edit\Action as YiiEditAction;
use Rasuvaeff\Yii3SettingsUi\Yii\List\Action as YiiListAction;
use Rasuvaeff\Yii3SettingsUi\Yii\Reset\Action as YiiResetAction;
use Rasuvaeff\Yii3SettingsUi\Yii\Update\Action as YiiUpdateAction;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/** @var array $params */

$definitions = ConfigSettingsProvider::normalizeDefinitions(
    $params['rasuvaeff/yii3-settings']['definitions'] ?? [],
);
$uiConfig = $params[SettingsRoutes::PARAM_KEY] ?? [];
$views = $uiConfig['views'] ?? [];
$layout = $uiConfig['layout'] ?? null;
$routeNames = \is_array($uiConfig['route_names'] ?? null) ? $uiConfig['route_names'] : [];

return [
    TemplateRendererInterface::class => static fn (WebViewRenderer $renderer): ViewTemplateRenderer => new ViewTemplateRenderer(
        renderer: $renderer,
        layout: is_string($layout) ? $layout : null,
        views: is_array($views) ? $views : [],
    ),
    SettingValueValidator::class => SettingValueValidator::class,

    SettingsUrls::class => static fn (UrlGeneratorInterface $urlGenerator): SettingsUrls => new SettingsUrls(
        urlGenerator: $urlGenerator,
        routeNames: $routeNames,
    ),

    EditPageRenderer::class => static fn (
        TemplateRendererInterface $renderer,
        SettingsInspector $inspector,
        SettingsUrls $urls,
    ): EditPageRenderer => new EditPageRenderer(
        renderer: $renderer,
        inspector: $inspector,
        urls: $urls,
        definitions: $definitions,
    ),

    SettingsGridFactory::class => static fn (ContainerInterface $container): SettingsGridFactory => new SettingsGridFactory(
        container: $container,
    ),

    ListSettingsResponder::class => static fn (
        TemplateRendererInterface $renderer,
        SettingsInspector $settingsInspector,
        SettingsUrls $urls,
        SettingsGridFactory $gridFactory,
    ): ListSettingsResponder => new ListSettingsResponder(
        renderer: $renderer,
        settingsInspector: $settingsInspector,
        urls: $urls,
        gridFactory: $gridFactory,
        definitions: $definitions,
    ),

    EditSettingResponder::class => static fn (
        EditPageRenderer $editPage,
        ResponseFactoryInterface $responseFactory,
    ): EditSettingResponder => new EditSettingResponder(
        editPage: $editPage,
        responseFactory: $responseFactory,
        definitions: $definitions,
    ),

    UpdateSettingProcessor::class => static fn (
        WritableSettingsProvider $settingsProvider,
        ResponseFactoryInterface $responseFactory,
        SettingValueValidator $validator,
        EditPageRenderer $editPage,
        SettingsUrls $urls,
        CurrentUser $currentUser,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): UpdateSettingProcessor => new UpdateSettingProcessor(
        settingsProvider: $settingsProvider,
        responseFactory: $responseFactory,
        validator: $validator,
        editPage: $editPage,
        urls: $urls,
        definitions: $definitions,
        currentUser: $currentUser,
        eventDispatcher: $eventDispatcher,
    ),

    ResetSettingProcessor::class => static fn (
        WritableSettingsProvider $settingsProvider,
        ResponseFactoryInterface $responseFactory,
        SettingsUrls $urls,
        CurrentUser $currentUser,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): ResetSettingProcessor => new ResetSettingProcessor(
        settingsProvider: $settingsProvider,
        responseFactory: $responseFactory,
        urls: $urls,
        definitions: $definitions,
        currentUser: $currentUser,
        eventDispatcher: $eventDispatcher,
    ),

    YiiListAction::class => YiiListAction::class,
    YiiEditAction::class => YiiEditAction::class,
    YiiUpdateAction::class => YiiUpdateAction::class,
    YiiResetAction::class => YiiResetAction::class,
];
