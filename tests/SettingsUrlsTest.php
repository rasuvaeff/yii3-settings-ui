<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use Rasuvaeff\Yii3SettingsUi\Service\SettingsUrls;
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeUrlGenerator;
use Stringable;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;

#[Test]
#[Covers(SettingsUrls::class)]
final class SettingsUrlsTest
{
    public function generatesUrlsForDefaultRouteNames(): void
    {
        $urls = new SettingsUrls(urlGenerator: new FakeUrlGenerator());

        Assert::same($urls->list(), '/admin/settings');
        Assert::same($urls->edit('mail.from'), '/admin/settings/mail.from/edit');
        Assert::same($urls->update('mail.from'), '/admin/settings/mail.from');
        Assert::same($urls->reset('mail.from'), '/admin/settings/mail.from/reset');
    }

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

        Assert::same(
            $recorder->names,
            ['admin/settings', 'admin/settings/edit', SettingsRoutes::UPDATE],
        );
    }
}
