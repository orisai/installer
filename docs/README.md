# Installer

Composer installer for nette/di based packages

## Content

- [Schema](#schema)
- [Automatic configurator](#automatic-configurator)

## Schema

TODO

## Automatic configurator

Automatic configurator is a replacement for [nette/bootstrap](https://github.com/nette/bootstrap) `Configurator` and
[orisai/nette-di](https://github.com/orisai/nette-di) `ManualConfigurator`.

API is exactly same as for `ManualConfigurator`, except the `addConfig()` method which is replaced by internal calls
to `Loader`. Check the [orisai/nette-di](https://github.com/orisai/nette-di) documentation first to learn how to use the configurator.

```php
use App\Boot\Loader;
use Orisai\Installer\AutomaticConfigurator;

$configurator = new AutomaticConfigurator($rootDir, new Loader());
```

In DI are available additional parameters in `modules` key. They contain useful info about installed modules.

Installer config file switches `consoleMode` and `debugMode` switches are pre-configured to always load right configuration subset.
