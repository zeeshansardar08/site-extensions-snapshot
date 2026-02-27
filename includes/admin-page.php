<?php
/**
 * Admin Page Handler
 *
 * @package SiteExtensionsSnapshot
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
class Sesnap_Admin_Page {

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
            'sesnap-admin-styles',
            SESNAP_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            SESNAP_VERSION
        );

        wp_enqueue_script(
            'sesnap-admin-scripts',
            SESNAP_PLUGIN_URL . 'assets/js/admin-scripts.js',
            array( 'jquery' ),
            SESNAP_VERSION,
            true
        );

        wp_localize_script(
            'sesnap-admin-scripts',
            'sesnap_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'sesnap_export_nonce' ),
                'strings'  => array(
                    'exporting' => __( 'Exporting...', 'site-extensions-snapshot' ),
                    'error'     => __( 'An error occurred. Please try again.', 'site-extensions-snapshot' ),
                    'search_placeholder' => __( 'Search plugins and themes...', 'site-extensions-snapshot' ),
                    'tooltip_active'     => __( 'This item is currently active and running.', 'site-extensions-snapshot' ),
                    'tooltip_inactive'   => __( 'This item is installed but not currently active.', 'site-extensions-snapshot' ),
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
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'site-extensions-snapshot' ) );
        }

        // Get current tab with nonce verification.
        $allowed_tabs = array( 'plugins', 'themes' );
        $current_tab = 'plugins';
        $requested_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
        $tab_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( $requested_tab && in_array( $requested_tab, $allowed_tabs, true ) && wp_verify_nonce( $tab_nonce, 'sesnap_admin_tab' ) ) {
            $current_tab = $requested_tab;
        }

        $tab_nonce = wp_create_nonce( 'sesnap_admin_tab' );
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

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Site Extensions Snapshot', 'site-extensions-snapshot' ); ?></h1>
            
            <p class="description">
                <?php esc_html_e( 'View and manage all installed plugins and themes. Export the complete list to CSV for documentation purposes.', 'site-extensions-snapshot' ); ?>
            </p>

            <div class="sesnap-export-section">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'sesnap_export_csv', 'sesnap_export_nonce' ); ?>
                    <input type="hidden" name="action" value="sesnap_export_csv">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e( 'Export to CSV', 'site-extensions-snapshot' ); ?>
                    </button>
                </form>

                <div class="sesnap-search-wrap">
                    <span class="dashicons dashicons-search" aria-hidden="true"></span>
                    <input type="search" class="sesnap-search" placeholder="<?php echo esc_attr__( 'Search plugins and themes...', 'site-extensions-snapshot' ); ?>" />
                </div>
            </div>

            <nav class="nav-tab-wrapper">
                <a href="<?php echo esc_url( $plugins_tab_url ); ?>" 
                   class="nav-tab <?php echo esc_attr( 'plugins' === $current_tab ? 'nav-tab-active' : '' ); ?>">
                    <?php esc_html_e( 'Plugins', 'site-extensions-snapshot' ); ?>
                    <span class="sesnap-count"><?php echo esc_html( count( $this->get_plugins_data() ) ); ?></span>
                </a>
                <a href="<?php echo esc_url( $themes_tab_url ); ?>" 
                   class="nav-tab <?php echo esc_attr( 'themes' === $current_tab ? 'nav-tab-active' : '' ); ?>">
                    <?php esc_html_e( 'Themes', 'site-extensions-snapshot' ); ?>
                    <span class="sesnap-count"><?php echo esc_html( count( $this->get_themes_data() ) ); ?></span>
                </a>
            </nav>

            <div class="sesnap-content">
                <?php if ( 'plugins' === $current_tab ) : ?>
                    <?php $this->display_plugins_table(); ?>
                <?php else : ?>
                    <?php $this->display_themes_table(); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display plugins table
     *
     * @since 1.0.0
     */
    private function display_plugins_table() {
        $plugins = $this->get_plugins_data();
        ?>
        <div class="sesnap-table-container">
            <table class="wp-list-table widefat fixed striped">
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
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $plugin['name'] ); ?></strong>
                                </td>
                                <td><?php echo esc_html( $plugin['version'] ); ?></td>
                                <td>
                                    <span class="sesnap-status sesnap-status-<?php echo esc_attr( $plugin['status'] ); ?>">
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
     */
    private function display_themes_table() {
        $themes = $this->get_themes_data();
        ?>
        <div class="sesnap-table-container">
            <table class="wp-list-table widefat fixed striped">
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
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $theme['name'] ); ?></strong>
                                </td>
                                <td><?php echo esc_html( $theme['version'] ); ?></td>
                                <td>
                                    <span class="sesnap-status sesnap-status-<?php echo esc_attr( $theme['status'] ); ?>">
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
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $active_plugins = get_option( 'active_plugins' );
        $plugins_data = array();

        foreach ( $plugins as $plugin_file => $plugin_data ) {
            $plugins_data[] = array(
                'name'        => $plugin_data['Name'],
                'version'     => $plugin_data['Version'],
                'status'      => in_array( $plugin_file, $active_plugins, true ) ? 'active' : 'inactive',
                'author'      => $plugin_data['Author'],
                'description' => $plugin_data['Description'],
            );
        }

        // Sort by name
        usort( $plugins_data, function( $a, $b ) {
            return strcasecmp( $a['name'], $b['name'] );
        } );

        /**
         * Filter plugins data before display.
         *
         * @since 1.0.0
         * @param array $plugins_data Array of plugins data.
         */
        return apply_filters( 'sesnap_plugins_data', $plugins_data );
    }

    /**
     * Get themes data
     *
     * @since 1.0.0
     * @return array
     */
    public function get_themes_data() {
        $themes = wp_get_themes();
        $current_theme = get_stylesheet();
        $themes_data = array();

        foreach ( $themes as $theme_slug => $theme ) {
            $themes_data[] = array(
                'name'        => $theme->get( 'Name' ),
                'version'     => $theme->get( 'Version' ),
                'status'      => $theme_slug === $current_theme ? 'active' : 'inactive',
                'author'      => $theme->get( 'Author' ),
                'description' => $theme->get( 'Description' ),
            );
        }

        // Sort by name
        usort( $themes_data, function( $a, $b ) {
            return strcasecmp( $a['name'], $b['name'] );
        } );

        /**
         * Filter themes data before display.
         *
         * @since 1.0.0
         * @param array $themes_data Array of themes data.
         */
        return apply_filters( 'sesnap_themes_data', $themes_data );
    }
}






