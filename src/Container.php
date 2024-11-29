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
     * Executes the passed closure, autowiring its parameters with dependencies retrieved from
     * the container.
     *
     * Parameters of named, non-scalar types will be injected based on the class/interface/enum
     * name in the function signature of the closure.
     *
     * Alternatively, for any parameter including scalars, the {@see Inject} attribute can be used on
     * a closure parameter to specify, by id, the dependency to be injected for that parameter.
     *
     * Example:
     * <pre>
     *     $container->run(function (
     *         Foobar $foobar,
     *         #[Inject('ttl-seconds')] int $ttlSeconds,
     *     ): void {
     *         // do stuff
     *     });
     * </pre>
     *
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     *
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
     * Creates a new instance of the passed class, using the passed container instance to get any dependencies
     * required to be passed to the class's constructor.
     *
     * This function can be referenced as a closure within factory definitions, to tell the {@see Container} to
     * autowire this dependency. This should only be used where the dependency is of a class type with a public
     * constructor for which all the parameters either have defaults, or can themselves be provided by this
     * {@see Container}.
     *
     * @throws AutowireException
     */
    public static function autowire(self $container, string $class): mixed
    {
        if (! \class_exists($class)) {
            throw new AutowireException("Not a class name: $class");
        }
        $reflectionClass = new \ReflectionClass($class);
        if (! $reflectionClass->isInstantiable()) {
            throw new AutowireException("Not instantiable: $class");
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
            return $reflectionClass->newInstanceArgs($arguments);
        } catch (\ReflectionException | DependencyNotFoundException $e) {
            throw new AutowireException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns an instance of the requested dependency.
     *
     * Internally, the {@see Container} will construct an instance of the dependency only the first time one
     * requested, and will return the same instance on any subsequent requests.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface if the requested dependency is not defined by this {@see Container}.
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

    /**
     * Returns true if the container contains (or can access via inheritance) a definition for this
     * dependency; otherwise, returns false.
     */
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
