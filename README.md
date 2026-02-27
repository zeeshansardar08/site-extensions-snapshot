# Site Extensions Snapshot

A WordPress plugin that adds a dashboard to view installed plugins and themes with status details and CSV export.

## Description

Site Extensions Snapshot adds a Tools page in the WordPress admin where administrators can:

- View all installed plugins with status, version, author, and description
- View all installed themes with status, version, author, and description
- Export the complete list to CSV for documentation or audits
- Search and sort the data

Benefits:
- Faster audits and inventory reports
- Clear visibility into active vs inactive items
- Quick CSV export for compliance or client handoff
- No changes made to plugins or themes

## Features

- Plugin and theme overview with status indicators
- CSV export for plugins and themes
- Search and sortable columns
- Keyboard shortcuts for export and tab switching
- Responsive admin layout

## Requirements

- WordPress 6.0+
- PHP 7.4+
- `manage_options` capability

## Installation

1. Upload the `site-extensions-snapshot` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Go to Tools -> Site Extensions Snapshot.

## Usage

- Use the Plugins and Themes tabs to switch views.
- Click "Export to CSV" to download the current data.
- Use the search box to filter the table and click column headers to sort.

## Hooks and Filters

```php
// Filter plugin data before display
add_filter( 'sesnap_plugins_data', 'my_custom_plugin_filter' );

// Filter theme data before display
add_filter( 'sesnap_themes_data', 'my_custom_theme_filter' );

// Modify CSV export data
add_filter( 'sesnap_csv_data', 'my_custom_csv_filter', 10, 3 );
```

## Internationalization

- Text domain: `site-extensions-snapshot`
- POT file: `languages/site-extensions-snapshot.pot`

## File Structure

```
site-extensions-snapshot/
+-- site-extensions-snapshot.php
+-- includes/
¦   +-- admin-page.php
¦   +-- csv-export.php
+-- assets/
¦   +-- css/
¦   ¦   +-- admin-styles.css
¦   +-- js/
¦       +-- admin-scripts.js
+-- languages/
¦   +-- site-extensions-snapshot.pot
+-- uninstall.php
+-- README.md
```

## License

GPL v2 or later. See `LICENSE.txt`.






