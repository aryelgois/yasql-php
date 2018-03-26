# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html)\*.

> \* Until 0.3.0, patch versions equal to 0 were omitted.


## [Unreleased]

### Added

### Changed

### Deprecated

### Removed

### Fixed

### Security


## [0.5.0] - 2018-03-26

### Added
- Link for each class in API
- Database names can be overwritten in config file

### Changed
- Reduce `config file` section header level

### Fixed
- Indentation


## [0.4.2] - 2018-02-07

### Fixed
- When config file does not exist


## [0.4.1] - 2018-02-07

### Fixed
- When `config` argument is omitted


## [0.4.0] - 2018-02-07

### Added
- Option not to build with a local config file, but with vendor's

### Fixed
- Database example


## [0.4.0-alpha] - 2018-02-07

### Added
- GitHub link
- Changelog file
- README Index
- Default build output directory

### Changed
- API

### Removed
- .gitattributes, that made composer.lock binary
- `Populator->root` property

### Fixed
- Issue [#2][issues/2]
- Typo


## [0.3.1] - 2018-01-05

### Added
- Year 2018 in LICENSE


## [0.3] - 2018-01-05

### Added
- Composer class _(not to be confused with Composer package manager)_
- Composer scripts for testing
- Support for quoted identifiers
- support to `ZEROFILL`
- More test databases

### Changed
- Rename composer script `yasql-builder` to `yasql-build`

### Removed
- Test script

### Fixed
- Issue [#1][issues/1]
- When vendor directory is not found or does not exist in the project


## [0.2] - 2017-12-26

### Added
- Support to vendors
- Utils class
- Populator class
- Composer keyword

### Changed
- Move `arrayAppendLast()` from Generator to Utils
- Allow a `~` ([yaml] null) in a sequence of configs
- Allow a config file that only include others from vendors
- Allow multiple post files

### Removed
- Some colons in log

### Fixed
- Typo


## [0.1] - 2017-12-23

### Added
- README content
- composer.json
- composer.lock (as binary)
- Base PHP classes
- Test database and script


[Unreleased]: https://github.com/aryelgois/yasql-php/compare/v0.5.0...develop
[0.5.0]: https://github.com/aryelgois/yasql-php/compare/v0.4.2...v0.5.0
[0.4.2]: https://github.com/aryelgois/yasql-php/compare/v0.4.1...v0.4.2
[0.4.1]: https://github.com/aryelgois/yasql-php/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/aryelgois/yasql-php/compare/v0.4.0-alpha...v0.4.0
[0.4.0-alpha]: https://github.com/aryelgois/yasql-php/compare/v0.3.1...v0.4.0-alpha
[0.3.1]: https://github.com/aryelgois/yasql-php/compare/v0.3...v0.3.1
[0.3]: https://github.com/aryelgois/yasql-php/compare/v0.2...v0.3
[0.2]: https://github.com/aryelgois/yasql-php/compare/v0.1...v0.2
[0.1]: https://github.com/aryelgois/yasql-php/compare/271219190ff3dc0955b682a9444e52f6cca7424a...v0.1

[issues/1]: https://github.com/aryelgois/yasql-php/issues/1
[issues/2]: https://github.com/aryelgois/yasql-php/issues/2

[YAML]: http://yaml.org/
