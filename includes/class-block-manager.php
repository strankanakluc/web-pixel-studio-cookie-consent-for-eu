<?php
/**
 * Block manager (CRUD for script blocking rules).
 *
 * @package CookieConsentWPS
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCWPS_Block_Manager {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'ccwps_blocked_scripts';
	}

	public function get_all(): array {
		global $wpdb;
		$table = $this->table;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY category, id", ARRAY_A ) ?: [];
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public function get( int $id ): ?array {
		global $wpdb;
		$table = $this->table;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$id
		), ARRAY_A );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $row ?: null;
	}

	public function insert( array $data ): int|false {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( $this->table, $this->sanitize( $data ) );
		return $result ? (int) $wpdb->insert_id : false;
	}

	public function update( int $id, array $data ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return false !== $wpdb->update( $this->table, $this->sanitize( $data ), [ 'id' => $id ] );
	}

	public function delete( int $id ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return false !== $wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
	}

	public function replace_all( array $rows ): bool {
		global $wpdb;

		$table = $this->table;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$table}" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( $rows as $row ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert( $this->table, $this->sanitize( $row ) );
			if ( false === $inserted ) {
				return false;
			}
		}

		return true;
	}

	private function sanitize( array $data ): array {
		return [
			'script_source' => sanitize_text_field( $data['script_source'] ?? '' ),
			'category'      => sanitize_key( $data['category'] ?? 'analytics' ),
			'is_regex'      => ! empty( $data['is_regex'] ) ? 1 : 0,
			'created_at'    => current_time( 'mysql' ),
		];
	}

	/**
	 * Get rules for frontend JS.
	 */
	public function get_rules_for_frontend(): array {
		$all = $this->get_all();
		$rules = [];
		foreach ( $all as $rule ) {
			$rules[] = [
				'source'  => $rule['script_source'],
				'cat'     => $rule['category'],
				'isRegex' => (bool) $rule['is_regex'],
			];
		}
		return $rules;
	}
}
