<?php
/**
 * REST API endpoint for consent saving.
 *
 * @package CookieConsentWPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCWPS_Rest_API {

	private CCWPS_Consent_Log $log;
	private CCWPS_Settings $settings;

	public function __construct( CCWPS_Consent_Log $log, CCWPS_Settings $settings ) {
		$this->log      = $log;
		$this->settings = $settings;
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route( 'ccwps/v1', '/consent', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'save_consent' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'consent_id'  => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
				'url'         => [ 'type' => 'string', 'sanitize_callback' => 'esc_url_raw' ],
				'analytics'   => [ 'type' => 'integer', 'default' => 0 ],
				'targeting'   => [ 'type' => 'integer', 'default' => 0 ],
				'preferences' => [ 'type' => 'integer', 'default' => 0 ],
			],
		] );
	}

	public function save_consent( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->settings->get( 'record_consents' ) ) {
			return new WP_REST_Response( [ 'success' => true ], 200 );
		}

		$ip = '';
		$headers = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ];
		foreach ( $headers as $h ) {
			$value = $this->get_server_value( $h );
			if ( '' !== $value ) {
				$candidate = trim( explode( ',', $value )[0] );
				if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
					$ip = $candidate;
					break;
				}
			}
		}

		$data = [
			'consent_id'  => $request['consent_id'],
			'url'         => $request['url'] ?? '',
			'location'    => sanitize_text_field( $request['location'] ?? '' ),
			'ip_address'  => $ip,
			'user_agent'  => sanitize_textarea_field( $this->get_server_value( 'HTTP_USER_AGENT' ) ),
			'analytics'   => (int) $request['analytics'],
			'targeting'   => (int) $request['targeting'],
			'preferences' => (int) $request['preferences'],
		];

		$this->log->save( $data );

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	private function get_server_value( string $key ): string {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_SERVER[ $key ] ) || is_array( $_SERVER[ $key ] ) ) {
			return '';
		}

		$value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return $value;
	}
}
