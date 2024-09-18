# [SubstancePHP](https://github.com/substancephp): [Container](https://packagist.org/packages/substancephp/container)

![CI](https://github.com/substancephp/container/actions/workflows/ci.yml/badge.svg)

## Overview

`substancephp/container` is a dependency injection package for PHP that offers three core features:
* A container class that implements the [PSR-11 container interface](https://www.php-fig.org/psr/psr-11/)
* A container inheritance mechanism
* Automatic parameter injection into closures, using either type hinting or attributes
* Optional autowiring

## Installation

```
composer require substancephp/container
```

## Usage

```php
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\Container\Inject;

$container = Container::from([
    Foo::class => fn () => new Foo(),
    Bar::class => fn ($c) => new Bar($c->get(Foo::class)),
    Baz::class => Container::autowire(...),
    'ttl-seconds' => fn () => 30,
]);

$request = ServerRequestFactory::fromGlobals();

// Spawn a child container that inherits the parent container's definitions,
// and augments/overrides them with additional definitions:

$container2 = Container::extend($container, [ServerRequestInterface::class => fn () => $request]);

// Call a function, autowiring its parameters:

$container2->run(function(
    Foo::class $foo,
    #[Inject('ttl-seconds')] int $ttl,
    ServerRequestInterface $request,
): void {
    // ... do stuff
});

// If a class is autowired using Container::autowire(...), you can tell it use the Inject attribute
// to specify dependencies for parameters that cannot be inferred automatically:

public function __construct(
    Bar $bar,
    #[Inject('thing.xyz')] int $someParam,
) {
}
```

Note, after a given dependency has been looked up the first time, it is cached internally within the container,
and the same instance will be returned again the next time. This behaviour is notably different to that of, say,
Laravel's service container, which returns a new instance by default on each lookup.
