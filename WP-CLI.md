Traffic is fully usable from command-line, thanks to [WP-CLI](https://wp-cli.org/). You can set Traffic options, view past or current API calls and much more, without using a web browser.

1. [Viewing API calls](#viewing-api-calls) - `wp api tail`
2. [Getting Traffic status](#getting-traffic-status) - `wp api status`
3. [Managing main settings](#managing-main-settings) - `wp api settings`
4. [HTTP status codes](#http-status-codes) - `wp api statuscode`
5. [Misc flags](#misc-flags)

## Viewing API calls

Traffic lets you use command-line to view past and currents API calls. All is done via the `wp api tail [<count>] [--direction=<direction>] [--filter=<filter>] [--format=<format>] [--col=<columns>] [--theme=<theme>] [--yes]` command.

If you don't specify `<count>`, Traffic will launch an interactive monitoring session: it will display calls as soon as they occur on your site. To quit this session, hit `CTRL+C`.

If you specifiy a value for `<count>` between 1 to 60, Traffic will show you the *count* last calls occured on your site.

> Note the `tail` command needs shared memory support on your server, both for web server and command-line configuration. If it's not already the case, you must activate the ***shmop*** PHP module.

Whether it's an interactive session or viewing past calls, you can filter what is displayed as follows:

### Direction

To display only events having a specific direction, use `--direction=<direction>` parameter. `<direction>` can be `both`, `inbound` or `outbound`.

### Field filters

You can filter displayed events on fields too. To do it, use the `--filter=<filter>` parameter. `<filter>` is a json string containing "field":"regexp" pairs. The available fields are: 'authority', 'scheme', 'endpoint', 'verb', 'code', 'message', 'size', 'latency' and 'site_id'.

Each regular expression must be surrounded by `/` like that: `"authority":"/wordpress\.org/"` and the whole filter must start with `'{` and end with `}'` (see examples).

### Columns count

By default, Traffic will output each call string on a 160 character basis. If you want to change it, use `--col=<columns>` where `<columns>` is an integer between 80 and 400.

### Colors scheme

To change the default color scheme to something more *eyes-saving*, use `--theme`.

If you prefer, you can even suppress all colorization with the standard `--no-color` flag.

### Examples

To see all "live" calls, type the following command:
```console
pierre@dev:~$ wp api tail
...
```

To see only past GET calls on wordpress.org APIs, type the following command:
```console
pierre@dev:~$ wp api tail 20 --filter='{"authority":"/wordpress\.org/", "verb":"/GET/"}'
...
```

## Getting Traffic status

To get detailed status and operation mode, use the `wp api status` command.

## Managing main settings

To toggle on/off main settings, use `wp api settings <enable|disable> <inbound-analytics|outbound-analytics|auto-monitoring|smart-filter|metrics>`.

### Available settings

- `inbound-analytics`: if activated, Traffic will analyze inbound API calls (the calls made by external sites or apps to your site).
- `outbound-analytics`: if activated, Traffic will analyze outbound API calls (the calls made by your site to external services).
- `auto-monitoring`: if activated, Traffic will silently start the features needed by live console.
- `smart-filter`: if activated, Traffic will not take into account the calls that generate "noise" in monitoring.
- `metrics`: if activated, Traffic will collate metrics.

### Example

To disable smart filtering without confirmation prompt, type the following command:
```console
wp api settings disable smart-filter --yes
```

## HTTP status codes

Traffic exposes a simple command to let you know all the HTTP status codes it handles.

To list these status codes, use `wp api httpstatus list`.

## Misc flags

For most commands, Traffic lets you use the following flags:
- `--yes`: automatically answer "yes" when a question is prompted during the command execution.
- `--stdout`: outputs a clean STDOUT string so you can pipe or store result of command execution.

> It's not mandatory to use `--stdout` when using `--format=count` or `--format=ids`: in such cases `--stdout` is assumed.

> Note Traffic sets exit code so you can use `$?` to write scripts.
> To know the meaning of Traffic exit codes, just use the command `wp api exitcode list`.