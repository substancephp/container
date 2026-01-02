# SubstancePHP: Container

![CI](https://github.com/substancephp/container/actions/workflows/ci.yml/badge.svg)

## Overview

`substancephp/container` is a dependency injection package for PHP. It offers:

* A container class that implements the [PSR-11 container interface](https://www.php-fig.org/psr/psr-11/)
* A container inheritance mechanism
* Automatic parameter injection into closures, using either type hinting or attributes
* Optional autowiring

## Installation

```
composer require substancephp/container
```

## Usage

The following is an illustrative code passage showing how to use the `SubstancePHP\Container` class to define
dependencies for injection.

```php
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\Container\Inject;

// Initialize a `Container` instance from an array. The array's keys are strings
// (typically, but not necessarily, class, enum or interface names), and the array
// values are callbacks telling the container how to construct each given dependency.

$container = Container::from([

    // Example of simple "manual" definition:
    Foo::class => function (): Foo {
        return new Foo('example', 'constructor', 'parameters');
    },

    // Dependencies can also be literal values; and we can use arrow functions for
    // brevity:
    'ttl-seconds' => fn () => 30,

    // Example of manual definition by passing the container to the callback.
    // This can be used to get other dependencies required by the definition.
    BarInterface::class => function (Container $c): BarInterface {
        return new BarImplementation($c->get(Foo::class));
    },

    // Example of autowiring.
    // By referencing the `Container::autowire` method as a closure, we can
    // arrange for the construction of the given dependency using reflection
    // on its constructor parameters:
    Baz::class => Container::autowire(...),

    // The `Container::autowire` method can also be used within the callback
    // itself:
    BaqInterface::class => function (Container $c): BaqInterface {
        return Container::autowire($c, BaqImplementation::class);
    },
]);

// You can spawn a child container that efficiently inherits the parent container's
// definitions, and augments/overrides them with additional definitions. This might
// be done, for example, to initialize a separate `Container` instance per HTTP
// request, if your application's architecture requires that.
// Note that the *parent* container can be *any* instance of
// `Psr\Container\ContainerInterface`; it need not be an instance of
// `SubstancePHP\Container\Container`.
$container2 = Container::extend($container, [
    ServerRequestInterface::class => fn () => ServerRequestFactory::fromGlobals(),
]);


// A `Container` can be invoked to execute an arbitrary callable, autowiring its
// parameters with dependencies retrieved from the container:

$container2->run(function(

    // Parameters of named, non-scalar types will be injected based on the
    // class/interface/enum name:

    Foo $foo,

    ServerRequestInterface $request,

    // The `SubstancePHP\Container\Inject` attribute allows us to specify, by key, the
    // specific dependency to be injected for the given parameter. This is especially
    // useful when the parameter is of a scalar type:

    #[Inject('ttl-seconds')] int $ttl,

): void {
    // ... do stuff
});

// Similarly, if a class is autowired using Container::autowire(...), you can
// use the `Inject` attribute in its constructor, to specify dependencies for
// parameters that cannot be inferred automatically:

public function __construct(
    Bar $bar,
    #[Inject('thing.xyz')] int $someParam,
) {
}
```

Note, dependencies are always initialised lazily, when and only when the container is required to provide them.

Also, after a given dependency has been looked up the first time, it is cached internally within the container, and
the same instance will be returned again the next time. This behaviour is notably different to that of, say, Laravel's
service container, which returns a new instance by default on each lookup.


## Performance

`substancephp/container` aims to offer a very simple, yet flexible API surface, while conforming with the PSR-11
container interface.

It is not a goal of the library to have the highest possible runtime performance; but rather, to perform well enough
for the vast majority of use cases.
