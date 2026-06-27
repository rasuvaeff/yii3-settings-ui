<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Double;

use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;

/**
 * @internal
 */
final class FakeIdentityRepository implements IdentityRepositoryInterface
{
    #[\Override]
    public function findIdentity(string $id): ?IdentityInterface
    {
        return null;
    }
}
