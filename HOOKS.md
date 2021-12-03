This plugin has a number of hooks that you can use, as developer or as a user, to customize the user experience or to give access to extended functionalities.

## Customization of path cutting
It is possible to customize the default path cutting value (set in the plugin settings) based on the host, the endpoint and the bound.

To do so, use the `traffic_path_level` filter.

#### Example
```php
  add_filter(
    'traffic_path_level',
    function( $cut, $host, $endpoint, $bound ) {
      if ( 'inbound' === $bound && false !== strpos( $endpoint, '/beacon' ) ) {
        $cut = 1;
      }
      if ( 'outbound' === $bound && false !== strpos( $host, 'wordpress.org' ) ) {
        $cut = 5;
      }
      return $cut;
    },
    10,
    4
  );
```

## Customization of PerfOps One menus
You can use the `poo_hide_main_menu` filter to completely hide the main PerfOps One menu or use the `poo_hide_analytics_menu`, `poo_hide_consoles_menu`, `poo_hide_insights_menu`, `poo_hide_tools_menu`, `poo_hide_records_menu` and `poo_hide_settings_menu` filters to selectively hide submenus.

### Example
Hide the main menu:
```php
  add_filter( 'poo_hide_main_menu', '__return_true' );
```

## Customization of the admin bar
You can use the `poo_hide_adminbar` filter to completely hide this plugin's item(s) from the admin bar.

### Example
Remove this plugin's item(s) from the admin bar:
```php
  add_filter( 'poo_hide_adminbar', '__return_true' );
```

## Advanced settings and controls
By default, advanced settings and controls are hidden to avoid cluttering admin screens. Nevertheless, if this plugin have such settings and controls, you can force them to display with `perfopsone_show_advanced` filter.

### Example
Display advanced settings and controls in admin screens:
```php
  add_filter( 'perfopsone_show_advanced', '__return_true' );
```