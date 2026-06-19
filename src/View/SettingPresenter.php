<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\View;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingState;

/**
 * @internal
 */
final readonly class SettingPresenter
{
    private const string MASKED_VALUE = "\u{25CF}\u{25CF}\u{25CF}\u{25CF}";

    public string $key;
    public string $label;
    public string $type;
    public string $source;
    public string $group;
    public bool $isSecret;
    public bool $hasStoredOverride;
    public bool $isWritable;
    public string $displayValue;
    public ?string $help;
    public bool $readonly;

    public function __construct(
        SettingDefinition $definition,
        SettingState $state,
        public string $editUrl,
    ) {
        $this->key = $state->key;
        $this->label = $definition->label ?? $state->key;
        $this->type = $definition->type->value;
        $this->source = $state->source;
        $this->group = $definition->group ?? \explode('.', $state->key, 2)[0];
        $this->isSecret = $state->isSecret;
        $this->hasStoredOverride = $state->hasStoredOverride;
        $this->isWritable = $state->isWritable;
        $this->help = $definition->help;
        $this->readonly = $definition->readonly;

        if ($state->isSecret) {
            $this->displayValue = $state->hasStoredOverride
                ? self::MASKED_VALUE . ' (set)'
                : "\u{2014} (not set)";
        } else {
            $this->displayValue = \is_scalar($state->effectiveValue) || $state->effectiveValue === null
                ? (string) $state->effectiveValue
                : json_encode($state->effectiveValue, JSON_THROW_ON_ERROR);
        }
    }
}
