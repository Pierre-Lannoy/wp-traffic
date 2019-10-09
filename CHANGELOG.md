# Changelog
All notable changes to **Traffic** is documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Traffic** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- [MultiSite] Action link in sites list for network admins.
- [MultiSite] Action link in "my sites" for local admins.
- Zoom-in tooltip for all lists.
- "Metrics Variations" selectors tooltip.
### Changed
- [MultiSite] The site ID (currently viewed) is now displayed in the control bar.
- Better selector layouts on mobile devices.
- The active "Metrics Variations" selector has now a different color.
### Fixed
- PHP warning when computing percentages.
- Clicking on "Metrics Variations" selectors while loading data may lead to an unexpected result.
- In the date-picker, the string "Custom Range" is not internationalized (it's always in English).

## [1.1.0] - 2019-10-05
### Added
- It's now possible to use public CDN to serve Traffic scripts and stylesheets (see _Settings | Traffic | Options_).
- The option page shows the logging plugin used.
### Changed
- Better computing of KPIs when no data are collected on the current day.
- Cron jobs are now excluded from analytics.
- Traffic database table collation is now `utf8_unicode_ci`.
- The (nag) update message has now a link to display changelog.
### Fixed
- Error while creating Traffic database table with utf8mb4 charset for some version of MySQL.
- KPIs layout may be jammed by site-wide stylesheets.
- PHP notice and warning when trying to count calls when there's no call for current day.
- Some typos in tooltips.

## [1.0.0] - 2019-10-04
### Initial release