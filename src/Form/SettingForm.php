<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Form;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueRules;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\RulesProviderInterface;

/**
 * Submitted edit input for a single setting.
 *
 * Only the `value` field is user-controlled; the key comes from the route and
 * the type/secret flags come from the {@see SettingDefinition}. `present`
 * distinguishes "field absent" (e.g. unchecked checkbox, no submission) from
 * "submitted empty" — which matters for secret keep-current semantics.
 *
 * @api
 */
final class SettingForm extends FormModel implements RulesProviderInterface
{
    public function __construct(public bool $present = false, public mixed $value = null, private readonly ?SettingDefinition $definition = null) {}

    /**
     * Validation rules keyed by property. The `value` rules depend on the
     * setting's declared type, so a definition must be supplied for the form to
     * self-validate.
     */
    #[\Override]
    public function getRules(): iterable
    {
        return $this->definition instanceof SettingDefinition
            ? ['value' => SettingValueRules::for($this->definition)]
            : [];
    }

    /**
     * Builds the form from a parsed request body using the `Setting[value]` field.
     */
    public static function fromParsedBody(array|object|null $body, ?SettingDefinition $definition = null): self
    {
        if (!\is_array($body)) {
            return new self(definition: $definition);
        }

        $scope = $body['Setting'] ?? null;
        if (!\is_array($scope) || !\array_key_exists('value', $scope)) {
            return new self(definition: $definition);
        }

        /** @var mixed $value */
        $value = $scope['value'];

        return new self(present: true, value: $value, definition: $definition);
    }

    public function isBlank(): bool
    {
        return !$this->present || $this->value === null || $this->value === '';
    }
}
