<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Double;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Injector\Injector;

/**
 * Minimal PSR-11 container for dispatch tests: an explicit service map plus
 * autowiring of concrete classes through yiisoft/injector, mirroring what a
 * host application without any ParametersResolverInterface wiring provides to
 * the middleware factory.
 */
final class RouterDispatchContainer implements ContainerInterface
{
    private ?Injector $injector = null;

    /**
     * @param array<string, mixed> $services
     */
    public function __construct(
        private array $services = [],
    ) {}

    public function add(string $id, mixed $service): void
    {
        $this->services[$id] = $service;
    }

    #[\Override]
    public function get(string $id): mixed
    {
        if (\array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }

        if (class_exists($id) && (new \ReflectionClass($id))->isInstantiable()) {
            return ($this->injector ??= new Injector($this))->make($id);
        }

        throw new class ($id) extends \Exception implements NotFoundExceptionInterface {
            public function __construct(string $id)
            {
                parent::__construct("No service registered for \"$id\"");
            }
        };
    }

    #[\Override]
    public function has(string $id): bool
    {
        if (\array_key_exists($id, $this->services)) {
            return true;
        }

        if (interface_exists($id)) {
            return false;
        }

        if (class_exists($id)) {
            return (new \ReflectionClass($id))->isInstantiable();
        }

        return false;
    }
}
