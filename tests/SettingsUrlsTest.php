<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsUrls;
use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;

use function Rasuvaeff\Understudy\when;

#[Test]
#[Covers(SettingsUrls::class)]
final class SettingsUrlsTest
{
    public function generatesUrlsForDefaultRouteNames(): void
    {
        $generator = Understudy::for(UrlGeneratorInterface::class);
        when(fn() => $generator->generate(SettingsRoutes::LIST))->returns('/admin/settings');
        when(fn() => $generator->generate(SettingsRoutes::EDIT, ['key' => 'mail.from']))->returns('/admin/settings/mail.from/edit');
        when(fn() => $generator->generate(SettingsRoutes::UPDATE, ['key' => 'mail.from']))->returns('/admin/settings/mail.from');
        when(fn() => $generator->generate(SettingsRoutes::RESET, ['key' => 'mail.from']))->returns('/admin/settings/mail.from/reset');

        $urls = new SettingsUrls(urlGenerator: $generator);

        Assert::same($urls->list(), '/admin/settings');
        Assert::same($urls->edit('mail.from'), '/admin/settings/mail.from/edit');
        Assert::same($urls->update('mail.from'), '/admin/settings/mail.from');
        Assert::same($urls->reset('mail.from'), '/admin/settings/mail.from/reset');
    }

    public function forwardsConfiguredRouteNamesToGenerator(): void
    {
        $generator = Understudy::for(UrlGeneratorInterface::class);

        $urls = new SettingsUrls(
            urlGenerator: $generator,
            routeNames: ['list' => 'admin/settings', 'edit' => 'admin/settings/edit'],
        );

        $urls->list();
        $urls->edit('k');
        $urls->update('k');

        Understudy::verifySequence(
            fn() => $generator->generate('admin/settings'),
            fn() => $generator->generate('admin/settings/edit', ['key' => 'k']),
            fn() => $generator->generate(SettingsRoutes::UPDATE, ['key' => 'k']),
        );
    }
}
