<?php
/**
 * AJAX handlers.
 *
 * @package CookieConsentWPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCWPS_Ajax {

	private CCWPS_Consent_Log $log;
	private CCWPS_Settings $settings;

	public function __construct( CCWPS_Consent_Log $log, CCWPS_Settings $settings ) {
		$this->log      = $log;
		$this->settings = $settings;

		// Public (frontend).
		add_action( 'wp_ajax_ccwps_save_consent',        [ $this, 'save_consent' ] );
		add_action( 'wp_ajax_nopriv_ccwps_save_consent', [ $this, 'save_consent' ] );

		// Admin.
		add_action( 'wp_ajax_ccwps_clear_log',        [ $this, 'clear_log' ] );
		add_action( 'wp_ajax_ccwps_save_settings',    [ $this, 'save_settings' ] );
		add_action( 'wp_ajax_ccwps_save_cookie',      [ $this, 'save_cookie' ] );
		add_action( 'wp_ajax_ccwps_delete_cookie',    [ $this, 'delete_cookie' ] );
		add_action( 'wp_ajax_ccwps_save_block',       [ $this, 'save_block' ] );
		add_action( 'wp_ajax_ccwps_delete_block',     [ $this, 'delete_block' ] );
		add_action( 'wp_ajax_ccwps_reset_settings',   [ $this, 'reset_settings' ] );
		add_action( 'wp_ajax_ccwps_save_admin_lang',  [ $this, 'save_admin_lang' ] );
	}

	/* ---- PUBLIC ---- */

	public function save_consent(): void {
		check_ajax_referer( 'ccwps_consent', 'nonce' );

		if ( ! $this->settings->get( 'record_consents' ) ) {
			wp_send_json_success();
		}

		$ip = $this->get_ip();

		$data = [
			'consent_id'  => $this->get_posted_text( 'consent_id' ),
			'url'         => $this->get_posted_url( 'url' ),
			'location'    => $this->get_posted_text( 'location' ),
			'ip_address'  => $ip,
			'user_agent'  => sanitize_textarea_field( $this->get_server_value( 'HTTP_USER_AGENT' ) ),
			'analytics'   => $this->get_posted_int( 'analytics' ),
			'targeting'   => $this->get_posted_int( 'targeting' ),
			'preferences' => $this->get_posted_int( 'preferences' ),
		];

		$this->log->save( $data ) ? wp_send_json_success() : wp_send_json_error( 'Could not save consent.' );
	}

	/* ---- ADMIN ---- */

	public function clear_log(): void {
		check_ajax_referer( 'ccwps_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
		$this->log->clear_all();
		wp_send_json_success();
	}

	public function save_settings(): void {
		check_ajax_referer( 'ccwps_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );

		$allowed_keys = array_keys( $this->settings->get_all() );
		$color_keys   = [
			'primary_color', 'text_color', 'bg_color', 'btn_text_color',
			'btn_primary_bg', 'btn_primary_bg_hv', 'btn_primary_txt',
			'btn_ghost_bg', 'btn_ghost_bg_hv', 'btn_ghost_txt', 'btn_ghost_txt_hv',
			'btn_outline_bg', 'btn_outline_bg_hv', 'btn_outline_txt', 'btn_outline_border',
			'modal_bg', 'modal_header_bg', 'modal_footer_bg', 'modal_border', 'modal_text',
			'cat_header_bg', 'cat_header_bg_hv', 'toggle_on_color', 'always_on_color',
		];
		$html_keys    = [ 'lang_banner_description', 'lang_necessary_desc', 'lang_analytics_desc', 'lang_targeting_desc', 'lang_preferences_desc' ];

		$posted = $this->get_posted_array( 'settings' );

		$url_keys = [ 'icon_custom_url' ];
		foreach ( $allowed_keys as $key ) {
			if ( ! array_key_exists( $key, $posted ) ) continue;
			$value = $posted[ $key ];
			if ( in_array( $key, $color_keys, true ) ) {
				$value = ( $value === 'transparent' ) ? 'transparent' : sanitize_hex_color( $value );
			} elseif ( in_array( $key, $url_keys, true ) ) {
				$value = esc_url_raw( $value );
			} elseif ( in_array( $key, $html_keys, true ) ) {
				$value = wp_kses_post( $value );
			} else {
				$value = sanitize_text_field( $value );
			}
			$this->settings->set( $key, $value );
		}

		wp_send_json_success();
	}

	public function save_cookie(): void {
		check_ajax_referer( 'ccwps_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
		$manager = new CCWPS_Cookie_Manager();
		$id      = $this->get_posted_int( 'id' );
		$data    = [
			'name'        => $this->get_posted_text( 'name' ),
			'domain'      => $this->get_posted_text( 'domain' ),
			'expiration'  => $this->get_posted_text( 'expiration' ),
			'path'        => $this->get_posted_text( 'path', '/' ),
			'description' => $this->get_posted_text( 'description' ),
			'is_regex'    => $this->get_posted_int( 'is_regex' ),
			'category'    => sanitize_key( $this->get_posted_text( 'category', 'necessary' ) ),
		];
		$ok      = $id ? $manager->update( $id, $data ) : (bool) $manager->insert( $data );
		$ok ? wp_send_json_success() : wp_send_json_error( 'Failed to save cookie.' );
	}

	public function delete_cookie(): void {
		check_ajax_referer( 'ccwps_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
		$id = $this->get_posted_int( 'id' );
		( new CCWPS_Cookie_Manager() )->delete( $id ) ? wp_send_json_success() : wp_send_json_error();
	}

	public function save_block(): void {
		check_ajax_referer( 'ccwps_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
		$manager = new CCWPS_Block_Manager();
		$id      = $this->get_posted_int( 'id' );
		$data    = [
			'script_source' => $this->get_posted_text( 'script_source' ),
			'category'      => sanitize_key( $this->get_posted_text( 'category', 'analytics' ) ),
			'is_regex'      => $this->get_posted_int( 'is_regex' ),
		];
		$ok      = $id ? $manager->update( $id, $data ) : (bool) $manager->insert( $data );
		$ok ? wp_send_json_success() : wp_send_json_error( 'Failed to save rule.' );
	}

	public function delete_block(): void {
		check_ajax_referer( 'ccwps_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
		$id = $this->get_posted_int( 'id' );
		( new CCWPS_Block_Manager() )->delete( $id ) ? wp_send_json_success() : wp_send_json_error();
	}

	public function reset_settings(): void {
		check_ajax_referer( 'ccwps_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required wildcard cleanup for plugin options.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ccwps_%' AND option_name != 'ccwps_db_version'" );
		CCWPS_Activator::activate();
		wp_send_json_success();
	}

	public function save_admin_lang(): void {
		check_ajax_referer( 'ccwps_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );

		$lang   = sanitize_key( $this->get_posted_text( 'lang', 'sk' ) );
		$preset = CCWPS_Language_Presets::get( $lang );

		if ( ! $preset ) {
			wp_send_json_error( 'Unknown language.' );
		}

		// Save admin language.
		$this->settings->set( 'admin_lang', $lang );

		// Apply frontend translations.
		$html_keys = [ 'lang_banner_description', 'lang_necessary_desc', 'lang_analytics_desc', 'lang_targeting_desc', 'lang_preferences_desc' ];
		foreach ( $preset['strings'] as $key => $value ) {
			$this->settings->set( $key, in_array( $key, $html_keys, true ) ? wp_kses_post( $value ) : sanitize_text_field( $value ) );
		}

		// Apply admin UI translations (nav labels, preview buttons, etc.).
		if ( ! empty( $preset['admin_strings'] ) ) {
			foreach ( $preset['admin_strings'] as $key => $value ) {
				$this->settings->set( $key, sanitize_text_field( $value ) );
			}
		}

		wp_send_json_success( [ 'redirect' => admin_url( 'admin.php?page=ccwps' ) ] );
	}

	/* ---- HELPERS ---- */

	private function get_ip(): string {
		$headers = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ];
		foreach ( $headers as $h ) {
			$value = $this->get_server_value( $h );
			if ( '' !== $value ) {
				$ip = trim( explode( ',', $value )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
			}
		}
		return '';
	}

	private function get_posted_text( string $key, string $default = '' ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ $key ] ) || is_array( $_POST[ $key ] ) ) {
			return $default;
		}

		$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return $value;
	}

	private function get_posted_url( string $key, string $default = '' ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ $key ] ) || is_array( $_POST[ $key ] ) ) {
			return $default;
		}

		$value = esc_url_raw( wp_unslash( $_POST[ $key ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return $value;
	}

	private function get_posted_int( string $key, int $default = 0 ): int {
		return (int) $this->get_posted_text( $key, (string) $default );
	}

	private function get_posted_array( string $key ): array {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
			return [];
		}

		$value = wp_unslash( $_POST[ $key ] );
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing

		return $value;
	}

	private function get_server_value( string $key ): string {
		if ( ! isset( $_SERVER[ $key ] ) || is_array( $_SERVER[ $key ] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
	}
}
