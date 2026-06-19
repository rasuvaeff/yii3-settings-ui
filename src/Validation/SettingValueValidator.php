<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Validation;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3SettingsUi\Exception\InvalidSettingValueException;
use Yiisoft\Validator\Validator;
use Yiisoft\Validator\ValidatorInterface;

/**
 * Validates and normalizes submitted input against a setting's declared type
 * before it reaches the writable provider.
 *
 * Validation is delegated to yiisoft/validator rules built by
 * {@see SettingValueRules}; casting to the native type is performed by
 * {@see SettingValueNormalizer}. Error messages are value-free (key only) so
 * secret input never leaks.
 *
 * @api
 */
final readonly class SettingValueValidator
{
    public function __construct(
        private ValidatorInterface $validator = new Validator(),
        private SettingValueNormalizer $normalizer = new SettingValueNormalizer(),
    ) {}

    /**
     * @throws InvalidSettingValueException
     *
     * @return int|float|string|bool|array<array-key, mixed>
     */
    public function validate(SettingDefinition $definition, mixed $raw): int|float|string|bool|array
    {
        $result = $this->validator->validate($raw, SettingValueRules::for($definition));

        if (!$result->isValid()) {
            $messages = $result->getErrorMessages();

            throw new InvalidSettingValueException($messages[0] ?? 'Invalid value');
        }

        return $this->normalizer->normalize($definition, $raw);
    }
}
