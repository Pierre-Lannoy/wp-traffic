# Changelog
All notable changes to **Traffic** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Traffic** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.7.0] - 2020-07-20
### Added
- Compatibility with WordPress 5.5.
### Changed
- Improved compatibility with plugins using long treatments in `pre_http_request` hook.

## [1.6.4] - 2020-06-29
### Changed
- Full compatibility with PHP 7.4.
- Automatic switching between memory and transient when a cache plugin is installed without a properly configured Redis / Memcached.

## [1.6.3] - 2020-06-18
### Changed
- Improved detection of HTTP 200 code for inbound requests.
### Fixed
- When used for the first time, settings checkboxes may remain checked after being unchecked.

## [1.6.2] - 2020-05-05
### Fixed
- There's an error while activating the plugin when the server is Microsoft IIS with Windows 10.
- With Microsoft Edge, some layouts may be ugly.

## [1.6.1] - 2020-04-10
### Fixed
- Some main settings may be not saved.

## [1.6.0] - 2020-04-10
### Added
- Compatibility with [DecaLog](https://wordpress.org/plugins/decalog/) early loading feature.
- Full integration with [IP Locator](https://wordpress.org/plugins/ip-locator/).
### Changed
- Analytics now integrate file size in total request size for outbound POST and GET.
- Improved remote IP detection.
- The settings page have now the standard WordPress style.
- Better styling in "PerfOps Settings" page.
- In site health "info" tab, the boolean are now clearly displayed.
### Removed
- Dependency to "Geolocation IP Detection" plugin. Nevertheless, this plugin can be used as a fallback solution.
- Flagiconcss as library. If there's no other way, flags will be rendered as emoji.
- Unneeded tool links in settings page.

## [1.5.0] - 2020-03-01
### Added
- Full compatibility with [APCu Manager](https://wordpress.org/plugins/apcu-manager/).
- Full integration with PerfOps.One suite.
- Compatibility with WordPress 5.4.
### Changed
- New menus (in the left admin bar) for accessing features: "PerfOps Analytics" and "PerfOps Settings".
- Country flags are now cached.
### Fixed
- Some headers may be wrongly analyzed when they are arrays of arrays.
- With some plugins, box tooltips may be misplaced (css collision).
- In analytics reports, the layout for lines with 3 boxes may be ugly at some resolutions.
### Removed
- Compatibility with WordPress versions prior to 5.2.
- Old menus entries, due to PerfOps integration.

## [1.4.0] - 2020-01-01
### Added
- Full compatibility (for internal cache) with Redis and Memcached.
- Using APCu rather than database transients if APCu is available.
- [MultiSite] A new "Sites Breakdown" list in all reports is available to network admins.
- New Site Health "status" sections about OPcache and object cache. 
- New Site Health "status" section about i18n extension for non `en_US` sites.
- New Site Health "info" sections about OPcache and object cache. 
- New Site Health "info" section about the plugin itself.
- Automattic servers detection.
### Changed
- Reports have now a specific title for HTTP codes, protocols, methods and countries details.
- [MultiSite] Switching from a single site to all sites now preserves the reference domain.
- Private range IPs have now a specific favicon.
### Fixed
- Some headers may be wrongly analyzed when they are arrays.
- A wrong value may be used for the data retention period.

## [1.3.1] - 2019-12-19
### Changed
- Better cache management for old date ranges.
### Fixed
- Some plugin options may be not saved when needed (thanks to [Lucas Bustamante](https://github.com/Luc45)).

## [1.3.0] - 2019-12-05
### Added
- A new option (in settings) to set the data retention period.
### Changed
- Graph labels are now rightly positioned and aligned.
- Graph names have been changed to mark the difference between 'variation' and 'distribution'.
### Fixed
- With non-standard dashboard colors, tooltip shadow may be ugly.
- The buttons of the date range picker may have a wrong size.
### Removed
- As a result of the Plugin Team's request, the auto-update feature has been removed.

## [1.2.3] - 2019-11-15
### Changed
- Unit symbols and abbreviations are now visually differentiated.
- There's now a non-breaking space between values and units.
- Upgraded Feather library from version 4.22.1 to version 4.24.1.
### Fixed
- PHP warning (in some rare cases) when executing "rest_pre_echo_response" hook.
- Counting cookies sizes may produce a PHP notice in some cases.

## [1.2.2] - 2019-11-03
### Changed
- Normalization of cache IDs to avoid name collisions.
### Fixed
- Main graph doesn't show when time range is strictly one month.
- Some cached elements may be autoloaded even if not needed.
- A PHP notice may appear when enqueuing some plugin assets.
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