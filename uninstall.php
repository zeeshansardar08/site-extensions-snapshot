<?php
/**
 * Uninstall Site Extensions Snapshot
 *
 * @package SiteExtensionsSnapshot
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

// Delete plugin options.
delete_option( 'sesnap_activated' ); 






