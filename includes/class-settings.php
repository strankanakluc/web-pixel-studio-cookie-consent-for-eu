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
			'matomo_url', 'matomo_site_id', 'matomo_anonymous_without_consent',
			'banner_layout', 'banner_position', 'banner_show_icon',
			'floating_show_powered_by',
			'icon_position', 'icon_type', 'icon_custom_url', 'font_family',
			'banner_logo_show', 'banner_logo_url', 'banner_logo_link_url', 'banner_logo_width',
			'add_cookie_policy_link', 'cookie_policy_link_url', 'cookie_policy_link_text',
			'add_privacy_policy_link', 'privacy_policy_link_url', 'privacy_policy_link_text',
			'banner_text_padding_t', 'banner_text_padding_r', 'banner_text_padding_b', 'banner_text_padding_l',
			'banner_text_margin_t', 'banner_text_margin_r', 'banner_text_margin_b', 'banner_text_margin_l',
			'banner_links_padding_t', 'banner_links_padding_r', 'banner_links_padding_b', 'banner_links_padding_l',
			'banner_links_margin_t', 'banner_links_margin_r', 'banner_links_margin_b', 'banner_links_margin_l',
			'banner_accept_btn_padding_t', 'banner_accept_btn_padding_r', 'banner_accept_btn_padding_b', 'banner_accept_btn_padding_l',
			'banner_accept_btn_margin_t', 'banner_accept_btn_margin_r', 'banner_accept_btn_margin_b', 'banner_accept_btn_margin_l',
			'banner_reject_btn_padding_t', 'banner_reject_btn_padding_r', 'banner_reject_btn_padding_b', 'banner_reject_btn_padding_l',
			'banner_reject_btn_margin_t', 'banner_reject_btn_margin_r', 'banner_reject_btn_margin_b', 'banner_reject_btn_margin_l',
			'banner_manage_btn_padding_t', 'banner_manage_btn_padding_r', 'banner_manage_btn_padding_b', 'banner_manage_btn_padding_l',
			'banner_manage_btn_margin_t', 'banner_manage_btn_margin_r', 'banner_manage_btn_margin_b', 'banner_manage_btn_margin_l',
			'banner_btn_gap',
			'modal_body_padding_t', 'modal_body_padding_r', 'modal_body_padding_b', 'modal_body_padding_l',
			'modal_body_margin_t', 'modal_body_margin_r', 'modal_body_margin_b', 'modal_body_margin_l',
			'modal_save_btn_padding_t', 'modal_save_btn_padding_r', 'modal_save_btn_padding_b', 'modal_save_btn_padding_l',
			'modal_save_btn_margin_t', 'modal_save_btn_margin_r', 'modal_save_btn_margin_b', 'modal_save_btn_margin_l',
			'modal_reject_btn_padding_t', 'modal_reject_btn_padding_r', 'modal_reject_btn_padding_b', 'modal_reject_btn_padding_l',
			'modal_reject_btn_margin_t', 'modal_reject_btn_margin_r', 'modal_reject_btn_margin_b', 'modal_reject_btn_margin_l',
			'modal_accept_btn_padding_t', 'modal_accept_btn_padding_r', 'modal_accept_btn_padding_b', 'modal_accept_btn_padding_l',
			'modal_accept_btn_margin_t', 'modal_accept_btn_margin_r', 'modal_accept_btn_margin_b', 'modal_accept_btn_margin_l',
			'modal_btn_gap',
			'modal_desc_margin_t', 'modal_desc_margin_r', 'modal_desc_margin_b', 'modal_desc_margin_l',
			'floating_icon_bg', 'floating_icon_bg_hv', 'floating_icon_color', 'floating_popup_bg', 'floating_popup_text',
			// Banner box
			'primary_color', 'banner_title_color', 'text_color', 'bg_color', 'btn_text_color', 'btn_border_radius',
			'banner_border_radius', 'banner_shadow', 'cloud_bg_opacity',
			// Primary button (Accept All)
			'btn_primary_bg', 'btn_primary_bg_hv', 'btn_primary_txt',
			// Ghost button (Reject/Decline)
			'btn_ghost_bg', 'btn_ghost_bg_hv', 'btn_ghost_txt', 'btn_ghost_txt_hv',
			// Outline button (Manage Preferences)
			'btn_outline_bg', 'btn_outline_bg_hv', 'btn_outline_txt', 'btn_outline_border',
			// Modal window
			'modal_bg', 'modal_header_bg', 'modal_footer_bg', 'modal_border', 'modal_border_radius', 'modal_text', 'modal_consent_id_color',
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

		$opt = static function ( array $source, string $key, $default ) {
			return array_key_exists( $key, $source ) && null !== $source[ $key ] ? $source[ $key ] : $default;
		};

		$mode = (string) $opt( $s, 'consent_mode_version', 'v2' );
		$matomo_url = (string) $opt( $s, 'matomo_url', '' );
		$matomo_site_id = (int) $opt( $s, 'matomo_site_id', 0 );
		$matomo_host = (string) ( wp_parse_url( $matomo_url, PHP_URL_HOST ) ?: '' );
		$admin_lang = (string) $opt( $s, 'admin_lang', 'en' );
		$frontend_language_presets = $this->get_frontend_language_presets( $s );
		$current_i18n              = $this->get_current_frontend_i18n( $s );

		return [
			'autorun'              => (bool) $opt( $s, 'autorun', 1 ),
			'forceConsent'         => (bool) $opt( $s, 'force_consent', 0 ),
			'autoClearCookies'     => (bool) $opt( $s, 'auto_clear_cookies', 1 ),
			'manageScriptTags'     => (bool) $opt( $s, 'page_scripts', 1 ),
			'hideFromBots'         => (bool) $opt( $s, 'hide_from_bots', 1 ),
			'reconsent'            => (bool) $opt( $s, 'reconsent', 1 ),
			'recordConsents'       => (bool) $opt( $s, 'record_consents', 1 ),
			'hideEmptyCategories'  => (bool) $opt( $s, 'hide_empty_categories', 0 ),
			'delay'                => (int) $opt( $s, 'delay', 0 ),
			'cookieExpiration'     => (int) $opt( $s, 'cookie_expiration', 182 ),
			'cookiePath'           => (string) $opt( $s, 'cookie_path', '/' ),
			'cookieDomain'         => (string) $opt( $s, 'cookie_domain', '' ),
			'consentModeVersion'   => $mode,
			'consentModeEnabled'   => in_array( $mode, [ 'v2', 'v3' ], true ),
			'gtmId'                => (string) $opt( $s, 'gtm_id', '' ),
			'matomoEnabled'        => '' !== trim( $matomo_url ) && $matomo_site_id > 0,
			'matomoHost'           => $matomo_host,
			'matomoAnonymousWithoutConsent' => (bool) $opt( $s, 'matomo_anonymous_without_consent', 0 ),
			'detectVisitorLanguage'=> (bool) $opt( $s, 'frontend_detect_visitor_language', 0 ),
			'currentFrontendLang'  => $admin_lang,
			'frontendLanguageFallback' => 'en',
			'frontendLanguagePresets'  => $frontend_language_presets,
			'bannerLayout'         => (string) $opt( $s, 'banner_layout', 'box' ),
			'bannerPosition'       => (string) $opt( $s, 'banner_position', 'middle-center' ),
			'showFloatingIcon'     => (bool) $opt( $s, 'banner_show_icon', 1 ),
			'showPoweredByLink'    => (bool) $opt( $s, 'floating_show_powered_by', 0 ),
			'iconPosition'         => (string) $opt( $s, 'icon_position', 'bottom-left' ),
			'iconType'             => (string) $opt( $s, 'icon_type', 'cookie' ),
			'iconCustomUrl'        => (string) $opt( $s, 'icon_custom_url', '' ),
			'pluginAssetsPath'     => (string) ( wp_parse_url( CCWPS_PLUGIN_URL . 'public/js/', PHP_URL_PATH ) ?: '' ),
			'bannerLogoShow'       => (bool) $opt( $s, 'banner_logo_show', 0 ),
			'bannerLogoUrl'        => (string) $opt( $s, 'banner_logo_url', '' ),
			'bannerLogoLinkUrl'    => (string) $opt( $s, 'banner_logo_link_url', '' ),
			'bannerLogoWidth'      => (int) $opt( $s, 'banner_logo_width', 40 ),
			'legalLinks' => [
				'cookiePolicy' => [
					'enabled' => (bool) $opt( $s, 'add_cookie_policy_link', 0 ),
					'url'     => (string) $opt( $s, 'cookie_policy_link_url', '' ),
					'text'    => (string) $opt( $s, 'cookie_policy_link_text', '' ),
				],
				'privacyPolicy' => [
					'enabled' => (bool) $opt( $s, 'add_privacy_policy_link', 0 ),
					'url'     => (string) $opt( $s, 'privacy_policy_link_url', '' ),
					'text'    => (string) $opt( $s, 'privacy_policy_link_text', '' ),
				],
			],
			'spacing' => [
				'banner' => [
					'textPaddingT'      => (int) $opt( $s, 'banner_text_padding_t', 0 ),
					'textPaddingR'      => (int) $opt( $s, 'banner_text_padding_r', 0 ),
					'textPaddingB'      => (int) $opt( $s, 'banner_text_padding_b', 0 ),
					'textPaddingL'      => (int) $opt( $s, 'banner_text_padding_l', 0 ),
					'textMarginT'       => (int) $opt( $s, 'banner_text_margin_t', 0 ),
					'textMarginR'       => (int) $opt( $s, 'banner_text_margin_r', 0 ),
					'textMarginB'       => (int) $opt( $s, 'banner_text_margin_b', 5 ),
					'textMarginL'       => (int) $opt( $s, 'banner_text_margin_l', 0 ),
					'linksPaddingT'     => (int) $opt( $s, 'banner_links_padding_t', 0 ),
					'linksPaddingR'     => (int) $opt( $s, 'banner_links_padding_r', 0 ),
					'linksPaddingB'     => (int) $opt( $s, 'banner_links_padding_b', 0 ),
					'linksPaddingL'     => (int) $opt( $s, 'banner_links_padding_l', 0 ),
					'linksMarginT'      => (int) $opt( $s, 'banner_links_margin_t', 0 ),
					'linksMarginR'      => (int) $opt( $s, 'banner_links_margin_r', 0 ),
					'linksMarginB'      => (int) $opt( $s, 'banner_links_margin_b', 30 ),
					'linksMarginL'      => (int) $opt( $s, 'banner_links_margin_l', 0 ),
					'acceptBtnPaddingT' => (int) $opt( $s, 'banner_accept_btn_padding_t', 11 ),
					'acceptBtnPaddingR' => (int) $opt( $s, 'banner_accept_btn_padding_r', 20 ),
					'acceptBtnPaddingB' => (int) $opt( $s, 'banner_accept_btn_padding_b', 11 ),
					'acceptBtnPaddingL' => (int) $opt( $s, 'banner_accept_btn_padding_l', 20 ),
					'acceptBtnMarginT'  => (int) $opt( $s, 'banner_accept_btn_margin_t', 0 ),
					'acceptBtnMarginR'  => (int) $opt( $s, 'banner_accept_btn_margin_r', 0 ),
					'acceptBtnMarginB'  => (int) $opt( $s, 'banner_accept_btn_margin_b', 0 ),
					'acceptBtnMarginL'  => (int) $opt( $s, 'banner_accept_btn_margin_l', 0 ),
					'rejectBtnPaddingT' => (int) $opt( $s, 'banner_reject_btn_padding_t', 11 ),
					'rejectBtnPaddingR' => (int) $opt( $s, 'banner_reject_btn_padding_r', 20 ),
					'rejectBtnPaddingB' => (int) $opt( $s, 'banner_reject_btn_padding_b', 11 ),
					'rejectBtnPaddingL' => (int) $opt( $s, 'banner_reject_btn_padding_l', 20 ),
					'rejectBtnMarginT'  => (int) $opt( $s, 'banner_reject_btn_margin_t', 0 ),
					'rejectBtnMarginR'  => (int) $opt( $s, 'banner_reject_btn_margin_r', 0 ),
					'rejectBtnMarginB'  => (int) $opt( $s, 'banner_reject_btn_margin_b', 0 ),
					'rejectBtnMarginL'  => (int) $opt( $s, 'banner_reject_btn_margin_l', 0 ),
					'manageBtnPaddingT' => (int) $opt( $s, 'banner_manage_btn_padding_t', 11 ),
					'manageBtnPaddingR' => (int) $opt( $s, 'banner_manage_btn_padding_r', 20 ),
					'manageBtnPaddingB' => (int) $opt( $s, 'banner_manage_btn_padding_b', 11 ),
					'manageBtnPaddingL' => (int) $opt( $s, 'banner_manage_btn_padding_l', 20 ),
					'manageBtnMarginT'  => (int) $opt( $s, 'banner_manage_btn_margin_t', 0 ),
					'manageBtnMarginR'  => (int) $opt( $s, 'banner_manage_btn_margin_r', 0 ),
					'manageBtnMarginB'  => (int) $opt( $s, 'banner_manage_btn_margin_b', 0 ),
					'manageBtnMarginL'  => (int) $opt( $s, 'banner_manage_btn_margin_l', 0 ),
					'btnGap'            => (int) $opt( $s, 'banner_btn_gap', 10 ),
				],
				'modal' => [
					'bodyPaddingT'      => (int) $opt( $s, 'modal_body_padding_t', 24 ),
					'bodyPaddingR'      => (int) $opt( $s, 'modal_body_padding_r', 28 ),
					'bodyPaddingB'      => (int) $opt( $s, 'modal_body_padding_b', 24 ),
					'bodyPaddingL'      => (int) $opt( $s, 'modal_body_padding_l', 28 ),
					'bodyMarginT'       => (int) $opt( $s, 'modal_body_margin_t', 0 ),
					'bodyMarginR'       => (int) $opt( $s, 'modal_body_margin_r', 0 ),
					'bodyMarginB'       => (int) $opt( $s, 'modal_body_margin_b', 0 ),
					'bodyMarginL'       => (int) $opt( $s, 'modal_body_margin_l', 0 ),
					'saveBtnPaddingT'   => (int) $opt( $s, 'modal_save_btn_padding_t', 11 ),
					'saveBtnPaddingR'   => (int) $opt( $s, 'modal_save_btn_padding_r', 20 ),
					'saveBtnPaddingB'   => (int) $opt( $s, 'modal_save_btn_padding_b', 11 ),
					'saveBtnPaddingL'   => (int) $opt( $s, 'modal_save_btn_padding_l', 20 ),
					'saveBtnMarginT'    => (int) $opt( $s, 'modal_save_btn_margin_t', 0 ),
					'saveBtnMarginR'    => (int) $opt( $s, 'modal_save_btn_margin_r', 0 ),
					'saveBtnMarginB'    => (int) $opt( $s, 'modal_save_btn_margin_b', 0 ),
					'saveBtnMarginL'    => (int) $opt( $s, 'modal_save_btn_margin_l', 0 ),
					'rejectBtnPaddingT' => (int) $opt( $s, 'modal_reject_btn_padding_t', 11 ),
					'rejectBtnPaddingR' => (int) $opt( $s, 'modal_reject_btn_padding_r', 20 ),
					'rejectBtnPaddingB' => (int) $opt( $s, 'modal_reject_btn_padding_b', 11 ),
					'rejectBtnPaddingL' => (int) $opt( $s, 'modal_reject_btn_padding_l', 20 ),
					'rejectBtnMarginT'  => (int) $opt( $s, 'modal_reject_btn_margin_t', 0 ),
					'rejectBtnMarginR'  => (int) $opt( $s, 'modal_reject_btn_margin_r', 0 ),
					'rejectBtnMarginB'  => (int) $opt( $s, 'modal_reject_btn_margin_b', 0 ),
					'rejectBtnMarginL'  => (int) $opt( $s, 'modal_reject_btn_margin_l', 0 ),
					'acceptBtnPaddingT' => (int) $opt( $s, 'modal_accept_btn_padding_t', 11 ),
					'acceptBtnPaddingR' => (int) $opt( $s, 'modal_accept_btn_padding_r', 20 ),
					'acceptBtnPaddingB' => (int) $opt( $s, 'modal_accept_btn_padding_b', 11 ),
					'acceptBtnPaddingL' => (int) $opt( $s, 'modal_accept_btn_padding_l', 20 ),
					'acceptBtnMarginT'  => (int) $opt( $s, 'modal_accept_btn_margin_t', 0 ),
					'acceptBtnMarginR'  => (int) $opt( $s, 'modal_accept_btn_margin_r', 0 ),
					'acceptBtnMarginB'  => (int) $opt( $s, 'modal_accept_btn_margin_b', 0 ),
					'acceptBtnMarginL'  => (int) $opt( $s, 'modal_accept_btn_margin_l', 0 ),
					'btnGap'            => (int) $opt( $s, 'modal_btn_gap', 10 ),
					'descMarginT'       => (int) $opt( $s, 'modal_desc_margin_t', 0 ),
					'descMarginR'       => (int) $opt( $s, 'modal_desc_margin_r', 0 ),
					'descMarginB'       => (int) $opt( $s, 'modal_desc_margin_b', 6 ),
					'descMarginL'       => (int) $opt( $s, 'modal_desc_margin_l', 0 ),
				],
			],
			'colors' => [
				// Base (legacy, also used as fallback)
				'primary'       => $s['primary_color'],
				'text'          => $s['text_color'],
				'titleText'     => $s['banner_title_color'] ?: '#111827',
				'descText'      => $s['text_color'],
				'bg'            => $s['bg_color'],
				'btnText'       => $s['btn_text_color'],
				'btnRadius'     => (int) $s['btn_border_radius'],
				// Floating icon & tip
				'floatingIconBg'     => $s['floating_icon_bg']     ?: $s['primary_color'],
				'floatingIconBgHv'   => $s['floating_icon_bg_hv']  ?: $s['primary_color'],
				'floatingIconColor'  => $s['floating_icon_color']  ?: '#ffffff',
				'floatingPopupBg'    => $s['floating_popup_bg']    ?: $s['bg_color'],
				'floatingPopupText'  => $s['floating_popup_text']  ?: $s['text_color'],
				// Banner box
				'bannerBg'          => $s['bg_color'],
				'cloudBgOpacity'    => max( 0, min( 100, (int) ( $s['cloud_bg_opacity'] ?: 70 ) ) ),
				'bannerBorderRadius'=> (int) ( $s['banner_border_radius'] ?: $s['btn_border_radius'] ?: 12 ),
				'bannerShadow'      => $s['banner_shadow'] ?: '0 12px 40px rgba(0,0,0,.18)',
				// Primary button
				'btnPrimaryBg'      => $s['btn_primary_bg']    ?: $s['primary_color'],
				'btnPrimaryBgHv'    => $s['btn_primary_bg_hv'] ?: '',
				'btnPrimaryTxt'     => $s['btn_primary_txt']   ?: $s['btn_text_color'],
				// Ghost button (Reject)
				'btnGhostBg'        => $s['btn_ghost_bg']      ?: ( $s['btn_primary_bg'] ?: $s['primary_color'] ),
				'btnGhostBgHv'      => $s['btn_ghost_bg_hv']   ?: ( $s['btn_primary_bg_hv'] ?: ( $s['btn_primary_bg'] ?: $s['primary_color'] ) ),
				'btnGhostTxt'       => $s['btn_ghost_txt']     ?: ( $s['btn_primary_txt'] ?: $s['btn_text_color'] ),
				'btnGhostTxtHv'     => $s['btn_ghost_txt_hv']  ?: ( $s['btn_ghost_txt'] ?: ( $s['btn_primary_txt'] ?: $s['btn_text_color'] ) ),
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
				'modalConsentId'    => $s['modal_consent_id_color'] ?: ( $s['modal_text'] ?: $s['text_color'] ),
				// Category rows
				'catHeaderBg'       => $s['cat_header_bg']      ?: '#f9fafb',
				'catHeaderBgHv'     => $s['cat_header_bg_hv']   ?: '#f0f2f5',
				// Toggle & accents
				'toggleOnColor'     => $s['toggle_on_color']    ?: $s['primary_color'],
				'alwaysOnColor'     => $s['always_on_color']    ?: $s['primary_color'],
			],
			'fontFamily' => (string) $opt( $s, 'font_family', '\'Poppins\', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif' ),
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
			'poweredBy'         => 'Web Pixel Studio',
			'consentIdLabel'    => $this->resolve_frontend_label_setting( $settings, 'lang_consent_id_label', 'consentIdLabel' ),
			'alwaysOn'          => $this->resolve_frontend_label_setting( $settings, 'lang_always_on', 'alwaysOn' ),
			'cookieName'        => $this->resolve_frontend_label_setting( $settings, 'lang_cookie_name', 'cookieName' ),
			'cookieDomain'      => $this->resolve_frontend_label_setting( $settings, 'lang_cookie_domain', 'cookieDomain' ),
			'cookieExpiration'  => $this->resolve_frontend_label_setting( $settings, 'lang_cookie_expiration', 'cookieExpiration' ),
			'cookieDescription' => $this->resolve_frontend_label_setting( $settings, 'lang_cookie_description', 'cookieDescription' ),
			'consentDateLabel'  => $this->get_frontend_label_translation( 'consentDateLabel', $admin_lang ),
			'noConsentYet'      => $this->get_frontend_label_translation( 'noConsentYet', $admin_lang ),
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
				'consentIdLabel'    => (string) ( $strings['lang_consent_id_label'] ?? $this->get_frontend_label_translation( 'consentIdLabel', $lang_code ) ),
				'alwaysOn'          => (string) ( $strings['lang_always_on'] ?? $this->get_frontend_label_translation( 'alwaysOn', $lang_code ) ),
				'cookieName'        => (string) ( $strings['lang_cookie_name'] ?? $this->get_frontend_label_translation( 'cookieName', $lang_code ) ),
				'cookieDomain'      => (string) ( $strings['lang_cookie_domain'] ?? $this->get_frontend_label_translation( 'cookieDomain', $lang_code ) ),
				'cookieExpiration'  => (string) ( $strings['lang_cookie_expiration'] ?? $this->get_frontend_label_translation( 'cookieExpiration', $lang_code ) ),
				'cookieDescription' => (string) ( $strings['lang_cookie_description'] ?? $this->get_frontend_label_translation( 'cookieDescription', $lang_code ) ),
				'consentDateLabel'  => $this->get_frontend_label_translation( 'consentDateLabel', $lang_code ),
				'noConsentYet'      => $this->get_frontend_label_translation( 'noConsentYet', $lang_code ),
			];

			foreach ( $option_map as $source_key => $target_key ) {
				$presets[ $lang_code ][ $target_key ] = (string) ( $strings[ $source_key ] ?? '' );
			}

			$presets[ $lang_code ]['poweredBy'] = 'Web Pixel Studio';
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

	private function get_frontend_label_map(): array {
		return [
			'consentIdLabel' => [
				'sk' => 'ID vášho súhlasu',
				'en' => 'Your consent ID',
				'cs' => 'ID vašeho souhlasu',
				'de' => 'Ihre Einwilligungs-ID',
				'fr' => 'Votre ID de consentement',
				'es' => 'Su ID de consentimiento',
				'pl' => 'ID Twojej zgody',
				'hu' => 'Az Ön hozzájárulási azonosítója',
				'it' => 'Il tuo ID di consenso',
			],
			'alwaysOn' => [
				'sk' => 'Vždy aktívne',
				'en' => 'Always active',
				'cs' => 'Vždy aktivní',
				'de' => 'Immer aktiv',
				'fr' => 'Toujours actif',
				'es' => 'Siempre activo',
				'pl' => 'Zawsze aktywne',
				'hu' => 'Mindig aktív',
				'it' => 'Sempre attivo',
			],
			'cookieName' => [
				'sk' => 'Názov',
				'en' => 'Name',
				'cs' => 'Název',
				'de' => 'Name',
				'fr' => 'Nom',
				'es' => 'Nombre',
				'pl' => 'Nazwa',
				'hu' => 'Név',
				'it' => 'Nome',
			],
			'cookieDomain' => [
				'sk' => 'Doména',
				'en' => 'Domain',
				'cs' => 'Doména',
				'de' => 'Domain',
				'fr' => 'Domaine',
				'es' => 'Dominio',
				'pl' => 'Domena',
				'hu' => 'Domain',
				'it' => 'Dominio',
			],
			'cookieExpiration' => [
				'sk' => 'Platnosť',
				'en' => 'Expiration',
				'cs' => 'Platnost',
				'de' => 'Ablauf',
				'fr' => 'Expiration',
				'es' => 'Caducidad',
				'pl' => 'Okres ważności',
				'hu' => 'Lejárat',
				'it' => 'Scadenza',
			],
			'cookieDescription' => [
				'sk' => 'Popis',
				'en' => 'Description',
				'cs' => 'Popis',
				'de' => 'Beschreibung',
				'fr' => 'Description',
				'es' => 'Descripción',
				'pl' => 'Opis',
				'hu' => 'Leírás',
				'it' => 'Descrizione',
			],
			'consentDateLabel' => [
				'sk' => 'Dátum a čas',
				'en' => 'Date and time',
				'cs' => 'Datum a čas',
				'de' => 'Datum und Uhrzeit',
				'fr' => 'Date et heure',
				'es' => 'Fecha y hora',
				'pl' => 'Data i godzina',
				'hu' => 'Dátum és idő',
				'it' => 'Data e ora',
			],
			'noConsentYet' => [
				'sk' => 'Súhlas nebol udelený.',
				'en' => 'Consent has not been granted.',
				'cs' => 'Souhlas nebyl udělen.',
				'de' => 'Es wurde noch keine Einwilligung erteilt.',
				'fr' => 'Le consentement n\'a pas encore été accordé.',
				'es' => 'Aún no se ha otorgado el consentimiento.',
				'pl' => 'Zgoda nie została jeszcze udzielona.',
				'hu' => 'A hozzájárulás még nem lett megadva.',
				'it' => 'Il consenso non è stato ancora concesso.',
			],
		];
	}

	private function get_frontend_label_translation( string $label_key, string $lang_code ): string {
		$map       = $this->get_frontend_label_map()[ $label_key ] ?? [];
		$lang_code = strtolower( $lang_code );
		$base_lang = strtok( $lang_code, '-_' );

		return $map[ $lang_code ]
			?? ( is_string( $base_lang ) ? ( $map[ $base_lang ] ?? null ) : null )
			?? $map['en']
			?? '';
	}

	private function resolve_frontend_label_setting( array $settings, string $setting_key, string $label_key ): string {
		$lang_code = (string) ( $settings['admin_lang'] ?? 'sk' );
		$value     = $settings[ $setting_key ] ?? null;
		$fallback  = $this->get_frontend_label_translation( $label_key, $lang_code );

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return $fallback;
		}

		$value       = trim( $value );
		$translations = $this->get_frontend_label_map()[ $label_key ] ?? [];

		if ( in_array( $value, $translations, true ) ) {
			return $fallback;
		}

		return $value;
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
