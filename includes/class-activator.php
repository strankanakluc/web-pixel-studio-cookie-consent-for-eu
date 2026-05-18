<?php
/**
 * Plugin activator.
 *
 * @package CookieConsentWPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCWPS_Activator {
	private const SCHEMA_VERSION = '4';
	private const DEFAULT_FONT_FAMILY = '\'Poppins\', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';

	public static function activate(): void {
		self::maybe_upgrade();
		flush_rewrite_rules();
	}

	public static function maybe_upgrade(): void {
		$stored_schema_version = (string) get_option( 'ccwps_schema_version', '' );

		// Ak je to nová inštalácia (schema version prázdna), migrácia netreba
		if ( self::SCHEMA_VERSION === $stored_schema_version ) {
			return;
		}

		if ( $stored_schema_version && version_compare( $stored_schema_version, '1.1.0', '<=' ) ) {
			require_once CCWPS_PLUGIN_DIR . 'includes/class-migration.php';
			CCWPS_Migration::maybe_add_device_info_column();
		}

		self::create_tables();
		self::set_default_options();
		self::migrate_legacy_font_family();
		update_option( 'ccwps_schema_version', self::SCHEMA_VERSION );
	}

	private static function migrate_legacy_font_family(): void {
		$font_family = get_option( 'ccwps_font_family', null );

		if ( null === $font_family ) {
			return;
		}

		$normalized = is_string( $font_family ) ? strtolower( trim( $font_family ) ) : '';
		if ( '' === $normalized || 'inherit' === $normalized ) {
			update_option( 'ccwps_font_family', self::DEFAULT_FONT_FAMILY );
		}
	}

	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_log = $wpdb->prefix . 'ccwps_consent_log';
		$sql_log = "CREATE TABLE IF NOT EXISTS {$table_log} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			consent_id VARCHAR(64) NOT NULL,
			url TEXT NOT NULL,
			location VARCHAR(100) DEFAULT '',
			ip_address VARCHAR(45) DEFAULT '',
			user_agent TEXT DEFAULT '',
			device_info VARCHAR(100) DEFAULT '',
			necessary TINYINT(1) DEFAULT 1,
			analytics TINYINT(1) DEFAULT 0,
			targeting TINYINT(1) DEFAULT 0,
			preferences TINYINT(1) DEFAULT 0,
			recorded_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY consent_id (consent_id),
			KEY recorded_at (recorded_at)
		) {$charset_collate};";

		$table_cookies = $wpdb->prefix . 'ccwps_cookies';
		$sql_cookies = "CREATE TABLE IF NOT EXISTS {$table_cookies} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			domain VARCHAR(255) DEFAULT '',
			expiration VARCHAR(100) DEFAULT '',
			path VARCHAR(255) DEFAULT '/',
			description TEXT DEFAULT '',
			is_regex TINYINT(1) DEFAULT 0,
			category VARCHAR(50) DEFAULT 'necessary',
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id)
		) {$charset_collate};";

		$table_blocks = $wpdb->prefix . 'ccwps_blocked_scripts';
		$sql_blocks = "CREATE TABLE IF NOT EXISTS {$table_blocks} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			script_source TEXT NOT NULL,
			category VARCHAR(50) DEFAULT 'analytics',
			is_regex TINYINT(1) DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_log );
		dbDelta( $sql_cookies );
		dbDelta( $sql_blocks );

		update_option( 'ccwps_db_version', CCWPS_VERSION );
	}

	private static function set_default_options(): void {
		// Load EN preset as default language.
		require_once CCWPS_PLUGIN_DIR . 'includes/class-language-presets.php';
		$en = CCWPS_Language_Presets::get( 'en' );
		$en_strings = is_array( $en ) && ! empty( $en['strings'] ) && is_array( $en['strings'] ) ? $en['strings'] : [];
		$en_admin_strings = is_array( $en ) && ! empty( $en['admin_strings'] ) && is_array( $en['admin_strings'] ) ? $en['admin_strings'] : [];

		$defaults = [
			// Admin language (UI language for admin panel)
			'admin_lang'           => 'en',
			// Behavior
			'autorun'              => 1,
			'force_consent'        => 0,
			'auto_clear_cookies'   => 1,
			'page_scripts'         => 1,
			'hide_from_bots'       => 1,
			'reconsent'            => 1,
			'record_consents'      => 1,
			'frontend_detect_visitor_language' => 0,
			'hide_empty_categories'=> 0,
			'delay'                => 0,
			'cookie_expiration'    => 182,
			'cookie_path'          => '/',
			'cookie_domain'        => '',
			// Consent Mode
			'consent_mode_version' => 'v2',
			'gtm_id'               => '',
			// Matomo
			'matomo_url'           => '',
			'matomo_site_id'       => '',
			'matomo_anonymous_without_consent' => 0,
			// Banner appearance
			'banner_layout'        => 'box',
			'banner_position'      => 'middle-center',
			'banner_show_icon'     => 1,
			'floating_show_powered_by' => 0,
			'icon_position'        => 'bottom-left',
			'floating_icon_margin_y' => 0,
			'floating_icon_margin_y_mobile' => 0,
			'icon_type'            => 'cookie',
			'icon_custom_url'      => '',
			'banner_logo_show'     => 0,
			'banner_logo_url'      => '',
			'banner_logo_link_url' => '',
			'banner_logo_width'    => 40,
			'add_cookie_policy_link' => 0,
			'cookie_policy_link_url' => '',
			'cookie_policy_link_text' => 'Cookie Policy',
			'add_privacy_policy_link' => 0,
			'privacy_policy_link_url' => '',
			'privacy_policy_link_text' => 'Privacy Policy',
			// Banner text spacing
			'banner_text_padding_t' => 0,
			'banner_text_padding_r' => 0,
			'banner_text_padding_b' => 0,
			'banner_text_padding_l' => 0,
			'banner_text_margin_t' => 0,
			'banner_text_margin_r' => 0,
			'banner_text_margin_b' => 5,
			'banner_text_margin_l' => 0,
			// Banner legal links spacing
			'banner_links_padding_t' => 0,
			'banner_links_padding_r' => 0,
			'banner_links_padding_b' => 0,
			'banner_links_padding_l' => 0,
			'banner_links_margin_t' => 0,
			'banner_links_margin_r' => 0,
			'banner_links_margin_b' => 30,
			'banner_links_margin_l' => 0,
			// Banner Accept button spacing
			'banner_accept_btn_padding_t' => 11,
			'banner_accept_btn_padding_r' => 20,
			'banner_accept_btn_padding_b' => 11,
			'banner_accept_btn_padding_l' => 20,
			'banner_accept_btn_margin_t' => 0,
			'banner_accept_btn_margin_r' => 0,
			'banner_accept_btn_margin_b' => 0,
			'banner_accept_btn_margin_l' => 0,
			// Banner Reject button spacing
			'banner_reject_btn_padding_t' => 11,
			'banner_reject_btn_padding_r' => 20,
			'banner_reject_btn_padding_b' => 11,
			'banner_reject_btn_padding_l' => 20,
			'banner_reject_btn_margin_t' => 0,
			'banner_reject_btn_margin_r' => 0,
			'banner_reject_btn_margin_b' => 0,
			'banner_reject_btn_margin_l' => 0,
			// Banner Manage button spacing
			'banner_manage_btn_padding_t' => 11,
			'banner_manage_btn_padding_r' => 20,
			'banner_manage_btn_padding_b' => 11,
			'banner_manage_btn_padding_l' => 20,
			'banner_manage_btn_margin_t' => 0,
			'banner_manage_btn_margin_r' => 0,
			'banner_manage_btn_margin_b' => 0,
			'banner_manage_btn_margin_l' => 0,
			'banner_btn_gap'       => 10,
			// Modal body spacing
			'modal_body_padding_t' => 24,
			'modal_body_padding_r' => 28,
			'modal_body_padding_b' => 24,
			'modal_body_padding_l' => 28,
			'modal_body_margin_t' => 0,
			'modal_body_margin_r' => 0,
			'modal_body_margin_b' => 0,
			'modal_body_margin_l' => 0,
			// Modal Save button spacing
			'modal_save_btn_padding_t' => 11,
			'modal_save_btn_padding_r' => 20,
			'modal_save_btn_padding_b' => 11,
			'modal_save_btn_padding_l' => 20,
			'modal_save_btn_margin_t' => 0,
			'modal_save_btn_margin_r' => 0,
			'modal_save_btn_margin_b' => 0,
			'modal_save_btn_margin_l' => 0,
			// Modal Reject button spacing
			'modal_reject_btn_padding_t' => 11,
			'modal_reject_btn_padding_r' => 20,
			'modal_reject_btn_padding_b' => 11,
			'modal_reject_btn_padding_l' => 20,
			'modal_reject_btn_margin_t' => 0,
			'modal_reject_btn_margin_r' => 0,
			'modal_reject_btn_margin_b' => 0,
			'modal_reject_btn_margin_l' => 0,
			// Modal Accept button spacing
			'modal_accept_btn_padding_t' => 11,
			'modal_accept_btn_padding_r' => 20,
			'modal_accept_btn_padding_b' => 11,
			'modal_accept_btn_padding_l' => 20,
			'modal_accept_btn_margin_t' => 0,
			'modal_accept_btn_margin_r' => 0,
			'modal_accept_btn_margin_b' => 0,
			'modal_accept_btn_margin_l' => 0,
			'modal_btn_gap'        => 10,
			// Modal description spacing
			'modal_desc_margin_t' => 0,
			'modal_desc_margin_r' => 0,
			'modal_desc_margin_b' => 6,
			'modal_desc_margin_l' => 0,
			'floating_icon_bg'     => '#1a73e8',
			'floating_icon_bg_hv'  => '#1557b0',
			'floating_icon_color'  => '#ffffff',
			'floating_popup_bg'    => '#ffffff',
			'floating_popup_text'  => '#1f2937',
			'primary_color'        => '#1a73e8',
			'banner_title_color'   => '#1f2937',
			'text_color'           => '#1f2937',
			'bg_color'             => '#ffffff',
			'cloud_bg_opacity'     => 70,
			'btn_text_color'       => '#ffffff',
			'btn_border_radius'    => 8,
			'font_family'          => self::DEFAULT_FONT_FAMILY,
			// Banner box shape
			'banner_border_radius' => 12,
			'banner_shadow'        => '',
			// Primary button (Accept All)
			'btn_primary_bg'       => '',
			'btn_primary_bg_hv'    => '',
			'btn_primary_txt'      => '',
			// Ghost button (Reject)
			'btn_ghost_bg'         => '',
			'btn_ghost_bg_hv'      => '',
			'btn_ghost_txt'        => '',
			'btn_ghost_txt_hv'     => '',
			// Outline button (Manage Preferences)
			'btn_outline_bg'       => '',
			'btn_outline_bg_hv'    => '',
			'btn_outline_txt'      => '',
			'btn_outline_border'   => '',
			// Modal
			'modal_bg'             => '',
			'modal_consent_id_color' => '',
			'modal_header_bg'      => '',
			'modal_footer_bg'      => '',
			'modal_border'         => '',
			'modal_border_radius'  => 12,
			'modal_text'           => '',
			// Category rows
			'cat_header_bg'        => '',
			'cat_header_bg_hv'     => '',
			// Toggle & accents
			'toggle_on_color'      => '',
			'always_on_color'      => '',
			// Frontend translations (EN defaults)
			'lang_banner_title'       => $en_strings['lang_banner_title']       ?? 'We use cookies',
			'lang_banner_description' => $en_strings['lang_banner_description'] ?? 'We use cookies to enhance your browsing experience, serve personalised content, and analyse our traffic. By clicking "Accept All", you consent to our use of cookies.',
			'lang_accept_all'         => $en_strings['lang_accept_all']         ?? 'Accept All',
			'lang_reject_all'         => $en_strings['lang_reject_all']         ?? 'Reject All',
			'lang_manage_preferences' => $en_strings['lang_manage_preferences'] ?? 'Customize',
			'lang_save_preferences'   => $en_strings['lang_save_preferences']   ?? 'Save Preferences',
			'lang_close'              => $en_strings['lang_close']              ?? 'Close',
			'lang_necessary_title'    => $en_strings['lang_necessary_title']    ?? 'Necessary',
			'lang_necessary_desc'     => $en_strings['lang_necessary_desc']     ?? 'Necessary cookies help make a website usable by enabling basic functions like page navigation and access to secure areas. The website cannot function properly without these cookies.',
			'lang_analytics_title'    => $en_strings['lang_analytics_title']    ?? 'Analytics',
			'lang_analytics_desc'     => $en_strings['lang_analytics_desc']     ?? 'Analytics cookies help website owners understand how visitors interact with websites by collecting and reporting information anonymously.',
			'lang_targeting_title'    => $en_strings['lang_targeting_title']    ?? 'Targeting',
			'lang_targeting_desc'     => $en_strings['lang_targeting_desc']     ?? 'Targeting cookies are used to deliver advertisements more relevant to you and your interests. They are also used to limit the number of times you see an advertisement.',
			'lang_preferences_title'  => $en_strings['lang_preferences_title']  ?? 'Preferences',
			'lang_preferences_desc'   => $en_strings['lang_preferences_desc']   ?? 'Preference cookies enable a website to remember information that changes the way the website behaves or looks, like your preferred language or the region you are in.',
			'lang_powered_by'         => 'Web Pixel Studio',
			'lang_consent_id_label'   => 'Your consent ID',
			'lang_always_on'          => 'Always active',
			'lang_cookie_name'        => 'Name',
			'lang_cookie_domain'      => 'Domain',
			'lang_cookie_expiration'  => 'Expiration',
			'lang_cookie_description' => 'Description',
		];

		$defaults = array_merge( $defaults, $en_admin_strings );

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( 'ccwps_' . $key ) ) {
				update_option( 'ccwps_' . $key, $value );
			}
		}
	}
}
