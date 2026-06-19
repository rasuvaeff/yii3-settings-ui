<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Service;

use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * Generates settings URLs via the router (named routes), so links and redirects
 * stay correct regardless of the mount prefix / subdomain.
 *
 * @internal
 */
final readonly class SettingsUrls
{
    /**
     * @param array{list?: string, edit?: string, update?: string, reset?: string} $routeNames
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private array $routeNames = [],
    ) {}

    public function list(): string
    {
        return $this->urlGenerator->generate($this->name('list', SettingsRoutes::LIST));
    }

    public function edit(string $key): string
    {
        return $this->urlGenerator->generate($this->name('edit', SettingsRoutes::EDIT), ['key' => $key]);
    }

    public function update(string $key): string
    {
        return $this->urlGenerator->generate($this->name('update', SettingsRoutes::UPDATE), ['key' => $key]);
    }

    public function reset(string $key): string
    {
        return $this->urlGenerator->generate($this->name('reset', SettingsRoutes::RESET), ['key' => $key]);
    }

    private function name(string $key, string $default): string
    {
        $name = $this->routeNames[$key] ?? null;

        return \is_string($name) && $name !== '' ? $name : $default;
    }
}
