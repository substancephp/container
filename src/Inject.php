<?php

declare(strict_types=1);

namespace SubstancePHP\Container;

use Psr\Container\ContainerInterface;

/**
 * Injects a function parameter value using the attribute's argument as the dependency id for looking up in
 * the container, or the parameter name, if that argument is not provided.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
readonly class Inject implements InjectionInterface
{
    public function __construct(public ?string $id = null)
    {
    }

    /** @inheritDoc */
    public function resolveValue(ContainerInterface $container, \ReflectionParameter $parameter): mixed
    {
        return $container->get($this->id ?? $parameter->getName());
    }
}
