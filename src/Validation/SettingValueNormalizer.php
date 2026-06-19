<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Validation;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;

/**
 * Casts a value already accepted by {@see SettingValueRules} to the native PHP
 * type declared on the {@see SettingDefinition}.
 *
 * @internal
 */
final readonly class SettingValueNormalizer
{
    /**
     * @return int|float|string|bool|array<array-key, mixed>
     */
    public function normalize(SettingDefinition $definition, mixed $raw): int|float|string|bool|array
    {
        return match ($definition->type) {
            SettingType::String => $raw === null ? '' : (string) $raw,
            SettingType::Int => $this->normalizeInt($raw),
            SettingType::Float => $this->normalizeFloat($raw),
            SettingType::Bool => in_array($raw, [true, 1, '1', 'on', 'true'], true),
            SettingType::Array => $this->normalizeArray($raw),
        };
    }

    private function normalizeInt(mixed $raw): int
    {
        if (\is_int($raw)) {
            return $raw;
        }

        \assert(\is_string($raw));

        return (int) trim($raw);
    }

    private function normalizeFloat(mixed $raw): float
    {
        if (\is_int($raw) || \is_float($raw)) {
            return (float) $raw;
        }

        \assert(\is_string($raw));

        return (float) trim($raw);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function normalizeArray(mixed $raw): array
    {
        if (\is_array($raw)) {
            return $raw;
        }

        \assert(\is_string($raw));

        $decoded = json_decode(json: $raw, associative: true, flags: JSON_THROW_ON_ERROR);

        \assert(\is_array($decoded));

        return $decoded;
    }
}
