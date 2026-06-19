<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Double;

use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Stringable;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * Deterministic URL generator over the default settings route names, mounted at
 * `/admin/settings`. Lets tests assert link/redirect targets without a router.
 */
final class FakeUrlGenerator implements UrlGeneratorInterface
{
    private string $uriPrefix = '';

    #[\Override]
    public function generate(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null): string
    {
        $key = (string) ($arguments['key'] ?? '');

        return match ($name) {
            SettingsRoutes::LIST => '/admin/settings',
            SettingsRoutes::EDIT => '/admin/settings/' . rawurlencode($key) . '/edit',
            SettingsRoutes::UPDATE => '/admin/settings/' . rawurlencode($key),
            SettingsRoutes::RESET => '/admin/settings/' . rawurlencode($key) . '/reset',
            default => '/' . $name,
        };
    }

    #[\Override]
    public function generateAbsolute(string $name, array $arguments = [], array $queryParameters = [], ?string $hash = null, ?string $scheme = null, ?string $host = null): string
    {
        return 'https://example.test' . $this->generate($name, $arguments, $queryParameters, $hash);
    }

    #[\Override]
    public function generateFromCurrent(array $replacedArguments, array $queryParameters = [], ?string $hash = null, ?string $fallbackRouteName = null): string
    {
        return $this->generate($fallbackRouteName ?? SettingsRoutes::LIST, $replacedArguments, $queryParameters, $hash);
    }

    #[\Override]
    public function getUriPrefix(): string
    {
        return $this->uriPrefix;
    }

    #[\Override]
    public function setUriPrefix(string $name): void
    {
        $this->uriPrefix = $name;
    }

    #[\Override]
    public function setDefaultArgument(string $name, bool|float|int|string|Stringable|null $value): void {}
}
