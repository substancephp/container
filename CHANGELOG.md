# Changelog

### v0.5.1

* Improve autowire method:
  * Ensure it only ever throws AutowireException, never dependency not
    found exception
  * Remove dead code
  * Improve test coverage

### v0.5.0

* Change the autowiring mechanism. It now only works if you opt into
  autowiring for a specific class, using `Container::autowire(...)` as
  the factory callback.

### v0.4.0

* Allow autowiring mechanism to use default parameters

### v0.3.2

* Fix autowiring

### v0.3.1

* Constructors not autowired unless public

### v0.3.0

* Introduce autowiring

### v0.2.1

* Add tests
* Minor grammar correction in exception message
* Improvements to README

### v0.2.0

Breaking change:
* `InjectionInterface::resolveValue()` now accepts a second parameter, being the `\ReflectionParameter`
  instance for the parameter being injected into.

Enhancement:
* If the `SubstancePHP\Container\Inject` attribute does not receive an attribute constructor argument, then
  it will look up the value to inject using the parameter name (sans `$`).

### v0.1.1

Make attribute based autowiring more flexible, using an interface.

### v0.1.0

Initial release
