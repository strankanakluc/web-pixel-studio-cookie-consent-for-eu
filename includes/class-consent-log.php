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
			'device_info'  => sanitize_text_field( $data['device_info'] ?? '' ),
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
	 * Get consent records with optional pagination and filters.
	 *
	 * @param array $filters  Keys: 'date' (YYYY-MM-DD), 'consent_id' (partial match).
	 */
	public function get_all( int $per_page = 30, int $page = 1, array $filters = [] ): array {
		global $wpdb;

		$offset = ( $page - 1 ) * $per_page;
		$table  = $this->table;

		[ $where, $where_args ] = $this->build_where( $filters );
		$args = array_merge( $where_args, [ $per_page, $offset ] );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table}{$where} ORDER BY recorded_at DESC LIMIT %d OFFSET %d", ...$args ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $results ?: [];
	}

	public function count( array $filters = [] ): int {
		global $wpdb;
		$table = $this->table;

		[ $where, $where_args ] = $this->build_where( $filters );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $where_args ) ) {
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table}{$where}", ...$where_args ) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	private function build_where( array $filters ): array {
		global $wpdb;
		$conditions = [];
		$args       = [];

		if ( ! empty( $filters['date'] ) ) {
			$date = sanitize_text_field( $filters['date'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
				$conditions[] = 'DATE(recorded_at) = %s';
				$args[]       = $date;
			}
		}

		if ( ! empty( $filters['consent_id'] ) ) {
			$conditions[] = 'consent_id LIKE %s';
			$args[]       = '%' . $wpdb->esc_like( sanitize_text_field( $filters['consent_id'] ) ) . '%';
		}

		$where = empty( $conditions ) ? '' : ' WHERE ' . implode( ' AND ', $conditions );
		return [ $where, $args ];
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

		$headers = [ 'Date', 'ID', 'URL', 'Location', 'IP Address', 'Device Info', 'User Agent', 'Necessary', 'Analytics', 'Targeting', 'Preferences', 'Recorded At', 'Last Update At' ];

		$csv = $this->format_csv_row( $headers );

		foreach ( $rows as $row ) {
			$csv .= $this->format_csv_row( [
				$row['recorded_at'],
				$row['consent_id'],
				$row['url'],
				$row['location'],
				$row['ip_address'],
				$row['device_info'],
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
