<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\Exception\InvalidSettingValueException;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueValidator;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(SettingValueValidator::class)]
final class SettingValueValidatorTest
{
    private SettingValueValidator $validator;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->validator = new SettingValueValidator();
    }

    #[DataProvider('validProvider')]
    public function returnsNormalizedValue(SettingType $type, mixed $raw, mixed $expected): void
    {
        $definition = new SettingDefinition(key: 'app.setting', type: $type);

        Assert::same($this->validator->validate($definition, $raw), $expected);
    }

    /**
     * @return iterable<string, array{0: SettingType, 1: mixed, 2: mixed}>
     */
    public static function validProvider(): iterable
    {
        yield 'string passthrough' => [SettingType::String, 'hello', 'hello'];
        yield 'string from int' => [SettingType::String, 42, '42'];
        yield 'string from null' => [SettingType::String, null, ''];
        yield 'int from numeric string' => [SettingType::Int, '250', 250];
        yield 'int with surrounding spaces' => [SettingType::Int, ' -7 ', -7];
        yield 'int passthrough' => [SettingType::Int, 5, 5];
        yield 'float from string' => [SettingType::Float, '21.5', 21.5];
        yield 'float from int' => [SettingType::Float, 3, 3.0];
        yield 'bool checkbox on' => [SettingType::Bool, '1', true];
        yield 'bool unchecked' => [SettingType::Bool, null, false];
        yield 'bool literal true' => [SettingType::Bool, true, true];
        yield 'bool zero string' => [SettingType::Bool, '0', false];
        yield 'array from json object' => [SettingType::Array, '{"a":1}', ['a' => 1]];
        yield 'array from json list' => [SettingType::Array, '[1,2,3]', [1, 2, 3]];
        yield 'array passthrough' => [SettingType::Array, ['x' => 1], ['x' => 1]];
    }

    #[DataProvider('invalidProvider')]
    public function throwsOnInvalid(SettingType $type, mixed $raw): void
    {
        $definition = new SettingDefinition(key: 'app.setting', type: $type);

        try {
            $this->validator->validate($definition, $raw);
            Assert::fail('Expected InvalidSettingValueException');
        } catch (InvalidSettingValueException) {
            // Expected
        }
    }

    /**
     * @return iterable<string, array{0: SettingType, 1: mixed}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'int from word' => [SettingType::Int, 'abc'];
        yield 'int from float string' => [SettingType::Int, '1.5'];
        yield 'int from empty' => [SettingType::Int, ''];
        yield 'float from word' => [SettingType::Float, 'abc'];
        yield 'array from invalid json' => [SettingType::Array, 'not-json'];
        yield 'array from json scalar' => [SettingType::Array, '42'];
        yield 'string from array' => [SettingType::String, ['nope']];
    }

    public function errorMessageNeverEchoesValue(): void
    {
        $definition = new SettingDefinition(key: 'billing.stripe_key', type: SettingType::Int);

        try {
            $this->validator->validate($definition, 'sk_live_secret_value');
            Assert::fail('Expected InvalidSettingValueException');
        } catch (InvalidSettingValueException $e) {
            Assert::string($e->getMessage())->notContains('sk_live_secret_value');
            Assert::string($e->getMessage())->contains('billing.stripe_key');
        }
    }
}
