# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html)\*.

> \* Until 0.3.0, patch versions equal to 0 were omitted.


## [Unreleased]

### Added
- GitHub link
- Changelog file

### Changed

### Deprecated

### Removed

### Fixed

### Security


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


[Unreleased]: https://github.com/aryelgois/yasql-php/compare/v0.3.1...develop
[0.3.1]: https://github.com/aryelgois/yasql-php/compare/v0.3...v0.3.1
[0.3]: https://github.com/aryelgois/yasql-php/compare/v0.2...v0.3
[0.2]: https://github.com/aryelgois/yasql-php/compare/v0.1...v0.2
[0.1]: https://github.com/aryelgois/yasql-php/compare/271219190ff3dc0955b682a9444e52f6cca7424a...v0.1

[issues/1]: https://github.com/aryelgois/yasql-php/issues/1

[YAML]: http://yaml.org/
