<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsUrls;
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeUrlGenerator;
use Stringable;
use Yiisoft\Router\UrlGeneratorInterface;

#[CoversClass(SettingsUrls::class)]
final class SettingsUrlsTest extends TestCase
{
    #[Test]
    public function generatesUrlsForDefaultRouteNames(): void
    {
        $urls = new SettingsUrls(urlGenerator: new FakeUrlGenerator());

        $this->assertSame('/admin/settings', $urls->list());
        $this->assertSame('/admin/settings/mail.from/edit', $urls->edit('mail.from'));
        $this->assertSame('/admin/settings/mail.from', $urls->update('mail.from'));
        $this->assertSame('/admin/settings/mail.from/reset', $urls->reset('mail.from'));
    }

    #[Test]
    public function forwardsConfiguredRouteNamesToGenerator(): void
    {
        $recorder = new class implements UrlGeneratorInterface {
            /** @var list<string> */
            public array $names = [];

            #[\Override]
            public function generate(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null): string
            {
                $this->names[] = $name;

                return '/';
            }

            #[\Override]
            public function generateAbsolute(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null, ?string $scheme = null, ?string $host = null): string
            {
                return '/';
            }

            #[\Override]
            public function generateFromCurrent(array $replacedArguments, array $queryParameters = [], ?string $hash = null, ?string $fallbackRouteName = null): string
            {
                return '/';
            }

            #[\Override]
            public function getUriPrefix(): string
            {
                return '';
            }

            #[\Override]
            public function setUriPrefix(string $name): void {}

            #[\Override]
            public function setDefaultArgument(string $name, bool|float|int|string|Stringable|null $value): void {}
        };

        $urls = new SettingsUrls(
            urlGenerator: $recorder,
            routeNames: ['list' => 'admin/settings', 'edit' => 'admin/settings/edit'],
        );

        $urls->list();
        $urls->edit('k');
        $urls->update('k');

        $this->assertSame(
            ['admin/settings', 'admin/settings/edit', SettingsRoutes::UPDATE],
            $recorder->names,
        );
    }
}
