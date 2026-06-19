<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Validation;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;

/**
 * Builds yiisoft/validator rules encoding the accepted-value contract for a
 * setting's declared type. Messages are value-free so secret input never leaks.
 *
 * @internal
 */
final readonly class SettingValueRules
{
    /**
     * @return list<Callback>
     */
    public static function for(SettingDefinition $definition): array
    {
        $key = $definition->key;

        return match ($definition->type) {
            SettingType::String => [
                new Callback(static function (mixed $value) use ($key): Result {
                    if ($value === null || \is_scalar($value)) {
                        return new Result();
                    }

                    return (new Result())->addError(
                        sprintf('Setting "%s" must be a string, got %s', $key, get_debug_type($value)),
                    );
                }),
            ],
            SettingType::Int => [
                new Callback(static function (mixed $value) use ($key): Result {
                    if (\is_int($value)) {
                        return new Result();
                    }

                    if (\is_string($value)) {
                        $trimmed = trim($value);

                        // Reject non-integers, leading zeros and values outside the
                        // native int range, which `(int)` would silently clamp.
                        if ($trimmed !== '' && (string) (int) $trimmed === $trimmed) {
                            return new Result();
                        }
                    }

                    return (new Result())->addError(sprintf('Setting "%s" must be an integer', $key));
                }),
            ],
            SettingType::Float => [
                new Callback(static function (mixed $value) use ($key): Result {
                    if (\is_int($value) || \is_float($value)) {
                        return new Result();
                    }

                    if (\is_string($value) && is_numeric(trim($value))) {
                        return new Result();
                    }

                    return (new Result())->addError(sprintf('Setting "%s" must be a number', $key));
                }),
            ],
            SettingType::Bool => [],
            SettingType::Array => [
                new Callback(static function (mixed $value) use ($key): Result {
                    if (\is_array($value)) {
                        return new Result();
                    }

                    if (!\is_string($value)) {
                        return (new Result())->addError(
                            sprintf('Setting "%s" must be a JSON object or array', $key),
                        );
                    }

                    try {
                        $decoded = json_decode(json: $value, associative: true, flags: JSON_THROW_ON_ERROR);
                    } catch (\JsonException) {
                        return (new Result())->addError(sprintf('Setting "%s" must be valid JSON', $key));
                    }

                    if (!\is_array($decoded)) {
                        return (new Result())->addError(
                            sprintf('Setting "%s" must be a JSON object or array', $key),
                        );
                    }

                    return new Result();
                }),
            ],
        };
    }
}
