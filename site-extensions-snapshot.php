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
 * @package SiteExtensionsSnapshot
 * @since 1.0.0
 */

// Prevent direct access to this file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'SESNAP_VERSION', '1.0.0' );
define( 'SESNAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SESNAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SESNAP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
class Site_Extensions_Snapshot {

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
        require_once SESNAP_PLUGIN_DIR . 'includes/admin-page.php';
        require_once SESNAP_PLUGIN_DIR . 'includes/csv-export.php';
    }

    /**
     * Initialize admin functionality
     *
     * @since 1.0.0
     */
    private function init_admin() {
        new Sesnap_Admin_Page();
        new Sesnap_CSV_Export();
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

        // Add activation timestamp.
        add_option( 'sesnap_activated', time() );
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
$sesnap_plugin = new Site_Extensions_Snapshot();

// Register activation and deactivation hooks.
register_activation_hook( __FILE__, array( 'Site_Extensions_Snapshot', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Site_Extensions_Snapshot', 'deactivate' ) ); 






