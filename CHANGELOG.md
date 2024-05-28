# Changelog
All notable changes to **Traffic** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Traffic** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2024-05-28

### Added
- [BC] To enable installation on more heterogeneous platforms, the plugin now adapts its internal logging mode to already loaded libraries.

### Changed
- Updated DecaLog SDK from version 4.1.0 to version 5.0.0.

### Fixed
- PHP error with some plugins like Woocommerce Paypal Payments.

## [2.14.0] - 2024-05-07

### Changed
- The plugin now adapts its requirements to the PSR-3 loaded version.

## [2.13.2] - 2024-05-04

### Fixed
- PHP error when DecaLog is not installed.

## [2.13.1] - 2024-05-04

### Changed
- Updated DecaLog SDK from version 3.0.0 to version 4.1.0.
- Minimal required WordPress version is now 6.2.

## [2.13.0] - 2024-03-02

### Added
- Compatibility with WordPress 6.5.

### Changed
- Minimal required WordPress version is now 6.1.
- Minimal required PHP version is now 8.1.

### Fixed
- Mismatch in plugin versions

## [2.12.0] - withdrawn

### Added
- Compatibility with WordPress 6.5.

### Changed
- Minimal required WordPress version is now 6.1.
- Minimal required PHP version is now 8.1.

## [2.11.0] - 2023-10-25

### Added
- Compatibility with WordPress 6.4.

## [2.10.0] - 2023-07-12

### Added
- Compatibility with WordPress 6.3.

### Changed
- The color for `shmop` test in Site Health is now gray to not worry to much about it (was previously orange).

## [2.9.1] - 2023-03-02

### Fixed
- [SEC004] CSRF vulnerability / [CVE-2023-27444](https://www.cve.org/CVERecord?id=CVE-2023-27444) (thanks to [Mika](https://patchstack.com/database/researcher/5ade6efe-f495-4836-906d-3de30c24edad) from [Patchstack](https://patchstack.com)).

## [2.9.0] - 2023-02-24

The developments of PerfOps One suite, of which this plugin is a part, is now sponsored by [Hosterra](https://hosterra.eu).

Hosterra is a web hosting company I founded in late 2022 whose purpose is to propose web services operating in a European data center that is water and energy efficient and ensures a first step towards GDPR compliance.

This sponsoring is a way to keep PerfOps One plugins suite free, open source and independent.

### Added
- Compatibility with WordPress 6.2.

### Changed
- Improved loading by removing unneeded jQuery references in public rendering (thanks to [Kishorchand](https://github.com/Kishorchandth)).

### Fixed
- In some edge-cases, detecting IP may produce PHP deprecation warnings (thanks to [YR Chen](https://github.com/stevapple)).

## [2.8.0] - 2022-10-06

### Added
- Compatibility with WordPress 6.1.
- [WPCLI] The results of `wp api` commands are now logged in [DecaLog](https://wordpress.org/plugins/decalog/).

### Changed
- Improved ephemeral cache in analytics.
- [WPCLI] The results of `wp api` commands are now prefixed by the product name.

### Fixed
- Live console with PHP 8 may be broken (thanks to [stuffeh](https://github.com/stuffeh)).
- [SEC003] Moment.js library updated to 2.29.4 / [Regular Expression Denial of Service (ReDoS)](https://github.com/moment/moment/issues/6012).

## [2.7.1] - 2022-04-20

### Fixed
- 2.7.0 tag is not displayed as new release on wp.org.

## [2.7.0] - 2022-04-20

### Added
- Compatibility with WordPress 6.0.

### Changed
- Site Health page now presents a much more realistic test about object caching.
- Improved favicon handling for new Google API specifications.
- Updated DecaLog SDK from version 2.0.2 to version 3.0.0.

### Fixed
- [SEC002] Moment.js library updated to 2.29.2 / [CVE-2022-24785](https://github.com/advisories/GHSA-8hfj-j24r-96c4).

## [2.6.1] - 2022-01-17

### Fixed
- The Site Health page may launch deprecated tests.

## [2.6.0] - 2022-01-17

### Added
- Compatibility with PHP 8.1.

### Changed
- Updated DecaLog SDK from version 2.0.0 to version 2.0.2.
- Updated PerfOps One library from 2.2.1 to 2.2.2.
- Refactored cache mechanisms to fully support Redis and Memcached.
- Improved bubbles display when width is less than 500px (thanks to [Pat Ol](https://profiles.wordpress.org/pasglop/)).
- The tables headers have now a better contrast (thanks to [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/)).

### Fixed
- Object caching method may be wrongly detected in Site Health status (thanks to [freshuk](https://profiles.wordpress.org/freshuk/)).
- The console menu may display an empty screen (thanks to [Renaud Pacouil](https://www.laboiteare.fr)).
- There may be name collisions with internal APCu cache.
- An innocuous Mysql error may be triggered at plugin activation.

## [2.5.0] - 2021-12-07

### Added
- Compatibility with WordPress 5.9.
- New button in settings to install recommended plugins.
- The available hooks (filters and actions) are now described in `HOOKS.md` file.
- Adherence to the `Mailarchiver-No-Log` directive implemented since [MailArchiver 2.5.0](https://wordpress.org/plugins/mailarchiver/).
- Adherence to the new `Traffic-No-Log` internal directive.

### Changed
- Improved update process on high-traffic sites to avoid concurrent resources accesses.
- Better publishing frequency for metrics.
- HTTP error codes 208 and 226 are now supported.
- X axis for graphs have been redesigned and are more accurate.
- Added beacon endpoints to the smart-filtered list.
- Updated labels and links in plugins page.
- Updated the `README.md` file.

### Fixed
- Internal API version is not consistent with plugin version.
- Some noisy calls, linked to live logging, escape the smart filter.
- Country translation with i18n module may be wrong.
- There's typos in `CHANGELOG.md`.

### Removed
- Display of beacon inbound calls in live console.

## [2.4.0] - 2021-09-07

### Added
- It's now possible to hide the main PerfOps One menu via the `poo_hide_main_menu` filter or each submenu via the `poo_hide_analytics_menu`, `poo_hide_consoles_menu`, `poo_hide_insights_menu`, `poo_hide_tools_menu`, `poo_hide_records_menu` and `poo_hide_settings_menu` filters (thanks to [Jan Thiel](https://github.com/JanThiel)).

### Changed
- Updated DecaLog SDK from version 1.2.0 to version 2.0.0.

### Fixed
- There may be name collisions for some functions if version of WordPress is lower than 5.6.
- The main PerfOps One menu is not hidden when it doesn't contain any items (thanks to [Jan Thiel](https://github.com/JanThiel)).
- In some very special conditions, the plugin may be in the default site language rather than the user's language.
- The PerfOps One menu builder is not compatible with Admin Menu Editor plugin (thanks to [dvokoun](https://wordpress.org/support/users/dvokoun/)).

## [2.3.2] - 2021-08-11

### Changed
- New redesigned UI for PerfOps One plugins management and menus (thanks to [Loïc Antignac](https://github.com/webaxones), [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/), [Axel Ducoron](https://github.com/aksld), [Laurent Millet](https://profiles.wordpress.org/wplmillet/), [Samy Rabih](https://github.com/samy) and [Raphaël Riehl](https://github.com/raphaelriehl) for their invaluable help).

### Fixed
- In some conditions, the plugin may be in the default site language rather than the user's language.

## [2.3.1] - 2021-06-22

### Fixed
- wp.org distributes a wrong version.

## [2.3.0] - 2021-06-22

### Added
- Compatibility with WordPress 5.8.
- Integration with DecaLog SDK.
- Traces and metrics collation and publication.
- New option, available via settings page and wp-cli, to disable/enable metrics collation.

### Changed
- [BC] Inbound latency is far more precise - warning, you may see gaps in values/graphs after updating plugin.
- Upgraded Lock library from version 2.1 to version 2.2.
- [WP-CLI] Changing the color scheme for the `tail` command is now done via the `--theme=<theme>` parameter.
- [WP-CLI] `api status` command now displays DecaLog SDK version too.
- [WP-CLI] Updated documentation.
- Self cron jobs spawning are now excluded from analytics only if smart filter is activated.

### Fixed
- Messages may be wrongly truncated in live console.

## [2.2.0] - 2021-02-24

### Added
- Compatibility with WordPress 5.7.
- Adherence to the new `Decalog-No-Log` directive implemented by [DecaLog 2.4.0](https://wordpress.org/plugins/decalog/).

### Changed
- Smart filter now filters `/server-status` and `/server-info` calls.
- Consistent reset for settings.
- Improved translation loading.
- [WP_CLI] `api` command have now a definition and all synopsis are up to date.
- More precise statistics about outbound calls made in shutdown handler(s).

### Fixed
- In Site Health section, Opcache status may be wrong (or generates PHP warnings) if OPcache API usage is restricted.
- Some settings are impossible to uncheck.

## [2.1.0] - 2020-11-23

### Added
- Compatibility with WordPress 5.6.

### Changed
- Improvement in the way roles are detected.
- Anonymous proxies, satellite providers and private networks are now fully detected when [IP Locator](https://wordpress.org/plugins/ip-locator/) is installed.
- Better web console layout.

### Fixed
- [SEC001] User may be wrongly detected in XML-RPC or Rest API calls.
- When site is in english and a user choose another language for herself/himself, menu may be stuck in english.

## [2.0.3] - 2020-10-13

### Changed
- [WP-CLI] Improved documentation.
- Hardening (once again) IPs detection.
- The analytics dashboard now displays a warning if analytics features are not activated.
- Prepares PerfOps menus to future 5.6 version of WordPress.

### Fixed
- The remote IP can be wrongly detected when behind some types of reverse-proxies.
- In admin dashboard, the statistics link is visible even if analytics features are not activated.

## [2.0.2] - 2020-10-05

### Fixed
- [WP-CLI] With some PHP configurations, there may be a (big) delay in the display of lines.

## [2.0.1] - 2020-10-05

### Fixed
- Some minimized js files are missing.

## [2.0.0] - 2020-10-05

### Added
- New live console-in-browser to see API calls as soon as they occur.
- [WP-CLI] New command to display (past or current) API calls in console: see `wp help api tail` for details.
- [WP-CLI] New command to display Traffic status: see `wp help api status` for details.
- [WP-CLI] New command to toggle on/off main settings: see `wp help api settings` for details.
- Traffic now sends inbound and outbound events to [DecaLog](https://wordpress.org/plugins/decalog/) with settable level.
- New "smart filter" options to exclude noisy internal calls.
- New Site Health "info" section about shared memory.

### Changed
- The positions of PerfOps menus are pushed lower to avoid collision with other plugins (thanks to [Loïc Antignac](https://github.com/webaxones)).
- Improved layout for language indicator.
- If GeoIP support is not done via [IP Locator](https://wordpress.org/plugins/ip-locator/), the flags are now correctly downgraded to emojis.
- Admin notices are now set to "don't display" by default.
- Improved IP detection  (thanks to [Ludovic Riaudel](https://github.com/lriaudel)).
- Improved changelog readability.
- The integrated markdown parser is now [Markdown](https://github.com/cebe/markdown) from Carsten Brandt.

### Fixed
- With Firefox, some links are unclickable in the Control Center (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).

### Removed
- Parsedown as integrated markdown parser.

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
- Full integration with PerfOps One suite.
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

Initial release