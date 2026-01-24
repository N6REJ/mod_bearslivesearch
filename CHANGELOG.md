# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2025.09.10] - 2025-09-10

### Added
- ACL (Access Control List) permission checks to search results
  - Users now only see articles they have permission to view based on their user group access levels
  - Prevents unauthorized access to restricted content through search functionality
  - Integrates with Joomla's `getAuthorisedViewLevels()` method for proper permission filtering

### Changed
- Default search phrase mode changed from "Exact Phrase" to "All Words"
  - Provides more intuitive and flexible search behavior for users
  - Updated in both template HTML (default.php) and JavaScript (bearslivesearch.js)
  - Users can still manually select "Exact Phrase" or "Any Words" if desired

### Security
- **CRITICAL**: Fixed security vulnerability where search was bypassing ACL permissions
  - Search queries now include `access IN (authorisedLevels)` filter with proper integer sanitization
  - All access level values are cast to integers using `array_map('intval', ...)` to prevent SQL injection
  - Filters out zero or negative values and provides fallback to public access level (1) if needed
  - Aligns search behavior with standard Joomla article viewing permissions

## [2025.09.09] - 2025-09-09

### Changed

* Update version to  [skip ci] ([9275c9d](https://github.com/N6REJ/mod_bearslivesearch/commit/9275c9d))
* Update .gitignore ([15247c0](https://github.com/N6REJ/mod_bearslivesearch/commit/15247c0))
* Update version to  [skip ci] ([601bdeb](https://github.com/N6REJ/mod_bearslivesearch/commit/601bdeb))
