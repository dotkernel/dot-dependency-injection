# Installation

## Requirements

- PHP 8.3, 8.4, 8.5 or 8.6
- A PSR-11 container (`psr/container` ^1.1 || ^2.0), usually `laminas/laminas-servicemanager`
- `doctrine/orm` ^2.9 || ^3.0, installed by your application, only if you use `AttributedRepositoryFactory`

## Install

Install `dotkernel/dot-dependency-injection` by executing the following Composer command:

```shell
composer require dotkernel/dot-dependency-injection
```

`doctrine/orm` is not installed with this package.
If you use `AttributedRepositoryFactory`, require it in your application:

```shell
composer require doctrine/orm
```

Upgrading from 1.x? See [Upgrading from 1.x](upgrading.md).
