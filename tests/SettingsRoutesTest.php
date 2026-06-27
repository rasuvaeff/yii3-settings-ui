<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Yiisoft\Request\Body\RequestBodyParser;
use Yiisoft\Router\Route;

#[Test]
#[Covers(SettingsRoutes::class)]
final class SettingsRoutesTest
{
    public function buildsFourRoutesWithDefaultNamesAndPrefix(): void
    {
        $routes = SettingsRoutes::create();

        Assert::count($routes, 4);

        $list = $routes[0];
        $edit = $routes[1];
        $update = $routes[2];
        $reset = $routes[3];

        Assert::same($list->getData('name'), SettingsRoutes::LIST);
        Assert::same($list->getData('pattern'), '/admin/settings');
        Assert::same($list->getData('methods'), ['GET']);

        Assert::same($edit->getData('name'), SettingsRoutes::EDIT);
        Assert::same($edit->getData('pattern'), '/admin/settings/{key}/edit');
        Assert::same($edit->getData('methods'), ['GET']);

        Assert::same($update->getData('name'), SettingsRoutes::UPDATE);
        Assert::same($update->getData('pattern'), '/admin/settings/{key}');
        Assert::same($update->getData('methods'), ['POST']);

        Assert::same($reset->getData('name'), SettingsRoutes::RESET);
        Assert::same($reset->getData('pattern'), '/admin/settings/{key}/reset');
        Assert::same($reset->getData('methods'), ['POST']);
    }

    public function appliesCustomPrefixAndNames(): void
    {
        $routes = SettingsRoutes::create(
            prefix: '/settings',
            names: ['list' => 'admin/settings', 'edit' => 'admin/settings/edit'],
        );

        Assert::same($routes[0]->getData('name'), 'admin/settings');
        Assert::same($routes[0]->getData('pattern'), '/settings');
        Assert::same($routes[1]->getData('name'), 'admin/settings/edit');
        Assert::same($routes[1]->getData('pattern'), '/settings/{key}/edit');
        // Unspecified names fall back to defaults.
        Assert::same($routes[2]->getData('name'), SettingsRoutes::UPDATE);
    }

    public function getRoutesHaveNoExtraMiddlewaresByDefault(): void
    {
        $getRoutes = [SettingsRoutes::create()[0], SettingsRoutes::create()[1]];

        foreach ($getRoutes as $route) {
            Assert::count($route->getData('enabledMiddlewares'), 1);
        }
    }

    public function postRoutesHaveBodyParserByDefault(): void
    {
        $postRoutes = [SettingsRoutes::create()[2], SettingsRoutes::create()[3]];

        foreach ($postRoutes as $route) {
            Assert::contains($route->getData('enabledMiddlewares'), RequestBodyParser::class);
        }
    }

    public function withBodyParserFalseSkipsBodyParser(): void
    {
        foreach (SettingsRoutes::create(withBodyParser: false) as $route) {
            Assert::count($route->getData('enabledMiddlewares'), 1);
        }
    }

    public function attachesAllMiddlewareToEveryRoute(): void
    {
        $mw = static fn(): string => 'noop';
        $routes = SettingsRoutes::create(middlewares: ['all' => [$mw]]);

        foreach ($routes as $route) {
            Assert::instanceOf($route, Route::class);
            Assert::contains($route->getData('enabledMiddlewares'), $mw);
        }
    }

    public function fromParamsReadsConfigFromParamsArray(): void
    {
        $routes = SettingsRoutes::fromParams([
            SettingsRoutes::PARAM_KEY => [
                'route_prefix' => '/my-settings',
                'route_names' => ['list' => 'my/settings/list'],
                'body_parser' => false,
            ],
        ]);

        Assert::same($routes[0]->getData('pattern'), '/my-settings');
        Assert::same($routes[0]->getData('name'), 'my/settings/list');
        // body_parser: false — POST routes have only the action
        Assert::count($routes[2]->getData('enabledMiddlewares'), 1);
    }

    public function fromParamsUsesDefaultsWhenConfigIsEmpty(): void
    {
        $routes = SettingsRoutes::fromParams([]);

        Assert::same($routes[0]->getData('pattern'), '/admin/settings');
        Assert::same($routes[0]->getData('name'), SettingsRoutes::LIST);
    }
}
