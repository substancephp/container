<?php

declare(strict_types=1);

namespace SubstancePHP\Container;

use SubstancePHP\Container\Exception\DependencyNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class Container implements ContainerInterface
{
    private readonly ?ContainerInterface $parent;

    /**
     * @var array<string, \Closure(ContainerInterface $c): mixed>
     */
    private readonly array $factories;

    /**
     * @var array<string, mixed> a store of lazily initialized singletons
     */
    private array $members;

    /**
     * @param array<string, \Closure(ContainerInterface $c): mixed> $factories
     */
    private function __construct(?ContainerInterface $parent, array $factories)
    {
        $this->parent = $parent;
        $this->factories = $factories;
        $this->members = [];
    }

    /** @param array<string, \Closure(ContainerInterface $c): mixed> $factories */
    public static function from(array $factories): self
    {
        return new self(null, $factories);
    }

    /** @param array<string, \Closure(ContainerInterface $c): mixed> $factories */
    public static function extend(ContainerInterface $parent, array $factories): self
    {
        return new self($parent, $factories);
    }

    /**
     * @throws ContainerExceptionInterface
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
     * @throws ContainerExceptionInterface
     */
    public function get(string $id): mixed
    {
        if (\array_key_exists($id, $this->members)) {
            return $this->members[$id];
        }
        if (\array_key_exists($id, $this->factories)) {
            $factory = $this->factories[$id];
            return ($this->members[$id] = $factory($this));
        }
        if ($this->parent !== null && $this->parent->has($id)) {
            return $this->parent->get($id);
        }
        try {
            if (! \class_exists($id)) {
                throw new DependencyNotFoundException("Not a class name: $id");
            }
            $reflectionClass = new \ReflectionClass($id);
            if (! $reflectionClass->isInstantiable()) {
                throw new DependencyNotFoundException("Not instantiable: $id");
            }
            $constructor = $reflectionClass->getConstructor();
            if ($constructor !== null && ! $constructor->isPublic()) {
                throw new DependencyNotFoundException("Constructor not accessible: $id");
            }
            if ($constructor === null) {
                return $reflectionClass->newInstance();
            }
            $parameters = $constructor->getParameters();
            $arguments = \array_map($this->valueForParameter(...), $parameters);
            return ($this->members[$id] = $reflectionClass->newInstanceArgs($arguments));
        } catch (\ReflectionException $e) {
            throw new DependencyNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function has(string $id): bool
    {
        try {
            $_ = $this->get($id);
            return true;
        } catch (ContainerExceptionInterface) {
            return false;
        }
    }
}
