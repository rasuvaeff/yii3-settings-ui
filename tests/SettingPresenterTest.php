<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingState;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\View\SettingPresenter;

#[CoversClass(SettingPresenter::class)]
final class SettingPresenterTest extends TestCase
{
    #[Test]
    public function mapsDefinitionAndStateToViewFields(): void
    {
        $def = new SettingDefinition(
            key: 'orders.max_items',
            type: SettingType::Int,
            default: 100,
            label: 'Max items',
            group: 'Orders',
            help: 'Cart limit',
        );
        $state = $this->state('orders.max_items', 250, true, 'db', false);

        $presenter = new SettingPresenter($def, $state, '/edit');

        $this->assertSame('orders.max_items', $presenter->key);
        $this->assertSame('Max items', $presenter->label);
        $this->assertSame('int', $presenter->type);
        $this->assertSame('db', $presenter->source);
        $this->assertSame('Orders', $presenter->group);
        $this->assertSame('Cart limit', $presenter->help);
        $this->assertSame('/edit', $presenter->editUrl);
        $this->assertFalse($presenter->readonly);
        $this->assertTrue($presenter->hasStoredOverride);
        $this->assertTrue($presenter->isWritable);
        $this->assertSame('250', $presenter->displayValue);
    }

    #[Test]
    public function exposesReadonlyFlagFromDefinition(): void
    {
        $def = new SettingDefinition(key: 'app.locked', type: SettingType::String, default: 'fixed', readonly: true);
        $state = $this->state('app.locked', 'fixed', false, 'default', false);

        $presenter = new SettingPresenter($def, $state, '/edit');

        $this->assertTrue($presenter->readonly);
    }

    #[Test]
    public function fallsBackToKeyForLabelAndNamespaceGroupWhenMetadataAbsent(): void
    {
        $def = new SettingDefinition(key: 'mail.from', type: SettingType::String);
        $state = $this->state('mail.from', 'a@b.c', false, 'default', false);

        $presenter = new SettingPresenter($def, $state, '/edit');

        $this->assertSame('mail.from', $presenter->label);
        $this->assertSame('mail', $presenter->group);
    }

    #[Test]
    public function masksSecretValueWhenOverrideExists(): void
    {
        $def = new SettingDefinition(key: 'billing.key', type: SettingType::String, secret: true);
        $state = $this->state('billing.key', 'sk_live_SECRET', true, 'db', true);

        $presenter = new SettingPresenter($def, $state, '/edit');

        $this->assertStringNotContainsString('sk_live_SECRET', $presenter->displayValue);
        $this->assertStringContainsString('(set)', $presenter->displayValue);
        $this->assertTrue($presenter->isSecret);
    }

    #[Test]
    public function showsNotSetWhenSecretHasNoOverride(): void
    {
        $def = new SettingDefinition(key: 'billing.key', type: SettingType::String, secret: true);
        $state = $this->state('billing.key', null, false, 'default', true);

        $presenter = new SettingPresenter($def, $state, '/edit');

        $this->assertStringContainsString('(not set)', $presenter->displayValue);
    }

    #[Test]
    public function rendersNullScalarAsEmptyString(): void
    {
        $def = new SettingDefinition(key: 'app.title', type: SettingType::String);
        $state = $this->state('app.title', null, false, 'default', false);

        $presenter = new SettingPresenter($def, $state, '/edit');

        $this->assertSame('', $presenter->displayValue);
    }

    #[Test]
    public function rendersArrayValueAsJson(): void
    {
        $def = new SettingDefinition(key: 'app.features', type: SettingType::Array, default: ['x' => 1]);
        $state = $this->state('app.features', ['x' => 1], false, 'default', false);

        $presenter = new SettingPresenter($def, $state, '/edit');

        $this->assertSame('{"x":1}', $presenter->displayValue);
    }

    private function state(string $key, mixed $value, bool $override, string $source, bool $secret): SettingState
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
}
