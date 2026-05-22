<?php
/**
 * Uninstall routine.
 * Called by WordPress when the plugin is deleted.
 *
 * @package CookieConsentWPS
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop tables.
$ccwps_tables = [
	$wpdb->prefix . 'ccwps_consent_log',
	$wpdb->prefix . 'ccwps_cookies',
	$wpdb->prefix . 'ccwps_blocked_scripts',
];

foreach ( $ccwps_tables as $ccwps_table ) {
	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$ccwps_table}" );
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
}

// Delete all options.
// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin uninstall requires deleting prefixed options in one query.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ccwps_%'" );

// Remove db version.
delete_option( 'ccwps_db_version' );
