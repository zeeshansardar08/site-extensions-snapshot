<?php
/**
 * CSV Export Handler
 *
 * @package SiteExtensionsSnapshot
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CSV Export Class
 *
 * @since 1.0.0
 */
class Sesnap_CSV_Export {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_post_sesnap_export_csv', array( $this, 'handle_csv_export' ) );
        add_action( 'wp_ajax_sesnap_export_csv', array( $this, 'handle_ajax_csv_export' ) );
    }

    /**
     * Handle CSV export via admin-post.php
     *
     * @since 1.0.0
     */
    public function handle_csv_export() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'site-extensions-snapshot' ) );
        }

        // Verify nonce.
        if ( ! isset( $_POST['sesnap_export_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sesnap_export_nonce'] ) ), 'sesnap_export_csv' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'site-extensions-snapshot' ) );
        }

        $this->generate_csv();
    }

    /**
     * Handle AJAX CSV export
     *
     * @since 1.0.0
     */
    public function handle_ajax_csv_export() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'site-extensions-snapshot' ) );
        }

        // Verify nonce.
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'sesnap_export_nonce' ) ) {
            wp_send_json_error( esc_html__( 'Security check failed.', 'site-extensions-snapshot' ) );
        }

        $this->generate_csv();
    }

    /**
     * Generate and download CSV file
     *
     * @since 1.0.0
     */
    private function generate_csv() {
        // Get data.
        $admin_page = new Sesnap_Admin_Page();
        $plugins_data = $admin_page->get_plugins_data();
        $themes_data = $admin_page->get_themes_data();

        // Prepare CSV data
        $csv_data = array();

        // Add header row
        $csv_data[] = array(
            esc_html__( 'Type', 'site-extensions-snapshot' ),
            esc_html__( 'Name', 'site-extensions-snapshot' ),
            esc_html__( 'Version', 'site-extensions-snapshot' ),
            esc_html__( 'Status', 'site-extensions-snapshot' ),
            esc_html__( 'Author', 'site-extensions-snapshot' ),
            esc_html__( 'Description', 'site-extensions-snapshot' ),
        );

        // Add plugins data
        foreach ( $plugins_data as $plugin ) {
            $csv_data[] = array(
                esc_html__( 'Plugin', 'site-extensions-snapshot' ),
                $plugin['name'],
                $plugin['version'],
                $plugin['status'],
                $plugin['author'],
                $plugin['description'],
            );
        }

        // Add themes data
        foreach ( $themes_data as $theme ) {
            $csv_data[] = array(
                esc_html__( 'Theme', 'site-extensions-snapshot' ),
                $theme['name'],
                $theme['version'],
                $theme['status'],
                $theme['author'],
                $theme['description'],
            );
        }

        /**
         * Filter CSV export data.
         *
         * @since 1.0.0
         * @param array $csv_data     CSV rows.
         * @param array $plugins_data Plugins data.
         * @param array $themes_data  Themes data.
         */
        $csv_data = apply_filters( 'sesnap_csv_data', $csv_data, $plugins_data, $themes_data );

        // Generate filename.
        $filename = 'site-extensions-snapshot_' . gmdate( 'Y-m-d_H-i-s' ) . '.csv';

        // Set headers for CSV download
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        // Add BOM for UTF-8 and output CSV data.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV output is already escaped.
        echo "\xEF\xBB\xBF";
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV output is already escaped.
        echo $this->build_csv_string( $csv_data );

        exit;
    }

    /**
     * Get CSV data as array (for testing purposes)
     *
     * @since 1.0.0
     * @return array
     */
    public function get_csv_data() {
        $admin_page = new Sesnap_Admin_Page();
        $plugins_data = $admin_page->get_plugins_data();
        $themes_data = $admin_page->get_themes_data();

        $csv_data = array();

        // Add header row
        $csv_data[] = array(
            esc_html__( 'Type', 'site-extensions-snapshot' ),
            esc_html__( 'Name', 'site-extensions-snapshot' ),
            esc_html__( 'Version', 'site-extensions-snapshot' ),
            esc_html__( 'Status', 'site-extensions-snapshot' ),
            esc_html__( 'Author', 'site-extensions-snapshot' ),
            esc_html__( 'Description', 'site-extensions-snapshot' ),
        );

        // Add plugins data
        foreach ( $plugins_data as $plugin ) {
            $csv_data[] = array(
                esc_html__( 'Plugin', 'site-extensions-snapshot' ),
                $plugin['name'],
                $plugin['version'],
                $plugin['status'],
                $plugin['author'],
                $plugin['description'],
            );
        }

        // Add themes data
        foreach ( $themes_data as $theme ) {
            $csv_data[] = array(
                esc_html__( 'Theme', 'site-extensions-snapshot' ),
                $theme['name'],
                $theme['version'],
                $theme['status'],
                $theme['author'],
                $theme['description'],
            );
        }

        /**
         * Filter CSV export data.
         *
         * @since 1.0.0
         * @param array $csv_data     CSV rows.
         * @param array $plugins_data Plugins data.
         * @param array $themes_data  Themes data.
         */
        return apply_filters( 'sesnap_csv_data', $csv_data, $plugins_data, $themes_data );
    }

    /**
     * Build a CSV string from rows.
     *
     * @since 1.0.0
     * @param array $rows CSV rows.
     * @return string
     */
    private function build_csv_string( array $rows ) {
        $lines = array();

        foreach ( $rows as $row ) {
            $escaped = array();

            foreach ( $row as $field ) {
                $field = (string) $field;
                $field = str_replace( '"', '""', $field );

                if ( preg_match( '/[",\r\n]/', $field ) ) {
                    $field = '"' . $field . '"';
                }

                $escaped[] = $field;
            }

            $lines[] = implode( ',', $escaped );
        }

        return implode( "\r\n", $lines ) . "\r\n";
    }
} 






