<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingState;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\View\SettingPresenter;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SettingPresenter::class)]
final class SettingPresenterTest
{
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

        Assert::same($presenter->key, 'orders.max_items');
        Assert::same($presenter->label, 'Max items');
        Assert::same($presenter->type, 'int');
        Assert::same($presenter->source, 'db');
        Assert::same($presenter->group, 'Orders');
        Assert::same($presenter->help, 'Cart limit');
        Assert::same($presenter->editUrl, '/edit');
        Assert::false($presenter->readonly);
        Assert::true($presenter->hasStoredOverride);
        Assert::true($presenter->isWritable);
        Assert::same($presenter->displayValue, '250');
    }

    public function exposesReadonlyFlagFromDefinition(): void
    {
        $def = new SettingDefinition(key: 'app.locked', type: SettingType::String, default: 'fixed', readonly: true);
        $state = $this->state('app.locked', 'fixed', false, 'default', false);

        $presenter = new SettingPresenter($def, $state, '/edit');

        Assert::true($presenter->readonly);
    }

    public function fallsBackToKeyForLabelAndNamespaceGroupWhenMetadataAbsent(): void
    {
        $def = new SettingDefinition(key: 'mail.from', type: SettingType::String);
        $state = $this->state('mail.from', 'a@b.c', false, 'default', false);

        $presenter = new SettingPresenter($def, $state, '/edit');

        Assert::same($presenter->label, 'mail.from');
        Assert::same($presenter->group, 'mail');
    }

    public function masksSecretValueWhenOverrideExists(): void
    {
        $def = new SettingDefinition(key: 'billing.key', type: SettingType::String, secret: true);
        $state = $this->state('billing.key', 'sk_live_SECRET', true, 'db', true);

        $presenter = new SettingPresenter($def, $state, '/edit');

        Assert::string($presenter->displayValue)->notContains('sk_live_SECRET');
        Assert::string($presenter->displayValue)->contains('(set)');
        Assert::true($presenter->isSecret);
    }

    public function showsNotSetWhenSecretHasNoOverride(): void
    {
        $def = new SettingDefinition(key: 'billing.key', type: SettingType::String, secret: true);
        $state = $this->state('billing.key', null, false, 'default', true);

        $presenter = new SettingPresenter($def, $state, '/edit');

        Assert::string($presenter->displayValue)->contains('(not set)');
    }

    public function rendersNullScalarAsEmptyString(): void
    {
        $def = new SettingDefinition(key: 'app.title', type: SettingType::String);
        $state = $this->state('app.title', null, false, 'default', false);

        $presenter = new SettingPresenter($def, $state, '/edit');

        Assert::same($presenter->displayValue, '');
    }

    public function rendersArrayValueAsJson(): void
    {
        $def = new SettingDefinition(key: 'app.features', type: SettingType::Array, default: ['x' => 1]);
        $state = $this->state('app.features', ['x' => 1], false, 'default', false);

        $presenter = new SettingPresenter($def, $state, '/edit');

        Assert::same($presenter->displayValue, '{"x":1}');
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
