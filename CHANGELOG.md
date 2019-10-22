# Changelog
All notable changes to **Traffic** is documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Traffic** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Changed
- Normalization of cache IDs to avoid name collisions.
### Fixed
- Some cached elements may be autoloaded even if not needed.
- [MultiSite] The "what's new?" screen is only viewable by network admin.

## [1.2.1] - 2019-10-17
### Changed
- Improvements to endpoint cleaning.
- Self cron jobs spawning are now excluded from analytics.
### Fixed
- Some outbounds calls made during 'shutdown' hook are not recorded.
- Some hits are false positive and must not be recorded.

## [1.2.0] - 2019-10-10
### Added
- [MultiSite] New box in summary displaying all sites to network admins.
- A new option to allow or disallow favicons downloading and displaying.
- [MultiSite] Action link in sites list for network admins.
- [MultiSite] Action link in "my sites" for local admins.
- Zoom-in tooltip for all lists.
- "Metrics Variations" selectors tooltip.
### Changed
- [MultiSite] The site ID (currently viewed) is now displayed in the controls bar.
- A default icon is now shown in case there's no favicons for a website.
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