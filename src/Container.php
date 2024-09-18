<?php

declare(strict_types=1);

namespace SubstancePHP\Container;

use SubstancePHP\Container\Exception\AutowireException;
use SubstancePHP\Container\Exception\DependencyNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class Container implements ContainerInterface
{
    private readonly ?ContainerInterface $parent;

    /**
     * @var array<string, \Closure(self $c, string $id): mixed>
     */
    private readonly array $factories;

    /**
     * @var array<string, mixed> a store of lazily initialized singletons
     */
    private array $members;

    /**
     * @param array<string, \Closure(self $c, string $id): mixed> $factories
     */
    private function __construct(?ContainerInterface $parent, array $factories)
    {
        $this->parent = $parent;
        $this->factories = $factories;
        $this->members = [];
    }

    /** @param array<string, \Closure(self $c, string $id): mixed> $factories */
    public static function from(array $factories): self
    {
        return new self(null, $factories);
    }

    /** @param array<string, \Closure(self $c, string $id): mixed> $factories */
    public static function extend(ContainerInterface $parent, array $factories): self
    {
        return new self($parent, $factories);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function run(\Closure $closure): mixed
    {
        $function = new \ReflectionFunction($closure);
        $parameters = $function->getParameters();
        $arguments = \array_map($this->valueForParameter(...), $parameters);
        return $closure(...$arguments);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function valueForParameter(\ReflectionParameter $parameter): mixed
    {
        $attributes = $parameter->getAttributes(InjectionInterface::class, \ReflectionAttribute::IS_INSTANCEOF);
        switch (\count($attributes)) {
            case 0:
                $type = $parameter->getType();
                if (($type instanceof \ReflectionNamedType) && $this->has($typeName = $type->getName())) {
                    return $this->get($typeName);
                }
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }
                break;
            case 1:
                $attribute = $attributes[0]->newInstance();
                \assert($attribute instanceof InjectionInterface);
                return $attribute->resolveValue($this, $parameter);
            default:
                throw new DependencyNotFoundException(
                    'Unexpected plurality of injection attributes on parameter ' . $parameter->getName(),
                );
        }
        throw new DependencyNotFoundException("Could not resolve dependency for parameter `{$parameter->getName()}`");
    }

    /**
     * This function can be referenced as a closure within factory definitions, to tell the {@see Container} to
     * autowire this dependency. This should only be used where the dependency is of a class type with a public
     * constructor for which all the parameters either have defaults, or can themselves be provided by this
     * {@see Container}.
     *
     * @throws AutowireException
     */
    public static function autowire(self $container, string $id): mixed
    {
        if (! \class_exists($id)) {
            throw new AutowireException("Not a class name: $id");
        }
        $reflectionClass = new \ReflectionClass($id);
        if (! $reflectionClass->isInstantiable()) {
            throw new AutowireException("Not instantiable: $id");
        }
        $constructor = $reflectionClass->getConstructor();
        if ($constructor === null) {
            try {
                return $reflectionClass->newInstance();
            } catch (\ReflectionException $e) {
                throw new AutowireException($e->getMessage(), $e->getCode(), $e);
            }
        }
        $parameters = $constructor->getParameters();
        try {
            $arguments = \array_map($container->valueForParameter(...), $parameters);
            return ($container->members[$id] = $reflectionClass->newInstanceArgs($arguments));
        } catch (\ReflectionException | DependencyNotFoundException $e) {
            throw new AutowireException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get(string $id): mixed
    {
        if (\array_key_exists($id, $this->members)) {
            return $this->members[$id];
        }
        if (\array_key_exists($id, $this->factories)) {
            $factory = $this->factories[$id];
            return ($this->members[$id] = $factory($this, $id));
        }
        if ($this->parent === null) {
            throw new DependencyNotFoundException("Dependency `{$id}` not found");
        }
        return $this->parent->get($id);
    }

    public function has(string $id): bool
    {
        if (\array_key_exists($id, $this->factories)) {
            return true;
        }
        if ($this->parent === null) {
            return false;
        }
        return $this->parent->has($id);
    }
}
