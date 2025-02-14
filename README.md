# Traffic
[![version](https://badgen.net/github/release/Pierre-Lannoy/wp-traffic/)](https://wordpress.org/plugins/traffic/)
[![php](https://badgen.net/badge/php/7.2+/green)](https://wordpress.org/plugins/traffic/)
[![wordpress](https://badgen.net/badge/wordpress/5.2+/green)](https://wordpress.org/plugins/traffic/)
[![license](https://badgen.net/github/license/Pierre-Lannoy/wp-traffic/)](/license.txt)

__Traffic__ is a full featured analytics reporting tool that analyzes all inbound and outbound API calls made to/from your WordPress site.

See [WordPress directory page](https://wordpress.org/plugins/traffic/) or [official website](https://perfops.one/traffic).

At this time, __Traffic__ can report, for inbound and outbound traffic:

* KPIs: number of calls, data volume, server error rate, quotas error rate, effective pass rate and perceived uptime;
* domains, subdomains and endpoints details;
* metrics variations;
* HTTP codes, protocols and methods details;
* geographical repartition of calls;

__Traffic__ supports multisite report delegation (see FAQ).

> __Traffic__ is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.
> 
> __The development of The PerfOps One plugins suite is sponsored by [Hosterra - Ethical & Sustainable Internet Hosting](https://hosterra.eu/).__

__Traffic__ is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

## WP-CLI

__Traffic__ implements a set of WP-CLI commands. For a full help on these commands, please read [this guide](WP-CLI.md).

## Hooks

__Traffic__ introduces some filters and actions to allow plugin customization. Please, read the [hooks reference](HOOKS.md) to learn more about them.

## Installation

1. From your WordPress dashboard, visit _Plugins | Add New_.
2. Search for 'Traffic'.
3. Click on the 'Install Now' button.

You can now activate __Traffic__ from your _Plugins_ page.

## Support

For any technical issue, or to suggest new idea or feature, please use [GitHub issues tracker](https://github.com/Pierre-Lannoy/wp-traffic/issues). Before submitting an issue, please read the [contribution guidelines](CONTRIBUTING.md).

Alternatively, if you have usage questions, you can open a discussion on the [WordPress support page](https://wordpress.org/support/plugin/traffic/). 

## Contributing

Before submitting an issue or a pull request, please read the [contribution guidelines](CONTRIBUTING.md).

> ⚠️ The `master` branch is the current development state of the plugin. If you want a stable, production-ready version, please pick the last official [release](https://github.com/Pierre-Lannoy/wp-traffic/releases).

## Smoke tests
[![WP compatibility](https://plugintests.com/plugins/traffic/wp-badge.svg)](https://plugintests.com/plugins/traffic/latest)
[![PHP compatibility](https://plugintests.com/plugins/traffic/php-badge.svg)](https://plugintests.com/plugins/traffic/latest)