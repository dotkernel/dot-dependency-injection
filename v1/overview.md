# Overview

`dot-dependency-injection` is Dotkernel's dependency injection service.

Instead of a handwritten factory class per service, you declare a class's dependencies with the `#[Inject]` attribute on its constructor - or a repository's entity with `#[Entity]` - and register one of the two reusable factories this package ships.
That removes an entire category of boilerplate files from a project and keeps the dependency list next to the constructor it feeds.

It provides:

- `Dot\DependencyInjection\Attribute\Inject` - declares the dependencies of a constructor
- `Dot\DependencyInjection\Attribute\Entity` - declares the entity of a Doctrine repository
- `Dot\DependencyInjection\Factory\AttributedServiceFactory` - builds any class from its `#[Inject]` attribute
- `Dot\DependencyInjection\Factory\AttributedRepositoryFactory` - builds any Doctrine repository from its `#[Entity]` attribute

Continue with [Attributes vs. factories](attributes-vs-factories.md) for the comparison and the trade-offs, or with [Installation](installation.md).

## Badges

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/dot-dependency-injection)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-dependency-injection/1.4.1)

[![GitHub issues](https://img.shields.io/github/issues/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/issues)
[![GitHub forks](https://img.shields.io/github/forks/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/network)
[![GitHub stars](https://img.shields.io/github/stars/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/stargazers)
[![GitHub license](https://img.shields.io/github/license/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/blob/1.0/LICENSE.md)

[![Build Static](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/continuous-integration.yml/badge.svg?branch=1.0)](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/dotkernel/dot-dependency-injection/graph/badge.svg?token=DayAoD2Oj6)](https://codecov.io/gh/dotkernel/dot-dependency-injection)
[![docs-build](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/docs-build.yml/badge.svg)](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/docs-build.yml)
[![PHPStan](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/static-analysis.yml/badge.svg?branch=1.0)](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/static-analysis.yml)
