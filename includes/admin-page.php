<?php
/**
 * Admin Page Handler
 *
 * @package Siteexsn
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Page Class
 *
 * @since 1.0.0
 */
class Siteexsn_Admin_Page {

    /**
     * Cached plugins data for the current request.
     *
     * @since 1.1.0
     * @var array|null
     */
    private $plugins_cache = null;

    /**
     * Cached themes data for the current request.
     *
     * @since 1.1.0
     * @var array|null
     */
    private $themes_cache = null;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * Add admin menu
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        add_management_page(
            esc_html__( 'Site Extensions Snapshot', 'site-extensions-snapshot' ),
            esc_html__( 'Site Extensions Snapshot', 'site-extensions-snapshot' ),
            'manage_options',
            'site-extensions-snapshot',
            array( $this, 'admin_page_content' )
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @since 1.0.0
     * @param string $hook_suffix The current admin page.
     */
    public function enqueue_admin_scripts( $hook_suffix ) {
        if ( 'tools_page_site-extensions-snapshot' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'siteexsn-admin-styles',
            SITEEXSN_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            SITEEXSN_VERSION
        );

        wp_enqueue_script(
            'siteexsn-admin-scripts',
            SITEEXSN_PLUGIN_URL . 'assets/js/admin-scripts.js',
            array( 'jquery' ),
            SITEEXSN_VERSION,
            true
        );

        wp_localize_script(
            'siteexsn-admin-scripts',
            'siteexsn_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'siteexsn_export_nonce' ),
                'strings'  => array(
                    'exporting'          => __( 'Exporting...', 'site-extensions-snapshot' ),
                    'error'              => __( 'An error occurred. Please try again.', 'site-extensions-snapshot' ),
                    'search_placeholder' => __( 'Search plugins and themes...', 'site-extensions-snapshot' ),
                    'tooltip_active'     => __( 'This item is currently active and running.', 'site-extensions-snapshot' ),
                    'tooltip_inactive'   => __( 'This item is installed but not currently active.', 'site-extensions-snapshot' ),
                    'tooltip_update'     => __( 'A new version is available.', 'site-extensions-snapshot' ),
                ),
            )
        );
    }

    /**
     * Admin page content
     *
     * @since 1.0.0
     */
    public function admin_page_content() {
        // Check user capabilities.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'site-extensions-snapshot' ) );
        }

        // Get current tab with nonce verification.
        $allowed_tabs  = array( 'plugins', 'themes' );
        $current_tab   = 'plugins';
        $requested_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
        $tab_nonce     = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( $requested_tab && in_array( $requested_tab, $allowed_tabs, true ) && wp_verify_nonce( $tab_nonce, 'siteexsn_admin_tab' ) ) {
            $current_tab = $requested_tab;
        }

        $tab_nonce       = wp_create_nonce( 'siteexsn_admin_tab' );
        $plugins_tab_url = add_query_arg(
            array(
                'page'     => 'site-extensions-snapshot',
                'tab'      => 'plugins',
                '_wpnonce' => $tab_nonce,
            ),
            admin_url( 'tools.php' )
        );
        $themes_tab_url = add_query_arg(
            array(
                'page'     => 'site-extensions-snapshot',
                'tab'      => 'themes',
                '_wpnonce' => $tab_nonce,
            ),
            admin_url( 'tools.php' )
        );

        $plugins_data = $this->get_plugins_data();
        $themes_data  = $this->get_themes_data();
        $stats        = $this->get_stats( 'plugins' === $current_tab ? $plugins_data : $themes_data );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Site Extensions Snapshot', 'site-extensions-snapshot' ); ?></h1>

            <p class="description">
                <?php esc_html_e( 'View and manage all installed plugins and themes. Export the complete list to CSV for documentation purposes.', 'site-extensions-snapshot' ); ?>
            </p>

            <?php $this->display_stats_cards( $stats, $current_tab ); ?>

            <div class="siteexsn-export-section">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'siteexsn_export_csv', 'siteexsn_export_nonce' ); ?>
                    <input type="hidden" name="action" value="siteexsn_export_csv">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e( 'Export to CSV', 'site-extensions-snapshot' ); ?>
                    </button>
                </form>

                <div class="siteexsn-search-wrap">
                    <span class="dashicons dashicons-search" aria-hidden="true"></span>
                    <input type="search" class="siteexsn-search" placeholder="<?php echo esc_attr__( 'Search plugins and themes...', 'site-extensions-snapshot' ); ?>" />
                </div>
            </div>

            <nav class="nav-tab-wrapper">
                <a href="<?php echo esc_url( $plugins_tab_url ); ?>"
                   class="nav-tab <?php echo esc_attr( 'plugins' === $current_tab ? 'nav-tab-active' : '' ); ?>">
                    <?php esc_html_e( 'Plugins', 'site-extensions-snapshot' ); ?>
                    <span class="siteexsn-count"><?php echo esc_html( count( $plugins_data ) ); ?></span>
                </a>
                <a href="<?php echo esc_url( $themes_tab_url ); ?>"
                   class="nav-tab <?php echo esc_attr( 'themes' === $current_tab ? 'nav-tab-active' : '' ); ?>">
                    <?php esc_html_e( 'Themes', 'site-extensions-snapshot' ); ?>
                    <span class="siteexsn-count"><?php echo esc_html( count( $themes_data ) ); ?></span>
                </a>
            </nav>

            <?php $this->display_filter_chips( $stats ); ?>

            <div class="siteexsn-content">
                <?php if ( 'plugins' === $current_tab ) : ?>
                    <?php $this->display_plugins_table( $plugins_data ); ?>
                <?php else : ?>
                    <?php $this->display_themes_table( $themes_data ); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Compute stats for a dataset.
     *
     * @since 1.1.0
     * @param array $items Plugins or themes data.
     * @return array {
     *     @type int $total
     *     @type int $active
     *     @type int $inactive
     *     @type int $updates
     * }
     */
    private function get_stats( array $items ) {
        $total    = count( $items );
        $active   = 0;
        $updates  = 0;

        foreach ( $items as $item ) {
            if ( isset( $item['status'] ) && 'active' === $item['status'] ) {
                $active++;
            }
            if ( ! empty( $item['update_available'] ) ) {
                $updates++;
            }
        }

        return array(
            'total'    => $total,
            'active'   => $active,
            'inactive' => max( 0, $total - $active ),
            'updates'  => $updates,
        );
    }

    /**
     * Display stats summary cards.
     *
     * @since 1.1.0
     * @param array  $stats   Stats array from get_stats().
     * @param string $context 'plugins' or 'themes'.
     */
    private function display_stats_cards( array $stats, $context ) {
        $is_plugins = ( 'plugins' === $context );
        $total_label    = $is_plugins ? __( 'Total Plugins', 'site-extensions-snapshot' ) : __( 'Total Themes', 'site-extensions-snapshot' );
        $active_label   = $is_plugins ? __( 'Active Plugins', 'site-extensions-snapshot' ) : __( 'Active Theme', 'site-extensions-snapshot' );
        $inactive_label = $is_plugins ? __( 'Inactive Plugins', 'site-extensions-snapshot' ) : __( 'Inactive Themes', 'site-extensions-snapshot' );
        $updates_label  = __( 'Updates Available', 'site-extensions-snapshot' );
        ?>
        <div class="siteexsn-stats" role="group" aria-label="<?php echo esc_attr__( 'Summary statistics', 'site-extensions-snapshot' ); ?>">
            <div class="siteexsn-stat-card siteexsn-stat-total">
                <span class="siteexsn-stat-icon dashicons dashicons-screenoptions" aria-hidden="true"></span>
                <span class="siteexsn-stat-value"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
                <span class="siteexsn-stat-label"><?php echo esc_html( $total_label ); ?></span>
            </div>
            <div class="siteexsn-stat-card siteexsn-stat-active">
                <span class="siteexsn-stat-icon dashicons dashicons-yes-alt" aria-hidden="true"></span>
                <span class="siteexsn-stat-value"><?php echo esc_html( number_format_i18n( $stats['active'] ) ); ?></span>
                <span class="siteexsn-stat-label"><?php echo esc_html( $active_label ); ?></span>
            </div>
            <div class="siteexsn-stat-card siteexsn-stat-inactive">
                <span class="siteexsn-stat-icon dashicons dashicons-marker" aria-hidden="true"></span>
                <span class="siteexsn-stat-value"><?php echo esc_html( number_format_i18n( $stats['inactive'] ) ); ?></span>
                <span class="siteexsn-stat-label"><?php echo esc_html( $inactive_label ); ?></span>
            </div>
            <div class="siteexsn-stat-card siteexsn-stat-updates">
                <span class="siteexsn-stat-icon dashicons dashicons-update" aria-hidden="true"></span>
                <span class="siteexsn-stat-value"><?php echo esc_html( number_format_i18n( $stats['updates'] ) ); ?></span>
                <span class="siteexsn-stat-label"><?php echo esc_html( $updates_label ); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Display filter chips (client-side filtering).
     *
     * @since 1.1.0
     * @param array $stats Stats array.
     */
    private function display_filter_chips( array $stats ) {
        ?>
        <div class="siteexsn-filter-chips" role="tablist" aria-label="<?php echo esc_attr__( 'Filter items', 'site-extensions-snapshot' ); ?>">
            <button type="button" class="siteexsn-chip is-active" data-filter="all" role="tab" aria-selected="true">
                <?php esc_html_e( 'All', 'site-extensions-snapshot' ); ?>
                <span class="siteexsn-chip-count"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
            </button>
            <button type="button" class="siteexsn-chip" data-filter="active" role="tab" aria-selected="false">
                <?php esc_html_e( 'Active', 'site-extensions-snapshot' ); ?>
                <span class="siteexsn-chip-count"><?php echo esc_html( number_format_i18n( $stats['active'] ) ); ?></span>
            </button>
            <button type="button" class="siteexsn-chip" data-filter="inactive" role="tab" aria-selected="false">
                <?php esc_html_e( 'Inactive', 'site-extensions-snapshot' ); ?>
                <span class="siteexsn-chip-count"><?php echo esc_html( number_format_i18n( $stats['inactive'] ) ); ?></span>
            </button>
            <button type="button" class="siteexsn-chip" data-filter="update" role="tab" aria-selected="false">
                <?php esc_html_e( 'Update Available', 'site-extensions-snapshot' ); ?>
                <span class="siteexsn-chip-count"><?php echo esc_html( number_format_i18n( $stats['updates'] ) ); ?></span>
            </button>
        </div>
        <?php
    }

    /**
     * Display plugins table
     *
     * @since 1.0.0
     * @param array $plugins Plugins data.
     */
    private function display_plugins_table( array $plugins ) {
        ?>
        <div class="siteexsn-table-container">
            <table class="wp-list-table widefat fixed striped siteexsn-list-table">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e( 'Plugin Name', 'site-extensions-snapshot' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Version', 'site-extensions-snapshot' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Status', 'site-extensions-snapshot' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Author', 'site-extensions-snapshot' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Description', 'site-extensions-snapshot' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $plugins ) ) : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'No plugins found.', 'site-extensions-snapshot' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $plugins as $plugin ) : ?>
                            <tr data-status="<?php echo esc_attr( $plugin['status'] ); ?>" data-update="<?php echo empty( $plugin['update_available'] ) ? '0' : '1'; ?>">
                                <td>
                                    <strong><?php echo esc_html( $plugin['name'] ); ?></strong>
                                    <?php if ( ! empty( $plugin['update_available'] ) ) : ?>
                                        <span class="siteexsn-update-badge" title="<?php echo esc_attr__( 'A new version is available.', 'site-extensions-snapshot' ); ?>">
                                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                                            <?php esc_html_e( 'Update', 'site-extensions-snapshot' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $plugin['version'] ); ?></td>
                                <td>
                                    <span class="siteexsn-status siteexsn-status-<?php echo esc_attr( $plugin['status'] ); ?>">
                                        <?php echo esc_html( $plugin['status'] ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $plugin['author'] ); ?></td>
                                <td><?php echo esc_html( $plugin['description'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Display themes table
     *
     * @since 1.0.0
     * @param array $themes Themes data.
     */
    private function display_themes_table( array $themes ) {
        ?>
        <div class="siteexsn-table-container">
            <table class="wp-list-table widefat fixed striped siteexsn-list-table">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e( 'Theme Name', 'site-extensions-snapshot' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Version', 'site-extensions-snapshot' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Status', 'site-extensions-snapshot' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Author', 'site-extensions-snapshot' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Description', 'site-extensions-snapshot' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $themes ) ) : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'No themes found.', 'site-extensions-snapshot' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $themes as $theme ) : ?>
                            <tr data-status="<?php echo esc_attr( $theme['status'] ); ?>" data-update="<?php echo empty( $theme['update_available'] ) ? '0' : '1'; ?>">
                                <td>
                                    <strong><?php echo esc_html( $theme['name'] ); ?></strong>
                                    <?php if ( ! empty( $theme['update_available'] ) ) : ?>
                                        <span class="siteexsn-update-badge" title="<?php echo esc_attr__( 'A new version is available.', 'site-extensions-snapshot' ); ?>">
                                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                                            <?php esc_html_e( 'Update', 'site-extensions-snapshot' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $theme['version'] ); ?></td>
                                <td>
                                    <span class="siteexsn-status siteexsn-status-<?php echo esc_attr( $theme['status'] ); ?>">
                                        <?php echo esc_html( $theme['status'] ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $theme['author'] ); ?></td>
                                <td><?php echo esc_html( $theme['description'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Get plugins data
     *
     * @since 1.0.0
     * @return array
     */
    public function get_plugins_data() {
        if ( null !== $this->plugins_cache ) {
            return $this->plugins_cache;
        }

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins         = get_plugins();
        $update_plugins  = get_site_transient( 'update_plugins' );
        $pending_updates = ( isset( $update_plugins->response ) && is_array( $update_plugins->response ) )
            ? $update_plugins->response
            : array();

        $plugins_data = array();

        foreach ( $plugins as $plugin_file => $plugin_data ) {
            $plugins_data[] = array(
                'name'             => $plugin_data['Name'],
                'version'          => $plugin_data['Version'],
                'status'           => is_plugin_active( $plugin_file ) ? 'active' : 'inactive',
                'author'           => wp_strip_all_tags( $plugin_data['Author'] ),
                'description'      => wp_strip_all_tags( $plugin_data['Description'] ),
                'update_available' => isset( $pending_updates[ $plugin_file ] ),
            );
        }

        usort(
            $plugins_data,
            function ( $a, $b ) {
                return strcasecmp( $a['name'], $b['name'] );
            }
        );

        /**
         * Filter plugins data before display.
         *
         * @since 1.0.0
         * @param array $plugins_data Array of plugins data.
         */
        $this->plugins_cache = apply_filters( 'siteexsn_plugins_data', $plugins_data );

        return $this->plugins_cache;
    }

    /**
     * Get themes data
     *
     * @since 1.0.0
     * @return array
     */
    public function get_themes_data() {
        if ( null !== $this->themes_cache ) {
            return $this->themes_cache;
        }

        $themes         = wp_get_themes();
        $current_theme  = get_stylesheet();
        $update_themes  = get_site_transient( 'update_themes' );
        $pending_updates = ( isset( $update_themes->response ) && is_array( $update_themes->response ) )
            ? $update_themes->response
            : array();

        $themes_data = array();

        foreach ( $themes as $theme_slug => $theme ) {
            $themes_data[] = array(
                'name'             => $theme->get( 'Name' ),
                'version'          => $theme->get( 'Version' ),
                'status'           => $theme_slug === $current_theme ? 'active' : 'inactive',
                'author'           => wp_strip_all_tags( $theme->get( 'Author' ) ),
                'description'      => wp_strip_all_tags( $theme->get( 'Description' ) ),
                'update_available' => isset( $pending_updates[ $theme_slug ] ),
            );
        }

        usort(
            $themes_data,
            function ( $a, $b ) {
                return strcasecmp( $a['name'], $b['name'] );
            }
        );

        /**
         * Filter themes data before display.
         *
         * @since 1.0.0
         * @param array $themes_data Array of themes data.
         */
        $this->themes_cache = apply_filters( 'siteexsn_themes_data', $themes_data );

        return $this->themes_cache;
    }
}
