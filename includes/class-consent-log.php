<?php
/**
 * Consent log manager.
 *
 * @package CookieConsentWPS
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCWPS_Consent_Log {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'ccwps_consent_log';
	}

	/**
	 * Insert or update a consent record.
	 */
	public function save( array $data ): bool {
		global $wpdb;

		$consent_id = sanitize_text_field( $data['consent_id'] ?? '' );
		if ( empty( $consent_id ) ) {
			return false;
		}

		$now = current_time( 'mysql' );
		$table = $this->table;

		// Check if exists.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE consent_id = %s LIMIT 1",
			$consent_id
		) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$row = [
			'consent_id'   => $consent_id,
			'url'          => esc_url_raw( $data['url'] ?? '' ),
			'location'     => sanitize_text_field( $data['location'] ?? '' ),
			'ip_address'   => sanitize_text_field( $data['ip_address'] ?? '' ),
			'user_agent'   => sanitize_textarea_field( $data['user_agent'] ?? '' ),
			'necessary'    => 1,
			'analytics'    => isset( $data['analytics'] ) ? (int) $data['analytics'] : 0,
			'targeting'    => isset( $data['targeting'] ) ? (int) $data['targeting'] : 0,
			'preferences'  => isset( $data['preferences'] ) ? (int) $data['preferences'] : 0,
			'updated_at'   => $now,
		];

		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update( $this->table, $row, [ 'id' => (int) $existing ] );
		} else {
			$row['recorded_at'] = $now;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->insert( $this->table, $row );
		}

		return false !== $result;
	}

	/**
	 * Get all consent records with optional pagination.
	 */
	public function get_all( int $per_page = 50, int $page = 1 ): array {
		global $wpdb;

		$offset = ( $page - 1 ) * $per_page;
		$table  = $this->table;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} ORDER BY recorded_at DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		), ARRAY_A );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $results ?: [];
	}

	public function count(): int {
		global $wpdb;
		$table = $this->table;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Delete all consent records.
	 */
	public function clear_all(): bool {
		global $wpdb;
		$table = $this->table;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return false !== $wpdb->query( "TRUNCATE TABLE {$table}" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Export as CSV string.
	 */
	public function export_csv(): string {
		global $wpdb;
		$table = $this->table;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY recorded_at DESC", ARRAY_A );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $rows ) ) {
			return '';
		}

		$headers = [ 'Date', 'ID', 'URL', 'Location', 'IP Address', 'User Agent', 'Necessary', 'Analytics', 'Targeting', 'Preferences', 'Recorded At', 'Last Update At' ];

		$csv = $this->format_csv_row( $headers );

		foreach ( $rows as $row ) {
			$csv .= $this->format_csv_row( [
				$row['recorded_at'],
				$row['consent_id'],
				$row['url'],
				$row['location'],
				$row['ip_address'],
				$row['user_agent'],
				$row['necessary'] ? 'Yes' : 'No',
				$row['analytics'] ? 'Yes' : 'No',
				$row['targeting'] ? 'Yes' : 'No',
				$row['preferences'] ? 'Yes' : 'No',
				$row['recorded_at'],
				$row['updated_at'],
			] );
		}

		return $csv;
	}

	private function format_csv_row( array $fields ): string {
		$escaped = array_map(
			static function ( $value ): string {
				$field = (string) $value;
				return '"' . str_replace( '"', '""', $field ) . '"';
			},
			$fields
		);

		return implode( ',', $escaped ) . "\n";
	}
}
