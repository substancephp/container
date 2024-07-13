<?php

namespace TestUtil\Fixtures;

use Psr\Container\ContainerInterface;
use SubstancePHP\Container\InjectionInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class DummyCustomInject implements InjectionInterface
{
    public function __construct(private readonly string $id)
    {
    }

    public function resolveValue(ContainerInterface $container, \ReflectionParameter $parameter): mixed
    {
        return "$this->id|$this->id";
    }
}