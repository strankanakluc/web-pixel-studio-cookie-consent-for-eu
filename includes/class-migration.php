<?php
/**
 * Database migration for consent log (device_info column)
 *
 * @package CookieConsentWPS
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCWPS_Migration {
	/**
	 * Adds device_info column if missing (for upgrades from <= 1.1.0)
	 */
	public static function maybe_add_device_info_column(): void {
		global $wpdb;
		
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return;
		}

		$table = $wpdb->prefix . 'ccwps_consent_log';

		// Skontroluje, či tabuľka existuje
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );

		if ( ! $table_exists ) {
			return; // Tabuľka neexistuje, nič netreba robiť
		}

		// Skontroluje, či stĺpec device_info už existuje
		$result = $wpdb->get_results( "DESC {$table}", ARRAY_A );
		
		if ( empty( $result ) ) {
			return; // Chyba pri čítaní tabuľky
		}

		$column_exists = false;
		foreach ( $result as $row ) {
			if ( isset( $row['Field'] ) && 'device_info' === $row['Field'] ) {
				$column_exists = true;
				break;
			}
		}

		// Ak device_info stĺpec neexistuje, pridáme ho
		if ( ! $column_exists ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN device_info VARCHAR(100) DEFAULT '' AFTER user_agent" );
		}
	}
}

// phpcs:enable

