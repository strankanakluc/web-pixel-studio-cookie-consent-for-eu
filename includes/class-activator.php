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

	public static function activate(): void {
		self::create_tables();
		self::set_default_options();
		flush_rewrite_rules();
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
		// Load SK preset as default language.
		require_once CCWPS_PLUGIN_DIR . 'includes/class-language-presets.php';
		$sk = CCWPS_Language_Presets::get( 'sk' );
		$sk_strings = $sk ? $sk['strings'] : [];

		$defaults = [
			// Admin language (UI language for admin panel)
			'admin_lang'           => 'sk',
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
			// Banner appearance
			'banner_layout'        => 'box',
			'banner_position'      => 'bottom-left',
			'banner_show_icon'     => 1,
			'icon_position'        => 'bottom-right',
			'icon_type'            => 'cookie',
			'icon_custom_url'      => '',
			'primary_color'        => '#1a73e8',
			'text_color'           => '#1f2937',
			'bg_color'             => '#ffffff',
			'btn_text_color'       => '#ffffff',
			'btn_border_radius'    => 8,
			'font_family'          => 'inherit',
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
			'btn_ghost_border'     => '',
			// Outline button (Manage Preferences)
			'btn_outline_bg'       => '',
			'btn_outline_bg_hv'    => '',
			'btn_outline_txt'      => '',
			'btn_outline_border'   => '',
			// Modal
			'modal_bg'             => '',
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
			// Frontend translations (SK defaults)
			'lang_banner_title'       => $sk_strings['lang_banner_title']       ?? 'Používame súbory cookie',
			'lang_banner_description' => $sk_strings['lang_banner_description'] ?? 'Používame súbory cookie na zlepšenie vášho zážitku z prehliadania.',
			'lang_accept_all'         => $sk_strings['lang_accept_all']         ?? 'Prijať všetky',
			'lang_reject_all'         => $sk_strings['lang_reject_all']         ?? 'Odmietnuť všetky',
			'lang_manage_preferences' => $sk_strings['lang_manage_preferences'] ?? 'Spravovať nastavenia',
			'lang_save_preferences'   => $sk_strings['lang_save_preferences']   ?? 'Uložiť nastavenia',
			'lang_close'              => $sk_strings['lang_close']              ?? 'Zavrieť',
			'lang_necessary_title'    => $sk_strings['lang_necessary_title']    ?? 'Nevyhnutné',
			'lang_necessary_desc'     => $sk_strings['lang_necessary_desc']     ?? 'Nevyhnutné súbory cookie pomáhajú, aby bola webová stránka použiteľná.',
			'lang_analytics_title'    => $sk_strings['lang_analytics_title']    ?? 'Analytické',
			'lang_analytics_desc'     => $sk_strings['lang_analytics_desc']     ?? 'Analytické súbory cookie pomáhajú pochopiť, ako návštevníci používajú web.',
			'lang_targeting_title'    => $sk_strings['lang_targeting_title']    ?? 'Marketingové',
			'lang_targeting_desc'     => $sk_strings['lang_targeting_desc']     ?? 'Marketingové súbory cookie sa používajú na zobrazovanie relevantných reklám.',
			'lang_preferences_title'  => $sk_strings['lang_preferences_title']  ?? 'Preferenčné',
			'lang_preferences_desc'   => $sk_strings['lang_preferences_desc']   ?? 'Preferenčné súbory cookie si pamätajú vaše preferencie.',
			'lang_powered_by'         => 'Web Pixel Studio',
			'lang_consent_id_label'   => 'ID vášho súhlasu',
			'lang_always_on'          => 'Vždy aktívne',
			'lang_cookie_name'        => 'Názov',
			'lang_cookie_domain'      => 'Doména',
			'lang_cookie_expiration'  => 'Platnosť',
			'lang_cookie_description' => 'Popis',
		];

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( 'ccwps_' . $key ) ) {
				update_option( 'ccwps_' . $key, $value );
			}
		}
	}
}
