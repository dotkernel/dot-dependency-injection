# Changelog

All notable changes to this project are documented in this file.

## 2.0.0 - TBD

### Added

- PHP 8.6 support
- `RuntimeException::notInstantiable()`, `dependencyNotInstantiable()`, `argumentCountMismatch()`, `invalidService()` and `missingPackage()`
- `InvalidArgumentException::emptySegment()`
- Documentation for version 2, including an upgrade guide

### Changed

- `doctrine/orm` moved from `require` to `suggest`; applications using `AttributedRepositoryFactory` must require it, and versions below 2.9 or from 4.0 conflict
- `psr/container` requirement raised to `^1.1 || ^2.0`
- `AttributedServiceFactory::readKeysFromArray()` takes the full service key as its third parameter
- PHPUnit 12.5 and PHPStan level 8; the test suite fails on notices, warnings, deprecations and risky tests

### Deprecated

- Nothing.

### Removed

- `AttributedServiceFactory::$originalKey`

### Fixed

- Requesting an abstract class, an enum or a class with a non-public constructor throws `RuntimeException` instead of a PHP `Error`
- An unregistered dependency that cannot be instantiated without arguments throws `RuntimeException` instead of `ArgumentCountError` or `Error`
- The number of `#[Inject]` entries is checked against the constructor; extra entries were silently ignored
- Empty dot-notation segments throw `InvalidArgumentException` instead of reading the `''` key
- `AttributedRepositoryFactory` checks the type of the entity manager service
- `AttributedRepositoryFactory` names the missing package when `doctrine/orm` is not installed

## 1.4.1 - 2026-09-16

### Fixed

- FAQ menu entry ([#14](https://github.com/dotkernel/dot-dependency-injection/pull/14))

## 1.4.0 - 2026-09-16

### Changed

- `#[Entity]` targets classes and `#[Inject]` targets methods only ([#13](https://github.com/dotkernel/dot-dependency-injection/pull/13))
- Documentation expanded with the "Attributes vs. factories" and FAQ pages ([#13](https://github.com/dotkernel/dot-dependency-injection/pull/13))

### Removed

- PHP 8.2 support ([#13](https://github.com/dotkernel/dot-dependency-injection/pull/13))

### Fixed

- A configuration value of `null` was treated as a missing key ([#13](https://github.com/dotkernel/dot-dependency-injection/pull/13))
- Dot-notation paths continuing past a scalar were silently truncated ([#13](https://github.com/dotkernel/dot-dependency-injection/pull/13))
- `AttributedRepositoryFactory` returned a repository of the wrong class when the entity did not declare `repositoryClass` ([#13](https://github.com/dotkernel/dot-dependency-injection/pull/13))
- An `#[Entity]` attribute naming a missing class was not reported ([#13](https://github.com/dotkernel/dot-dependency-injection/pull/13))

## 1.3.0 - 2025-10-30

### Added

- PHP 8.4 and 8.5 support ([#11](https://github.com/dotkernel/dot-dependency-injection/pull/11))

### Removed

- PHP 8.1 support ([#11](https://github.com/dotkernel/dot-dependency-injection/pull/11))

## 1.2.0 - 2025-03-04

### Changed

- Psalm replaced with PHPStan ([#9](https://github.com/dotkernel/dot-dependency-injection/pull/9))

## 1.1.1 - 2025-01-28

### Changed

- Psalm 6.x ([#7](https://github.com/dotkernel/dot-dependency-injection/pull/7))

## 1.1.0 - 2024-10-22

### Added

- PHP 8.4 support ([#5](https://github.com/dotkernel/dot-dependency-injection/pull/5))

### Changed

- `laminas/laminas-coding-standard` 3.x

## 1.0.0 - 2024-06-07

Initial tagged release.
