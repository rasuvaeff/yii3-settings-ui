<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Event;

/**
 * Dispatched after a setting is updated or reset through the admin UI.
 *
 * For secret settings `value` is always `null` — the plaintext is never carried
 * in the event, only the fact that the key changed.
 *
 * `actor` carries the current user ID when the host application provides one.
 *
 * @api
 */
final readonly class SettingChanged
{
    public const string OPERATION_UPDATED = 'updated';

    public const string OPERATION_RESET = 'reset';

    public function __construct(
        public string $key,
        public string $operation,
        public bool $isSecret = false,
        public mixed $value = null,
        public ?string $actor = null,
    ) {}

    public static function updated(string $key, bool $isSecret, mixed $value, ?string $actor = null): self
    {
        return new self(
            key: $key,
            operation: self::OPERATION_UPDATED,
            isSecret: $isSecret,
            value: $isSecret ? null : $value,
            actor: $actor,
        );
    }

    public static function reset(string $key, bool $isSecret, ?string $actor = null): self
    {
        return new self(
            key: $key,
            operation: self::OPERATION_RESET,
            isSecret: $isSecret,
            actor: $actor,
        );
    }
}
