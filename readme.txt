=== Site Extensions Snapshot ===
Contributors: zeeshansardar08
Tags: plugins, themes, dashboard, export, csv
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A dashboard to view installed plugins and themes with status, plus CSV export.

== Description ==

Site Extensions Snapshot adds a Tools page that lists all installed plugins and themes with their status (active/inactive), versions, authors, and descriptions. It also provides a one-click CSV export for documentation or audits.

Key features:

* Plugins and themes overview with status
* At-a-glance stats cards (total, active, inactive, updates available) that adapt to the current tab
* Filter chips to quickly switch between All / Active / Inactive / Update Available
* "Update Available" badge on items with a pending update
* CSV export of plugins and themes (now includes an Update Available column)
* Search and sortable columns
* Keyboard shortcuts for export and tab switching

Benefits:

* Faster audits and inventory reports
* Clear visibility into active vs inactive items
* Quick CSV export for compliance or client handoff
* No changes made to plugins or themes

Security and access:

* Capability checks (`manage_options`)
* Nonce verification for exports
* Sanitized input and escaped output

== Installation ==

1. Upload the `site-extensions-snapshot` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins menu.
3. Go to Tools -> Site Extensions Snapshot.

== Frequently Asked Questions ==

= Who can access the dashboard? =
Only users with the `manage_options` capability.

= Does this plugin modify plugins or themes? =
No. It only reads installed plugin and theme data and provides a CSV export.

= What data is stored? =
Only a single activation timestamp option is stored (`siteexsn_activated`). No external requests are made.

== Screenshots ==

1. Plugins overview with stats cards, filter chips, and update indicators.
2. Themes tab with status, update detection, and CSV export.
3. At-a-glance stats: total, active, inactive, and updates available.

== Changelog ==

= 1.1.1 =
* Compatibility: Tested with WordPress 7.0.
* Improvement: Accent colours now follow the active admin colour scheme, so the dashboard matches the new "Modern" default admin theme in WordPress 7.0 (and every other scheme) instead of using a fixed blue.
* Improvement: Clearer keyboard focus styles on the filter chips and search field.

= 1.1.0 =
* New: Stats cards showing total, active, inactive and updates-available counts (counts adapt to the selected tab).
* New: Filter chips for All / Active / Inactive / Update Available, combined with the existing search.
* New: "Update Available" badge in plugin and theme rows.
* New: "Update Available" column in CSV export.
* Improvement: Use is_plugin_active() for multisite-safe active-state detection.
* Improvement: Cache plugins/themes data per request to avoid duplicate work on page render.
* Improvement: Scope sortable-table JS to the plugin's own list table so other admin tables are not affected.
* Improvement: Don't hijack keyboard shortcuts while typing in inputs.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.1 =
* WordPress 7.0 compatible. The dashboard now follows your admin colour scheme, including the new "Modern" default.

= 1.1.0 =
* Adds stats cards, filter chips, update-available badges, and an Update Available column in the CSV export.

= 1.0.0 =
* Initial release.






