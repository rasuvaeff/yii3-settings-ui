<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\Form\SettingForm;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;
use Yiisoft\Validator\Rule\Callback;

#[Test]
#[Covers(SettingForm::class)]
final class SettingFormTest
{
    public function readsValueFromScopedBody(): void
    {
        $form = SettingForm::fromParsedBody(['Setting' => ['value' => 'test@example.com']]);

        Assert::true($form->present);
        Assert::same($form->value, 'test@example.com');
    }

    public function absentWhenBodyIsNotArray(): void
    {
        $form = SettingForm::fromParsedBody(null);

        Assert::false($form->present);
        Assert::null($form->value);
    }

    public function absentWhenScopeMissing(): void
    {
        $form = SettingForm::fromParsedBody(['Other' => ['value' => 'x']]);

        Assert::false($form->present);
    }

    public function absentWhenValueKeyMissing(): void
    {
        $form = SettingForm::fromParsedBody(['Setting' => ['unrelated' => 'x']]);

        Assert::false($form->present);
    }

    public function ignoresNonValueFields(): void
    {
        $form = SettingForm::fromParsedBody(['Setting' => ['value' => 'v', 'key' => 'injected', 'isSecret' => '1']]);

        Assert::same($form->value, 'v');
    }

    #[DataProvider('blankProvider')]
    public function detectsBlank(bool $present, mixed $value, bool $expected): void
    {
        $form = new SettingForm(present: $present, value: $value);

        Assert::same($form->isBlank(), $expected);
    }

    /**
     * @return iterable<string, array{0: bool, 1: mixed, 2: bool}>
     */
    public static function blankProvider(): iterable
    {
        yield 'absent' => [false, null, true];
        yield 'present empty string' => [true, '', true];
        yield 'present null' => [true, null, true];
        yield 'present value' => [true, 'secret', false];
        yield 'present zero string' => [true, '0', false];
    }

    public function getRulesAreBuiltForValueWhenDefinitionProvided(): void
    {
        $definition = new SettingDefinition(key: 'orders.max_items', type: SettingType::Int);
        $form = new SettingForm(definition: $definition);

        $rules = [...$form->getRules()];

        Assert::array($rules)->hasKeys('value');
        $valueRules = $rules['value'];
        \assert(\is_array($valueRules));
        Assert::true((bool) $valueRules);
        Assert::true($valueRules[0] instanceof Callback);
    }

    public function getRulesEmptyWithoutDefinition(): void
    {
        $form = new SettingForm();

        Assert::same([...$form->getRules()], []);
    }
}
