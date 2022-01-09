# Installer

[Composer](https://getcomposer.org) installer for [nette/di](https://github.com/nette/di/) based packages

## Content

- [Setup](#setup)
- [Schema](#schema)
- [Automatic configurator](#automatic-configurator)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer require orisai/installer
```

## Schema

`orisai.neon`

```neon
# schema version
version: 1

# generated code with all packages and their configs
# root-only
# default: null
loader:
    # path to file
    # string
    # required
    file: path/to/src/Loader.php
    # class name
    # class-string
    # required
    class: App\Loader

# config files provided by current package
# any package
configs:
    # path to config
    # string
    - path/to/config.neon
    -
      # path to config
      # string
      # required
      file: path/to/config.neon
      # switches required to include this config file
      # array<string, bool> array of switch names and their required value
      switches:
          # if switch value matches required value, include this file
          # bool
          httpsOnly: false
      # packages required to be installed to include this config file
      packages:
          # package name
          # string
          - vendor/package
      # change loading priority of config file
      # by default configs are ordered by package priority in dependency tree and added to 'normal' group
      # low|normal|high
      # default: normal
      priority: normal

# enable/disable config option from a config file
# any package
# array<string, bool> array of switch names and their default value
switches:
    # bool
    httpsOnly: true

# ignore config from packages
# any package
ignore:
    # string
    # package name
    - vendor/package

# packages which are part of monorepo are not really considered installed in monorepo
# this simulates their existence for purpose of in-monorepo development
# root-only
simulated-modules:
    # path to module
    # string
    - path/to/submodule-a
    -
      # path to module
      # string
      # required
      path: path/to/submodule-b
      # raise error when module is not found
      # bool
      # default: false
      optional: false
```

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
