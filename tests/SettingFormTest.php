<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\Form\SettingForm;
use Yiisoft\Validator\Rule\Callback;

#[CoversClass(SettingForm::class)]
final class SettingFormTest extends TestCase
{
    #[Test]
    public function readsValueFromScopedBody(): void
    {
        $form = SettingForm::fromParsedBody(['Setting' => ['value' => 'test@example.com']]);

        $this->assertTrue($form->present);
        $this->assertSame('test@example.com', $form->value);
    }

    #[Test]
    public function absentWhenBodyIsNotArray(): void
    {
        $form = SettingForm::fromParsedBody(null);

        $this->assertFalse($form->present);
        $this->assertNull($form->value);
    }

    #[Test]
    public function absentWhenScopeMissing(): void
    {
        $form = SettingForm::fromParsedBody(['Other' => ['value' => 'x']]);

        $this->assertFalse($form->present);
    }

    #[Test]
    public function absentWhenValueKeyMissing(): void
    {
        $form = SettingForm::fromParsedBody(['Setting' => ['unrelated' => 'x']]);

        $this->assertFalse($form->present);
    }

    #[Test]
    public function ignoresNonValueFields(): void
    {
        $form = SettingForm::fromParsedBody(['Setting' => ['value' => 'v', 'key' => 'injected', 'isSecret' => '1']]);

        $this->assertSame('v', $form->value);
    }

    #[Test]
    #[DataProvider('blankProvider')]
    public function detectsBlank(bool $present, mixed $value, bool $expected): void
    {
        $form = new SettingForm(present: $present, value: $value);

        $this->assertSame($expected, $form->isBlank());
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

    #[Test]
    public function getRulesAreBuiltForValueWhenDefinitionProvided(): void
    {
        $definition = new SettingDefinition(key: 'orders.max_items', type: SettingType::Int);
        $form = new SettingForm(definition: $definition);

        $rules = [...$form->getRules()];

        $this->assertArrayHasKey('value', $rules);
        $valueRules = $rules['value'];
        \assert(\is_array($valueRules));
        $this->assertNotEmpty($valueRules);
        $this->assertContainsOnlyInstancesOf(Callback::class, $valueRules);
    }

    #[Test]
    public function getRulesEmptyWithoutDefinition(): void
    {
        $form = new SettingForm();

        $this->assertSame([], [...$form->getRules()]);
    }
}
