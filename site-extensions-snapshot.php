<?php
/**
 * Plugin Name: Site Extensions Snapshot
 * Plugin URI:  https://wordpress.org/plugins/site-extensions-snapshot/
 * Description: A comprehensive dashboard to view and export all installed plugins and themes with their status information.
 * Version: 1.0.0
 * Author: Zignites
 * Author URI: https://zignites.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: site-extensions-snapshot
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 *
 * @package Siteexsn
 * @since 1.0.0
 */

// Prevent direct access to this file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'SITEEXSN_VERSION', '1.0.0' );
define( 'SITEEXSN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SITEEXSN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SITEEXSN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
class Siteexsn_Main {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }

    /**
     * Initialize the plugin
     *
     * @since 1.0.0
     */
    public function init() {
        // Load required files.
        $this->load_dependencies();

        // Initialize admin functionality.
        if ( is_admin() ) {
            $this->init_admin();
        }
    }

    /**
     * Load plugin dependencies
     *
     * @since 1.0.0
     */
    private function load_dependencies() {
        require_once SITEEXSN_PLUGIN_DIR . 'includes/admin-page.php';
        require_once SITEEXSN_PLUGIN_DIR . 'includes/csv-export.php';
    }

    /**
     * Initialize admin functionality
     *
     * @since 1.0.0
     */
    private function init_admin() {
        new Siteexsn_Admin_Page();
        new Siteexsn_CSV_Export();
    }


    /**
     * Plugin activation hook
     *
     * @since 1.0.0
     */
    public static function activate() {
        // Check if user has proper permissions
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        // Migrate legacy options from old prefix.
        self::migrate_legacy_options();

        // Add activation timestamp.
        add_option( 'siteexsn_activated', time() );
    }

    /**
     * Migrate legacy options from the old sesnap_ prefix to siteexsn_.
     *
     * @since 1.1.0
     */
    private static function migrate_legacy_options() {
        $legacy_map = array(
            'sesnap_activated' => 'siteexsn_activated',
        );

        foreach ( $legacy_map as $old_key => $new_key ) {
            $old_value = get_option( $old_key );
            if ( false !== $old_value && false === get_option( $new_key ) ) {
                update_option( $new_key, $old_value );
                delete_option( $old_key );
            }
        }
    }

    /**
     * Plugin deactivation hook
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Check if user has proper permissions.
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
    }
}

// Initialize the plugin.
$siteexsn_plugin = new Siteexsn_Main();

// Register activation and deactivation hooks.
register_activation_hook( __FILE__, array( 'Siteexsn_Main', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Siteexsn_Main', 'deactivate' ) ); 






