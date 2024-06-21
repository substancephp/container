## Overview

`substancephp/container` is a dependency injection package for PHP that offers three core features:
* A container class that implements the [PSR-11 container interface](https://www.php-fig.org/psr/psr-11/)
* A container inheritance mechanism
* Automatic parameter injection into closures, using either type hinting or attributes

## Installation

```
composer require substancephp/container
```

## Usage example

```php
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use SubstancePHP\Container\Container;
use SubstancePHP\Container\Inject;

$container = Container::from([
    Foo::class => fn () => new Foo(),
    Bar::class => fn ($c) => new Bar($c->get(Foo::class)),
    'ttl-seconds' => 30,
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
