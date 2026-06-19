<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3SettingsUi\Event\SettingChanged;

#[CoversClass(SettingChanged::class)]
final class SettingChangedTest extends TestCase
{
    #[Test]
    public function updatedNonSecretCarriesValue(): void
    {
        $event = SettingChanged::updated(key: 'mail.from', isSecret: false, value: 'new@example.com', actor: 'user-1');

        $this->assertSame('mail.from', $event->key);
        $this->assertSame(SettingChanged::OPERATION_UPDATED, $event->operation);
        $this->assertFalse($event->isSecret);
        $this->assertSame('new@example.com', $event->value);
        $this->assertSame('user-1', $event->actor);
    }

    #[Test]
    public function updatedSecretNeverCarriesValue(): void
    {
        $event = SettingChanged::updated(key: 'billing.stripe_key', isSecret: true, value: 'sk_live_secret');

        $this->assertTrue($event->isSecret);
        $this->assertNull($event->value);
        $this->assertNull($event->actor);
    }

    #[Test]
    public function resetCarriesNoValue(): void
    {
        $event = SettingChanged::reset(key: 'mail.from', isSecret: false, actor: 'user-1');

        $this->assertSame(SettingChanged::OPERATION_RESET, $event->operation);
        $this->assertNull($event->value);
        $this->assertSame('user-1', $event->actor);
    }

    #[Test]
    public function constructorDefaults(): void
    {
        $event = new SettingChanged(key: 'mail.from', operation: SettingChanged::OPERATION_RESET);

        $this->assertFalse($event->isSecret);
        $this->assertNull($event->value);
        $this->assertNull($event->actor);
    }
}
