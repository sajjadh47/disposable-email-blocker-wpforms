<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      2.0.0
 * @package    Disposable_Email_Blocker_Wpforms
 * @author     Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/**
 * Remove plugin added table on uninstall/delete
 */
global $wpdb;

$table_name = $wpdb->prefix . DISPOSABLE_EMAIL_BLOCKER_WPFORMS_PLUGIN_TABLE_NAME;

// Drop the table.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %s', $table_name ) );

// Remove scheduled cron event (if it still exists).
wp_clear_scheduled_hook( 'wpforms_create_disposable_email_domains_table' );

// Remove plugin option.
delete_option( 'debwpforms_db_version' );
