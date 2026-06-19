<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Double;

use Rasuvaeff\Yii3Settings\WritableSettingsProvider;

final class RecordingWritableProvider implements WritableSettingsProvider
{
    /**
     * @var array<string, mixed>
     */
    public array $setCalls = [];

    /**
     * @var list<string>
     */
    public array $removeCalls = [];

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private array $values = [],
    ) {}

    #[\Override]
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->values);
    }

    #[\Override]
    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    #[\Override]
    public function set(string $key, mixed $value): void
    {
        $this->setCalls[$key] = $value;
        $this->values[$key] = $value;
    }

    #[\Override]
    public function remove(string $key): void
    {
        $this->removeCalls[] = $key;
        unset($this->values[$key]);
    }
}
