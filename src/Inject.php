<?php

declare(strict_types=1);

namespace SubstancePHP\Container;

use Psr\Container\ContainerInterface;

/**
 * Injects a function parameter value using the attribute's argument as the dependency id for looking up in
 * the container.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
readonly class Inject implements InjectionInterface
{
    public function __construct(public string $id)
    {
    }

    /** @inheritDoc */
    public function resolveValue(ContainerInterface $container): mixed
    {
        return $container->get($this->id);
    }
}
