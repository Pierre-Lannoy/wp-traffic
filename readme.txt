=== Traffic ===
Contributors: PierreLannoy, hosterra
Tags: api, analytics, reports, rest-api, statistics
Requires at least: 6.2
Requires PHP: 8.1
Tested up to: 6.5
Stable tag: 3.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Full featured monitoring & analytics for WordPress APIs.

== Description ==

**Full featured analytics for WordPress APIs.**

**Traffic** is a full featured analytics reporting tool that analyzes all inbound and outbound API calls made to/from your site.

> 🎁 Give this plugin a drive test on a free dummy site: [One-Click Test!](https://tastewp.com/new/?pre-installed-plugin-slug=traffic)

At this time, **Traffic** can report, for inbound and outbound traffic:

* KPIs: number of calls, data volume, server error rate, quotas error rate, effective pass rate and perceived uptime;
* domains, subdomains and endpoints details;
* metrics variations;
* HTTP codes, protocols and methods details;
* geographical repartition of calls;

**Traffic** supports multisite report delegation (see FAQ).

**Traffic** supports WP-CLI commands to:

* display (past or current) API calls in console - see `wp help api tail` for details;
* toggle on/off main settings - see `wp help api settings` for details.

For a full help on WP-CLI commands in Traffic, please [read this guide](https://perfops.one/traffic-wpcli).

> **Traffic** is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.

**Traffic** is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

= Support =

This plugin is free and provided without warranty of any kind. Use it at your own risk, I'm not responsible for any improper use of this plugin, nor for any damage it might cause to your site. Always backup all your data before installing a new plugin.

Anyway, I'll be glad to help you if you encounter issues when using this plugin. Just use the support section of this plugin page.

= Donation =

If you like this plugin or find it useful and want to thank me for the work done, please consider making a donation to [La Quadrature Du Net](https://www.laquadrature.net/en) or the [Electronic Frontier Foundation](https://www.eff.org/) which are advocacy groups defending the rights and freedoms of citizens on the Internet. By supporting them, you help the daily actions they perform to defend our fundamental freedoms!

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'.
2. Search for 'Traffic'.
3. Click on the 'Install Now' button.
4. Activate Traffic.

= From WordPress.org =

1. Download Traffic.
2. Upload the `traffic` directory to your `/wp-content/plugins/` directory, using your favorite method (ftp, sftp, scp, etc...).
3. Activate Traffic from your Plugins page.

= Once Activated =

1. Visit 'PerfOps One > Control Center > Traffic' in the left-hand menu of your WP Admin to adjust settings.
2. Enjoy!

== Frequently Asked Questions ==

= What are the requirements for this plugin to work? =

You need at least **WordPress 5.2** and **PHP 7.2**.

= Can this plugin work on multisite? =

Yes. It is designed to work on multisite too. Network Admins can configure the plugin and have access to all analytics reports. Sites Admins have access to the analytics reports of their sites.

= Where can I get support? =

Support is provided via the official [WordPress page](https://wordpress.org/support/plugin/traffic/).

= Where can I report a bug? =
 
You can report bugs and suggest ideas via the [GitHub issue tracker](https://github.com/Pierre-Lannoy/wp-traffic/issues) of the plugin.

== Changelog ==

Please, see [full changelog](https://perfops.one/traffic-changelog).

== Upgrade Notice ==

== Screenshots ==

1. Main Page
2. Domains List
3. Countries List
4. HTTP Codes List
5. Subdomain Details - Endpoints List
6. Endpoint Summary
7. Live Monitoring