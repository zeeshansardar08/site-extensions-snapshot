<?php
/**
 * Uninstall Site Extensions Snapshot
 *
 * @package Siteexsn
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Check if user has proper permissions.
if ( ! current_user_can( 'activate_plugins' ) ) {
    return;
}

// Delete plugin options (current prefix).
delete_option( 'siteexsn_activated' );

// Delete legacy options (old prefix) if they still exist.
delete_option( 'sesnap_activated' ); 






