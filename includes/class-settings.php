<?php
/**
 * Settings helper.
 *
 * @package CookieConsentWPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCWPS_Settings {

	private const REGISTERED_KEYS_OPTION = 'ccwps_registered_keys';

	private array $cache = [];
	private ?array $all_cache = null;

	public function get( string $key, $default = null ) {
		if ( ! array_key_exists( $key, $this->cache ) ) {
			$this->cache[ $key ] = get_option( 'ccwps_' . $key, $default );
		}
		return $this->cache[ $key ];
	}

	public function set( string $key, $value ): bool {
		$this->cache[ $key ] = $value;
		$this->all_cache     = null;
		$this->register_option_key( $key );
		return update_option( 'ccwps_' . $key, $value );
	}

	public function get_all(): array {
		if ( null !== $this->all_cache ) {
			return $this->all_cache;
		}

		$all = [];
		foreach ( $this->get_known_keys() as $key ) {
			$all[ $key ] = $this->get( $key );
		}

		foreach ( $this->get_dynamic_option_keys() as $key ) {
			$all[ $key ] = $this->get( $key );
		}

		$this->all_cache = $all;
		return $this->all_cache;
	}

	private function get_known_keys(): array {
		$keys = [
			'admin_lang',
			'autorun', 'force_consent', 'auto_clear_cookies', 'page_scripts',
			'hide_from_bots', 'reconsent', 'record_consents', 'frontend_detect_visitor_language', 'hide_empty_categories',
			'delay', 'cookie_expiration', 'cookie_path', 'cookie_domain',
			'consent_mode_version', 'gtm_id',
			'banner_layout', 'banner_position', 'banner_show_icon',
			'icon_position', 'icon_type', 'icon_custom_url', 'font_family',
			// Banner box
			'primary_color', 'text_color', 'bg_color', 'btn_text_color', 'btn_border_radius',
			'banner_border_radius', 'banner_shadow',
			// Primary button (Accept All)
			'btn_primary_bg', 'btn_primary_bg_hv', 'btn_primary_txt',
			// Ghost button (Reject/Decline)
			'btn_ghost_bg', 'btn_ghost_bg_hv', 'btn_ghost_txt', 'btn_ghost_border',
			// Outline button (Manage Preferences)
			'btn_outline_bg', 'btn_outline_bg_hv', 'btn_outline_txt', 'btn_outline_border',
			// Modal window
			'modal_bg', 'modal_header_bg', 'modal_footer_bg', 'modal_border', 'modal_border_radius', 'modal_text',
			// Category rows
			'cat_header_bg', 'cat_header_bg_hv',
			// Toggle switch & accents
			'toggle_on_color', 'always_on_color',
			'lang_banner_title', 'lang_banner_description',
			'lang_accept_all', 'lang_reject_all', 'lang_manage_preferences',
			'lang_save_preferences', 'lang_close',
			'lang_necessary_title', 'lang_necessary_desc',
			'lang_analytics_title', 'lang_analytics_desc',
			'lang_targeting_title', 'lang_targeting_desc',
			'lang_preferences_title', 'lang_preferences_desc',
			'lang_powered_by', 'lang_consent_id_label', 'lang_always_on',
			'lang_cookie_name', 'lang_cookie_domain', 'lang_cookie_expiration', 'lang_cookie_description',
		];

		if ( class_exists( 'CCWPS_Language_Presets' ) ) {
			$sk_preset = CCWPS_Language_Presets::get( 'sk' );
			if ( is_array( $sk_preset ) && ! empty( $sk_preset['admin_strings'] ) && is_array( $sk_preset['admin_strings'] ) ) {
				$keys = array_merge( $keys, array_keys( $sk_preset['admin_strings'] ) );
			}
		}

		return array_values( array_unique( $keys ) );
	}

	private function get_dynamic_option_keys(): array {
		$keys = array_merge(
			$this->get_registered_option_keys(),
			$this->get_autoloaded_option_keys()
		);

		$keys = array_filter(
			array_map( [ $this, 'normalize_option_key' ], $keys )
		);

		return array_values( array_unique( $keys ) );
	}

	private function get_registered_option_keys(): array {
		$keys = get_option( self::REGISTERED_KEYS_OPTION, [] );

		return is_array( $keys ) ? $keys : [];
	}

	private function get_autoloaded_option_keys(): array {
		$alloptions = wp_load_alloptions();
		if ( empty( $alloptions ) || ! is_array( $alloptions ) ) {
			return [];
		}

		$keys = [];
		foreach ( array_keys( $alloptions ) as $option_name ) {
			if ( ! is_string( $option_name ) || 0 !== strpos( $option_name, 'ccwps_' ) ) {
				continue;
			}

			$keys[] = $option_name;
		}

		return $keys;
	}

	private function register_option_key( string $key ): void {
		$normalized_key = $this->normalize_option_key( $key );
		if ( '' === $normalized_key ) {
			return;
		}

		$keys   = $this->get_registered_option_keys();
		$keys[] = $normalized_key;
		$keys   = array_values( array_unique( $keys ) );

		update_option( self::REGISTERED_KEYS_OPTION, $keys );
	}

	private function normalize_option_key( $key ): string {
		if ( ! is_string( $key ) || '' === $key ) {
			return '';
		}

		if ( 0 === strpos( $key, 'ccwps_' ) ) {
			$key = substr( $key, 6 );
		}

		if ( '' === $key || 'db_version' === $key || 'registered_keys' === $key ) {
			return '';
		}

		return $key;
	}

	/**
	 * Returns config array for frontend JS.
	 */
	public function get_frontend_config(): array {
		$s = $this->get_all();
		$mode = $s['consent_mode_version'] ?? 'v2';
		$admin_lang = (string) ( $s['admin_lang'] ?? 'sk' );
		$frontend_language_presets = $this->get_frontend_language_presets( $s );
		$current_i18n              = $this->get_current_frontend_i18n( $s );

		return [
			'autorun'              => (bool) $s['autorun'],
			'forceConsent'         => (bool) $s['force_consent'],
			'autoClearCookies'     => (bool) $s['auto_clear_cookies'],
			'manageScriptTags'     => (bool) $s['page_scripts'],
			'hideFromBots'         => (bool) $s['hide_from_bots'],
			'reconsent'            => (bool) $s['reconsent'],
			'recordConsents'       => (bool) $s['record_consents'],
			'hideEmptyCategories'  => (bool) $s['hide_empty_categories'],
			'delay'                => (int) $s['delay'],
			'cookieExpiration'     => (int) $s['cookie_expiration'],
			'cookiePath'           => $s['cookie_path'],
			'cookieDomain'         => $s['cookie_domain'],
			'consentModeVersion'   => $mode,
			'consentModeEnabled'   => in_array( $mode, [ 'v2', 'v3' ], true ),
			'gtmId'                => $s['gtm_id'],
			'detectVisitorLanguage'=> (bool) $s['frontend_detect_visitor_language'],
			'currentFrontendLang'  => $admin_lang,
			'frontendLanguageFallback' => 'en',
			'frontendLanguagePresets'  => $frontend_language_presets,
			'bannerLayout'         => $s['banner_layout'],
			'bannerPosition'       => $s['banner_position'],
			'showFloatingIcon'     => (bool) $s['banner_show_icon'],
			'iconPosition'         => $s['icon_position'],
			'iconType'             => $s['icon_type'],
			'iconCustomUrl'        => $s['icon_custom_url'],
			'colors' => [
				// Base (legacy, also used as fallback)
				'primary'       => $s['primary_color'],
				'text'          => $s['text_color'],
				'bg'            => $s['bg_color'],
				'btnText'       => $s['btn_text_color'],
				'btnRadius'     => (int) $s['btn_border_radius'],
				// Banner box
				'bannerBg'          => $s['bg_color'],
				'bannerBorderRadius'=> (int) ( $s['banner_border_radius'] ?: $s['btn_border_radius'] ?: 12 ),
				'bannerShadow'      => $s['banner_shadow'] ?: '0 12px 40px rgba(0,0,0,.18)',
				// Primary button
				'btnPrimaryBg'      => $s['btn_primary_bg']    ?: $s['primary_color'],
				'btnPrimaryBgHv'    => $s['btn_primary_bg_hv'] ?: '',
				'btnPrimaryTxt'     => $s['btn_primary_txt']   ?: $s['btn_text_color'],
				// Ghost button (Reject)
				'btnGhostBg'        => $s['btn_ghost_bg']      ?: '#f0f2f5',
				'btnGhostBgHv'      => $s['btn_ghost_bg_hv']   ?: '#e5e7eb',
				'btnGhostTxt'       => $s['btn_ghost_txt']     ?: $s['text_color'],
				'btnGhostBorder'    => $s['btn_ghost_border']  ?: '#e5e7eb',
				// Outline button (Manage Preferences)
				'btnOutlineBg'      => $s['btn_outline_bg']     ?: 'transparent',
				'btnOutlineBgHv'    => $s['btn_outline_bg_hv']  ?: $s['primary_color'],
				'btnOutlineTxt'     => $s['btn_outline_txt']    ?: $s['primary_color'],
				'btnOutlineBorder'  => $s['btn_outline_border'] ?: $s['primary_color'],
				// Modal
				'modalBg'           => $s['modal_bg']            ?: $s['bg_color'],
				'modalHeaderBg'     => $s['modal_header_bg']     ?: $s['bg_color'],
				'modalFooterBg'     => $s['modal_footer_bg']     ?: '#f9fafb',
				'modalBorder'       => $s['modal_border']        ?: '#e5e7eb',
				'modalBorderRadius' => (int) ( $s['modal_border_radius'] ?: $s['btn_border_radius'] ?: 12 ),
				'modalText'         => $s['modal_text']          ?: $s['text_color'],
				// Category rows
				'catHeaderBg'       => $s['cat_header_bg']      ?: '#f9fafb',
				'catHeaderBgHv'     => $s['cat_header_bg_hv']   ?: '#f0f2f5',
				// Toggle & accents
				'toggleOnColor'     => $s['toggle_on_color']    ?: $s['primary_color'],
				'alwaysOnColor'     => $s['always_on_color']    ?: $s['primary_color'],
			],
			'fontFamily' => $s['font_family'],
			'i18n' => $current_i18n,
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ccwps_consent' ),
		];
	}

	private function get_current_frontend_i18n( array $settings ): array {
		$admin_lang = (string) ( $settings['admin_lang'] ?? 'sk' );

		return [
			'bannerTitle'       => (string) ( $settings['lang_banner_title'] ?? '' ),
			'bannerDescription' => (string) ( $settings['lang_banner_description'] ?? '' ),
			'acceptAll'         => (string) ( $settings['lang_accept_all'] ?? '' ),
			'rejectAll'         => (string) ( $settings['lang_reject_all'] ?? '' ),
			'managePreferences' => (string) ( $settings['lang_manage_preferences'] ?? '' ),
			'savePreferences'   => (string) ( $settings['lang_save_preferences'] ?? '' ),
			'close'             => (string) ( $settings['lang_close'] ?? '' ),
			'necessaryTitle'    => (string) ( $settings['lang_necessary_title'] ?? '' ),
			'necessaryDesc'     => (string) ( $settings['lang_necessary_desc'] ?? '' ),
			'analyticsTitle'    => (string) ( $settings['lang_analytics_title'] ?? '' ),
			'analyticsDesc'     => (string) ( $settings['lang_analytics_desc'] ?? '' ),
			'targetingTitle'    => (string) ( $settings['lang_targeting_title'] ?? '' ),
			'targetingDesc'     => (string) ( $settings['lang_targeting_desc'] ?? '' ),
			'preferencesTitle'  => (string) ( $settings['lang_preferences_title'] ?? '' ),
			'preferencesDesc'   => (string) ( $settings['lang_preferences_desc'] ?? '' ),
			'poweredBy'         => $this->resolve_powered_by_text( $settings ),
			'consentIdLabel'    => $this->resolve_frontend_text( $settings, 'lang_consent_id_label', 'ID vášho súhlasu', $admin_lang ),
			'alwaysOn'          => $this->resolve_frontend_text( $settings, 'lang_always_on', 'Vždy aktívne', $admin_lang ),
			'cookieName'        => $this->resolve_frontend_text( $settings, 'lang_cookie_name', 'Názov', $admin_lang ),
			'cookieDomain'      => $this->resolve_frontend_text( $settings, 'lang_cookie_domain', 'Doména', $admin_lang ),
			'cookieExpiration'  => $this->resolve_frontend_text( $settings, 'lang_cookie_expiration', 'Platnosť', $admin_lang ),
			'cookieDescription' => $this->resolve_frontend_text( $settings, 'lang_cookie_description', 'Popis', $admin_lang ),
			'consentDateLabel'  => $this->translate_frontend_label( $admin_lang, 'Dátum a čas' ),
			'noConsentYet'      => $this->translate_frontend_label( $admin_lang, 'Súhlas nebol udelený.' ),
		];
	}

	private function get_frontend_language_presets( array $settings ): array {
		$presets      = [];
		$all_presets  = CCWPS_Language_Presets::get_all();
		$option_map   = [
			'lang_banner_title'       => 'bannerTitle',
			'lang_banner_description' => 'bannerDescription',
			'lang_accept_all'         => 'acceptAll',
			'lang_reject_all'         => 'rejectAll',
			'lang_manage_preferences' => 'managePreferences',
			'lang_save_preferences'   => 'savePreferences',
			'lang_close'              => 'close',
			'lang_always_on'          => 'alwaysOn',
			'lang_necessary_title'    => 'necessaryTitle',
			'lang_necessary_desc'     => 'necessaryDesc',
			'lang_analytics_title'    => 'analyticsTitle',
			'lang_analytics_desc'     => 'analyticsDesc',
			'lang_targeting_title'    => 'targetingTitle',
			'lang_targeting_desc'     => 'targetingDesc',
			'lang_preferences_title'  => 'preferencesTitle',
			'lang_preferences_desc'   => 'preferencesDesc',
			'lang_powered_by'         => 'poweredBy',
		];

		foreach ( $all_presets as $lang_code => $preset ) {
			$strings = isset( $preset['strings'] ) && is_array( $preset['strings'] ) ? $preset['strings'] : [];
			$presets[ $lang_code ] = [
				'consentIdLabel'    => $this->translate_frontend_label( $lang_code, 'ID vášho súhlasu' ),
				'alwaysOn'          => $this->translate_frontend_label( $lang_code, 'Vždy aktívne' ),
				'cookieName'        => $this->translate_frontend_label( $lang_code, 'Názov' ),
				'cookieDomain'      => $this->translate_frontend_label( $lang_code, 'Doména' ),
				'cookieExpiration'  => $this->translate_frontend_label( $lang_code, 'Platnosť' ),
				'cookieDescription' => $this->translate_frontend_label( $lang_code, 'Popis' ),
				'consentDateLabel'  => $this->translate_frontend_label( $lang_code, 'Dátum a čas' ),
				'noConsentYet'      => $this->translate_frontend_label( $lang_code, 'Súhlas nebol udelený.' ),
			];

			foreach ( $option_map as $source_key => $target_key ) {
				$presets[ $lang_code ][ $target_key ] = (string) ( $strings[ $source_key ] ?? '' );
			}
		}

		$current_lang = (string) ( $settings['admin_lang'] ?? 'sk' );
		$presets[ $current_lang ] = $this->get_current_frontend_i18n( $settings );

		if ( empty( $presets['en'] ) ) {
			$presets['en'] = $this->get_current_frontend_i18n( array_merge( $settings, [ 'admin_lang' => 'en' ] ) );
		}

		return $presets;
	}

	private function translate_frontend_label( string $lang_code, string $text ): string {
		$translated = CCWPS_Language_Presets::translate_admin_text( $lang_code, $text );

		if ( $translated === $text && 'en' !== $lang_code ) {
			$translated = CCWPS_Language_Presets::translate_admin_text( 'en', $text );
		}

		return $translated;
	}

	private function resolve_frontend_text( array $settings, string $setting_key, string $default_text, string $lang_code ): string {
		$value = $settings[ $setting_key ] ?? null;

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return $this->translate_frontend_label( $lang_code, $default_text );
		}

		$value = trim( $value );
		$english_default = CCWPS_Language_Presets::translate_admin_text( 'en', $default_text );

		if ( 'sk' !== $lang_code && ( $value === $default_text || $value === $english_default ) ) {
			return $this->translate_frontend_label( $lang_code, $default_text );
		}

		return $value;
	}

	private function resolve_powered_by_text( array $settings ): string {
		$value = $settings['lang_powered_by'] ?? '';

		if ( ! is_string( $value ) ) {
			return 'Web Pixel Studio';
		}

		$value = trim( preg_replace( '/^powered\s+by\s+/i', '', $value ) );

		if ( '' === $value || in_array( strtolower( $value ), [ 'cookie consent', 'powered by cookie consent' ], true ) ) {
			return 'Web Pixel Studio';
		}

		return $value;
	}
}
