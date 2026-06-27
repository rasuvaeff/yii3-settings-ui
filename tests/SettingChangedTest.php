<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use Rasuvaeff\Yii3SettingsUi\Event\SettingChanged;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SettingChanged::class)]
final class SettingChangedTest
{
    public function updatedNonSecretCarriesValue(): void
    {
        $event = SettingChanged::updated(key: 'mail.from', isSecret: false, value: 'new@example.com', actor: 'user-1');

        Assert::same($event->key, 'mail.from');
        Assert::same($event->operation, SettingChanged::OPERATION_UPDATED);
        Assert::false($event->isSecret);
        Assert::same($event->value, 'new@example.com');
        Assert::same($event->actor, 'user-1');
    }

    public function updatedSecretNeverCarriesValue(): void
    {
        $event = SettingChanged::updated(key: 'billing.stripe_key', isSecret: true, value: 'sk_live_secret');

        Assert::true($event->isSecret);
        Assert::null($event->value);
        Assert::null($event->actor);
    }

    public function resetCarriesNoValue(): void
    {
        $event = SettingChanged::reset(key: 'mail.from', isSecret: false, actor: 'user-1');

        Assert::same($event->operation, SettingChanged::OPERATION_RESET);
        Assert::null($event->value);
        Assert::same($event->actor, 'user-1');
    }

    public function constructorDefaults(): void
    {
        $event = new SettingChanged(key: 'mail.from', operation: SettingChanged::OPERATION_RESET);

        Assert::false($event->isSecret);
        Assert::null($event->value);
        Assert::null($event->actor);
    }
}
