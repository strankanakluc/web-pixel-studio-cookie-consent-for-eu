<?php
/**
 * Cookie manager (CRUD for cookie declarations).
 *
 * @package CookieConsentWPS
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCWPS_Cookie_Manager {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'ccwps_cookies';
	}

	public function get_all(): array {
		global $wpdb;
		$table = $this->table;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY category, name", ARRAY_A ) ?: [];
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public function get_by_category( string $category ): array {
		global $wpdb;
		$table = $this->table;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE category = %s ORDER BY name",
			$category
		), ARRAY_A ) ?: [];
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
			'name'        => sanitize_text_field( $data['name'] ?? '' ),
			'domain'      => sanitize_text_field( $data['domain'] ?? '' ),
			'expiration'  => sanitize_text_field( $data['expiration'] ?? '' ),
			'path'        => sanitize_text_field( $data['path'] ?? '/' ),
			'description' => sanitize_textarea_field( $data['description'] ?? '' ),
			'is_regex'    => ! empty( $data['is_regex'] ) ? 1 : 0,
			'category'    => sanitize_key( $data['category'] ?? 'necessary' ),
			'created_at'  => current_time( 'mysql' ),
		];
	}

	/**
	 * Get cookies grouped by category for frontend config.
	 */
	public function get_grouped(): array {
		$all = $this->get_all();
		$grouped = [];
		foreach ( $all as $cookie ) {
			$cat = $cookie['category'];
			if ( ! isset( $grouped[ $cat ] ) ) {
				$grouped[ $cat ] = [];
			}
			$grouped[ $cat ][] = [
				'name'       => $cookie['name'],
				'domain'     => $cookie['domain'],
				'expiration' => $cookie['expiration'],
				'path'       => $cookie['path'],
				'desc'       => $cookie['description'],
				'isRegex'    => (bool) $cookie['is_regex'],
			];
		}
		return $grouped;
	}
}
