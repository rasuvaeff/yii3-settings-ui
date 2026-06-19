<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Yiisoft\Request\Body\RequestBodyParser;
use Yiisoft\Router\Route;

#[CoversClass(SettingsRoutes::class)]
final class SettingsRoutesTest extends TestCase
{
    #[Test]
    public function buildsFourRoutesWithDefaultNamesAndPrefix(): void
    {
        $routes = SettingsRoutes::create();

        $this->assertCount(4, $routes);

        $list = $routes[0];
        $edit = $routes[1];
        $update = $routes[2];
        $reset = $routes[3];

        $this->assertSame(SettingsRoutes::LIST, $list->getData('name'));
        $this->assertSame('/admin/settings', $list->getData('pattern'));
        $this->assertSame(['GET'], $list->getData('methods'));

        $this->assertSame(SettingsRoutes::EDIT, $edit->getData('name'));
        $this->assertSame('/admin/settings/{key}/edit', $edit->getData('pattern'));
        $this->assertSame(['GET'], $edit->getData('methods'));

        $this->assertSame(SettingsRoutes::UPDATE, $update->getData('name'));
        $this->assertSame('/admin/settings/{key}', $update->getData('pattern'));
        $this->assertSame(['POST'], $update->getData('methods'));

        $this->assertSame(SettingsRoutes::RESET, $reset->getData('name'));
        $this->assertSame('/admin/settings/{key}/reset', $reset->getData('pattern'));
        $this->assertSame(['POST'], $reset->getData('methods'));
    }

    #[Test]
    public function appliesCustomPrefixAndNames(): void
    {
        $routes = SettingsRoutes::create(
            prefix: '/settings',
            names: ['list' => 'admin/settings', 'edit' => 'admin/settings/edit'],
        );

        $this->assertSame('admin/settings', $routes[0]->getData('name'));
        $this->assertSame('/settings', $routes[0]->getData('pattern'));
        $this->assertSame('admin/settings/edit', $routes[1]->getData('name'));
        $this->assertSame('/settings/{key}/edit', $routes[1]->getData('pattern'));
        // Unspecified names fall back to defaults.
        $this->assertSame(SettingsRoutes::UPDATE, $routes[2]->getData('name'));
    }

    #[Test]
    public function getRoutesHaveNoExtraMiddlewaresByDefault(): void
    {
        $getRoutes = [SettingsRoutes::create()[0], SettingsRoutes::create()[1]];

        foreach ($getRoutes as $route) {
            $this->assertCount(1, $route->getData('enabledMiddlewares'));
        }
    }

    #[Test]
    public function postRoutesHaveBodyParserByDefault(): void
    {
        $postRoutes = [SettingsRoutes::create()[2], SettingsRoutes::create()[3]];

        foreach ($postRoutes as $route) {
            $this->assertContains(RequestBodyParser::class, $route->getData('enabledMiddlewares'));
        }
    }

    #[Test]
    public function withBodyParserFalseSkipsBodyParser(): void
    {
        foreach (SettingsRoutes::create(withBodyParser: false) as $route) {
            $this->assertCount(1, $route->getData('enabledMiddlewares'));
        }
    }

    #[Test]
    public function attachesAllMiddlewareToEveryRoute(): void
    {
        $mw = static fn(): string => 'noop';
        $routes = SettingsRoutes::create(middlewares: ['all' => [$mw]]);

        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $this->assertContains($mw, $route->getData('enabledMiddlewares'));
        }
    }

    #[Test]
    public function fromParamsReadsConfigFromParamsArray(): void
    {
        $routes = SettingsRoutes::fromParams([
            SettingsRoutes::PARAM_KEY => [
                'route_prefix' => '/my-settings',
                'route_names' => ['list' => 'my/settings/list'],
                'body_parser' => false,
            ],
        ]);

        $this->assertSame('/my-settings', $routes[0]->getData('pattern'));
        $this->assertSame('my/settings/list', $routes[0]->getData('name'));
        // body_parser: false — POST routes have only the action
        $this->assertCount(1, $routes[2]->getData('enabledMiddlewares'));
    }

    #[Test]
    public function fromParamsUsesDefaultsWhenConfigIsEmpty(): void
    {
        $routes = SettingsRoutes::fromParams([]);

        $this->assertSame('/admin/settings', $routes[0]->getData('pattern'));
        $this->assertSame(SettingsRoutes::LIST, $routes[0]->getData('name'));
    }
}
