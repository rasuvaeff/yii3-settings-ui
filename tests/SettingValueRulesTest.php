<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueRules;
use Yiisoft\Validator\Validator;

#[CoversClass(SettingValueRules::class)]
final class SettingValueRulesTest extends TestCase
{
    private Validator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    #[Test]
    #[DataProvider('acceptedProvider')]
    public function acceptsValidValue(SettingType $type, mixed $raw): void
    {
        $definition = new SettingDefinition(key: 'app.setting', type: $type);

        $result = $this->validator->validate($raw, SettingValueRules::for($definition));

        $this->assertTrue($result->isValid());
    }

    /**
     * @return iterable<string, array{0: SettingType, 1: mixed}>
     */
    public static function acceptedProvider(): iterable
    {
        yield 'string' => [SettingType::String, 'hello'];
        yield 'string from int' => [SettingType::String, 42];
        yield 'string from null' => [SettingType::String, null];
        yield 'int' => [SettingType::Int, 5];
        yield 'int from numeric string' => [SettingType::Int, '250'];
        yield 'int with surrounding spaces' => [SettingType::Int, ' -7 '];
        yield 'float from int' => [SettingType::Float, 3];
        yield 'float from numeric string' => [SettingType::Float, '21.5'];
        yield 'bool anything' => [SettingType::Bool, null];
        yield 'array passthrough' => [SettingType::Array, ['x' => 1]];
        yield 'array from json object' => [SettingType::Array, '{"a":1}'];
        yield 'array from json list' => [SettingType::Array, '[1,2,3]'];
    }

    #[Test]
    #[DataProvider('rejectedProvider')]
    public function rejectsInvalidValue(SettingType $type, mixed $raw, string $expectedMessageFragment): void
    {
        $definition = new SettingDefinition(key: 'app.setting', type: $type);

        $result = $this->validator->validate($raw, SettingValueRules::for($definition));

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString($expectedMessageFragment, $result->getErrorMessages()[0] ?? '');
    }

    /**
     * @return iterable<string, array{0: SettingType, 1: mixed, 2: string}>
     */
    public static function rejectedProvider(): iterable
    {
        yield 'int from word' => [SettingType::Int, 'abc', 'must be an integer'];
        yield 'int from float string' => [SettingType::Int, '1.5', 'must be an integer'];
        yield 'int from empty' => [SettingType::Int, '', 'must be an integer'];
        yield 'int overflowing PHP_INT_MAX' => [SettingType::Int, '99999999999999999999', 'must be an integer'];
        yield 'float from word' => [SettingType::Float, 'abc', 'must be a number'];
        yield 'string from array' => [SettingType::String, ['nope'], 'must be a string'];
        yield 'array from invalid json' => [SettingType::Array, 'not-json', 'must be valid JSON'];
        yield 'array from json scalar' => [SettingType::Array, '42', 'must be a JSON object or array'];
        yield 'array from non-string non-array' => [SettingType::Array, 7, 'must be a JSON object or array'];
    }

    #[Test]
    public function errorMessageCarriesKeyNotValue(): void
    {
        $definition = new SettingDefinition(key: 'billing.stripe_key', type: SettingType::Int);

        $result = $this->validator->validate('sk_live_secret_value', SettingValueRules::for($definition));

        $this->assertFalse($result->isValid());
        $message = $result->getErrorMessages()[0] ?? '';
        $this->assertStringContainsString('billing.stripe_key', $message);
        $this->assertStringNotContainsString('sk_live_secret_value', $message);
    }

    #[Test]
    public function boolYieldsNoRules(): void
    {
        $definition = new SettingDefinition(key: 'app.flag', type: SettingType::Bool);

        $this->assertSame([], SettingValueRules::for($definition));
    }
}
