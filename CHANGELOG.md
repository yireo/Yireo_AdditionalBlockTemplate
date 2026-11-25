# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.0.5] - 25 November 2026
### Added
- Support for PHP 8.5

### Changed
- Additional templates are no longer rendered when the original block outputs nothing; add `render_when_empty` to an item to render it anyway

### Removed
- Support for PHP 8.1 (PHP 8.2 up to 8.5 is now required)

### Fixed
- Invalid additional template items (malformed template identifier, `..` in the path, unknown position) are skipped instead of causing errors
- With multiple additional templates, all positions are now rendered relative to the original output, in the order they are declared
- The `nest` position no longer injects HTML inside a trailing `script`, `style`, `textarea`, `title` or `template` tag, and no longer breaks on output without a closing tag
- `getAdditionalTemplateName()` is reset after rendering, so the name no longer leaks into later output
- Missing dependencies on `Magento_Store` and `ext-pcre` are now declared

## [0.0.4] - 22 October 2025
### Fixed
- Add files
- Copy generic CI/CD files
- Pending changes

## 0.0.3 and earlier
- Initial releases 
