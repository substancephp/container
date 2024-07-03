<?php

namespace SubstancePHP\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Designed for implementing by attribute classes that control how they determine values for dependency injection.
 */
interface InjectionInterface
{
    /**
     * Given a DI container, resolve the value to inject for a given instance.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolveValue(ContainerInterface $container): mixed;
}