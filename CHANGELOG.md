# Changelog

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
