<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Double;

use Rasuvaeff\Yii3Settings\SettingsInspector;
use Rasuvaeff\Yii3Settings\SettingState;

final readonly class FakeInspector implements SettingsInspector
{
    /**
     * @param array<string, SettingState> $states
     */
    public function __construct(
        private array $states,
    ) {}

    #[\Override]
    public function describe(string $key): SettingState
    {
        return $this->states[$key];
    }

    /**
     * @return list<SettingState>
     */
    #[\Override]
    public function describeAll(): array
    {
        return array_values($this->states);
    }
}
