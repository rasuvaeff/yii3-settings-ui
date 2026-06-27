<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueNormalizer;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(SettingValueNormalizer::class)]
final class SettingValueNormalizerTest
{
    private SettingValueNormalizer $normalizer;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->normalizer = new SettingValueNormalizer();
    }

    #[DataProvider('castProvider')]
    public function castsToNativeType(SettingType $type, mixed $raw, mixed $expected): void
    {
        $definition = new SettingDefinition(key: 'app.setting', type: $type);

        Assert::same($this->normalizer->normalize($definition, $raw), $expected);
    }

    /**
     * @return iterable<string, array{0: SettingType, 1: mixed, 2: mixed}>
     */
    public static function castProvider(): iterable
    {
        yield 'string passthrough' => [SettingType::String, 'hello', 'hello'];
        yield 'string from int' => [SettingType::String, 42, '42'];
        yield 'string from null to empty' => [SettingType::String, null, ''];
        yield 'int passthrough' => [SettingType::Int, 5, 5];
        yield 'int from numeric string' => [SettingType::Int, '250', 250];
        yield 'int trims surrounding spaces' => [SettingType::Int, ' -7 ', -7];
        yield 'float from int' => [SettingType::Float, 3, 3.0];
        yield 'float from numeric string' => [SettingType::Float, '21.5', 21.5];
        yield 'bool checkbox on' => [SettingType::Bool, '1', true];
        yield 'bool unchecked null' => [SettingType::Bool, null, false];
        yield 'bool literal true' => [SettingType::Bool, true, true];
        yield 'bool zero string' => [SettingType::Bool, '0', false];
        yield 'array passthrough' => [SettingType::Array, ['x' => 1], ['x' => 1]];
        yield 'array from json object' => [SettingType::Array, '{"a":1}', ['a' => 1]];
        yield 'array from json list' => [SettingType::Array, '[1,2,3]', [1, 2, 3]];
    }
}
