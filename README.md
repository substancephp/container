# [SubstancePHP](https://github.com/substancephp): [Container](https://packagist.org/packages/substancephp/container)

![CI](https://github.com/substancephp/container/actions/workflows/ci.yml/badge.svg)

## Overview

`substancephp/container` is a dependency injection package for PHP that offers the following core features:
* A container class that implements the [PSR-11 container interface](https://www.php-fig.org/psr/psr-11/)
* A container inheritance mechanism
* Automatic parameter injection into closures, using either type hinting or attributes
* The option of either autowiring, or manually defining factory callbacks

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
```

Note, after a given dependency has been looked up the first time, it is cached internally within the container,
and the same instance will be returned again the next time. This behaviour is notably different to that of, say,
Laravel's service container, which returns a new instance by default on each lookup.

Dependencies that are not explicitly defined in the container or its parents, will be &ldquo;autowired&rdquo;,
assuming they have a constructor for which parameters can be injected.

For example:

```php
class Foo
{
    public function __construct(private readonly Bar $bar)
    {
    }
}

class Bar
{
    public function __construct()
    {
    }
}
```

In this case, running `$container->get(Foo::class)` will return a new instance of `Foo` without `Foo::class` having
to have an explicit definition in the container.
