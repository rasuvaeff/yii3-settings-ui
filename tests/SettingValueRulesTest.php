<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueRules;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Validator\Validator;

#[Test]
#[Covers(SettingValueRules::class)]
final class SettingValueRulesTest
{
    private Validator $validator;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->validator = new Validator();
    }

    #[DataProvider('acceptedProvider')]
    public function acceptsValidValue(SettingType $type, mixed $raw): void
    {
        $definition = new SettingDefinition(key: 'app.setting', type: $type);

        $result = $this->validator->validate($raw, SettingValueRules::for($definition));

        Assert::true($result->isValid());
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

    #[DataProvider('rejectedProvider')]
    public function rejectsInvalidValue(SettingType $type, mixed $raw, string $expectedMessageFragment): void
    {
        $definition = new SettingDefinition(key: 'app.setting', type: $type);

        $result = $this->validator->validate($raw, SettingValueRules::for($definition));

        Assert::false($result->isValid());
        Assert::string($result->getErrorMessages()[0] ?? '')->contains($expectedMessageFragment);
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

    public function errorMessageCarriesKeyNotValue(): void
    {
        $definition = new SettingDefinition(key: 'billing.stripe_key', type: SettingType::Int);

        $result = $this->validator->validate('sk_live_secret_value', SettingValueRules::for($definition));

        Assert::false($result->isValid());
        $message = $result->getErrorMessages()[0] ?? '';
        Assert::string($message)->contains('billing.stripe_key');
        Assert::string($message)->notContains('sk_live_secret_value');
    }

    public function boolYieldsNoRules(): void
    {
        $definition = new SettingDefinition(key: 'app.flag', type: SettingType::Bool);

        Assert::same(SettingValueRules::for($definition), []);
    }
}
