<?php
/**
 * Admin panel.
 *
 * @package CookieConsentWPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCWPS_Admin {

	private CCWPS_Settings $settings;
	private CCWPS_Consent_Log $log;
	private CCWPS_Cookie_Manager $cookie_manager;
	private CCWPS_Block_Manager $block_manager;

	public function __construct(
		CCWPS_Settings $settings,
		CCWPS_Consent_Log $log,
		CCWPS_Cookie_Manager $cookie_manager,
		CCWPS_Block_Manager $block_manager
	) {
		$this->settings       = $settings;
		$this->log            = $log;
		$this->cookie_manager = $cookie_manager;
		$this->block_manager  = $block_manager;

		add_action( 'admin_menu',            [ $this, 'add_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_ccwps_export_csv',      [ $this, 'export_csv' ] );
		add_action( 'admin_post_ccwps_export_settings', [ $this, 'export_settings' ] );
		add_action( 'admin_post_ccwps_import_settings', [ $this, 'import_settings' ] );
		add_filter( 'plugin_action_links_' . CCWPS_PLUGIN_BASENAME, [ $this, 'add_plugin_action_links' ] );
	}

	public function add_plugin_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=ccwps' ) ),
			esc_html__( 'Nastavenia', 'web-pixel-studio-cookie-consent-eu' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	public function add_menu(): void {
		add_menu_page(
			__( 'Cookie Consent', 'web-pixel-studio-cookie-consent-eu' ),
			__( 'Cookie Consent', 'web-pixel-studio-cookie-consent-eu' ),
			'manage_options',
			'ccwps',
			[ $this, 'render_page' ],
			'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="8.5" cy="10.5" r="1.5" fill="currentColor"/><circle cx="14.5" cy="8.5" r="1" fill="currentColor"/><circle cx="15" cy="15" r="1.5" fill="currentColor"/><circle cx="9" cy="15.5" r="1" fill="currentColor"/></svg>' ),
			80
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_ccwps' !== $hook ) return;

		$this->ensure_required_plugin_cookies();

		$locale_switched = $this->switch_admin_locale();

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// WordPress Media Library for custom icon upload.
		wp_enqueue_media();

		wp_enqueue_style(  'ccwps-admin', CCWPS_PLUGIN_URL . 'admin/css/admin.css', [], CCWPS_VERSION );
		wp_enqueue_script( 'ccwps-admin', CCWPS_PLUGIN_URL . 'admin/js/admin.js', [ 'jquery', 'wp-color-picker' ], CCWPS_VERSION, true );

		wp_localize_script( 'ccwps-admin', 'ccwpsAdmin', [
			'nonce'       => wp_create_nonce( 'ccwps_admin' ),
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'siteUrl'     => home_url(),
			'siteHost'    => $this->get_home_url_host(),
			'i18n'        => [
				'saved'         => $this->tx( 'Nastavenia uložené.' ),
				'error'         => $this->tx( 'Vyskytla sa chyba.' ),
				'confirmClear'  => $this->tx( 'Naozaj chcete vymazať všetky záznamy súhlasov? Používatelia budú musieť znova udeliť súhlas.' ),
				'confirmDelete' => $this->tx( 'Naozaj chcete odstrániť túto položku?' ),
				'confirmReset'  => $this->tx( 'Resetovať všetky nastavenia na predvolené hodnoty?' ),
				'confirmSpacingReset' => $this->tx( 'Resetovať iba nastavenia vizuálnych medzier na predvolené hodnoty?' ),
				'confirmLangChange' => $this->tx( 'Zmeniť jazyk administrácie a aplikovať preklady na frontend?' ),
				'logCleared'    => $this->tx( 'Záznamy boli vymazané.' ),
				'langApplied'   => $this->tx( 'Jazyk bol aplikovaný. Uložte nastavenia.' ),
				'resetDone'     => $this->tx( 'Nastavenia boli resetované.' ),
				'spacingResetDone' => $this->tx( 'Vizuálne medzery boli resetované.' ),
				'saving'        => $this->tx( 'Ukladám…' ),
				'saveSettings'  => $this->tx( 'Uložiť nastavenia' ),
				'addCookie'     => $this->tx( 'Pridať cookie' ),
				'editCookie'    => $this->tx( 'Upraviť cookie' ),
				'enterCookieName' => $this->tx( 'Zadajte názov cookie.' ),
				'requestPreset' => $this->tx( 'Žiadosť o pridanie predvoľby' ),
				'requestPresetSend' => $this->tx( 'Odoslať žiadosť' ),
				'requestPresetSent' => $this->tx( 'Žiadosť bola úspešne odoslaná.' ),
				'requestPresetEmailInvalid' => $this->tx( 'Zadajte platný e-mail.' ),
				'requestPresetSubjectInvalid' => $this->tx( 'Predmet musí mať 3 až 150 znakov.' ),
				'requestPresetMessageInvalid' => $this->tx( 'Text správy musí mať 20 až 4000 znakov.' ),
				'sendingRequest' => $this->tx( 'Odosielam žiadosť…' ),
				'addRule'       => $this->tx( 'Pridať pravidlo' ),
				'editRule'      => $this->tx( 'Upraviť pravidlo' ),
				'enterScriptSource' => $this->tx( 'Zadajte zdroj skriptu.' ),
				'copied'        => $this->tx( '✓ Skopírované!' ),
				'copy'          => $this->t( 'admin_btn_copy', 'Kopírovať' ),
				'mediaTitle'    => $this->tx( 'Vybrať vlastnú ikonu' ),
				'mediaButton'   => $this->tx( 'Použiť tento obrázok' ),
				'customIconAlt' => $this->tx( 'Vlastná ikona' ),
				'noImage'       => $this->tx( 'Žiadny obrázok' ),
				'cookieListEmpty' => $this->tx( 'Žiadne cookies nie sú deklarované.' ),
				'cookieColName' => $this->settings->get( 'lang_cookie_name', __( 'Názov', 'web-pixel-studio-cookie-consent-eu' ) ),
				'cookieColDomain' => $this->settings->get( 'lang_cookie_domain', __( 'Doména', 'web-pixel-studio-cookie-consent-eu' ) ),
				'cookieColExpiration' => $this->settings->get( 'lang_cookie_expiration', __( 'Platnosť', 'web-pixel-studio-cookie-consent-eu' ) ),
				'cookieColDescription' => $this->settings->get( 'lang_cookie_description', __( 'Popis', 'web-pixel-studio-cookie-consent-eu' ) ),
				'catNecessary'  => $this->settings->get( 'lang_necessary_title', __( 'Nevyhnutné', 'web-pixel-studio-cookie-consent-eu' ) ),
				'catAnalytics'  => $this->settings->get( 'lang_analytics_title', __( 'Analytické', 'web-pixel-studio-cookie-consent-eu' ) ),
				'catTargeting'  => $this->settings->get( 'lang_targeting_title', __( 'Marketingové', 'web-pixel-studio-cookie-consent-eu' ) ),
				'catPreferences'=> $this->settings->get( 'lang_preferences_title', __( 'Preferenčné', 'web-pixel-studio-cookie-consent-eu' ) ),
				'applyPreset'   => $this->tx( 'Použiť predvoľbu' ),
				'selectPreset'  => $this->tx( 'Vyberte predvoľbu.' ),
				'presetsAdded'  => $this->tx( 'Predvoľby boli pridané.' ),
				'noNewPresets'  => $this->tx( 'Všetky vybrané predvoľby už existujú.' ),
			],
			'langPresets' => CCWPS_Language_Presets::get_all(),
			'cookies'     => $this->cookie_manager->get_grouped(),
			'blockRules'  => $this->block_manager->get_all(),
			'settings'    => $this->settings->get_all(),
			'appearanceDefaults' => $this->get_appearance_defaults(),
		] );

		if ( $locale_switched ) {
			restore_previous_locale();
		}
	}

	/* ================================================
	   PAGE RENDER
	   ================================================ */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) return;

		$locale_switched = $this->switch_admin_locale();
		$ob_started = false;
		try {
		ob_start();
		$ob_started = true;

		$import_notice = '';
		if ( '' !== $this->get_query_string( 'ccwps_imported' ) ) {
			$import_notice = '1' === $this->get_query_string( 'ccwps_imported' )
				? '<div class="ccwps-notice success">' . esc_html__( 'Nastavenia boli importované.', 'web-pixel-studio-cookie-consent-eu' ) . '</div>'
				: '<div class="ccwps-notice error">'   . esc_html__( 'Import zlyhal. Skontrolujte súbor.', 'web-pixel-studio-cookie-consent-eu' ) . '</div>';
		}

		$s          = $this->settings;
		$active_tab = $this->get_query_key( 'tab', 'settings' );
		$admin_lang = $s->get( 'admin_lang', 'sk' );

		$nav_items = [
			'settings'     => [ 'icon' => '⚙️', 'label' => $s->get( 'admin_nav_settings',     __( 'Nastavenia',          'web-pixel-studio-cookie-consent-eu' ) ) ],
			'appearance'   => [ 'icon' => '🎨', 'label' => $s->get( 'admin_nav_appearance',   __( 'Vzhľad',              'web-pixel-studio-cookie-consent-eu' ) ) ],
			'translations' => [ 'icon' => '🌐', 'label' => $s->get( 'admin_nav_translations', __( 'Preklady',            'web-pixel-studio-cookie-consent-eu' ) ) ],
			'cookies'      => [ 'icon' => '🍪', 'label' => $s->get( 'admin_nav_cookies',      __( 'Cookies',             'web-pixel-studio-cookie-consent-eu' ) ) ],
			'blocking'     => [ 'icon' => '🚫', 'label' => $s->get( 'admin_nav_blocking',     __( 'Blokovanie skriptov', 'web-pixel-studio-cookie-consent-eu' ) ) ],
			'log'          => [ 'icon' => '📋', 'label' => $s->get( 'admin_nav_log',          __( 'Záznamy súhlasov',    'web-pixel-studio-cookie-consent-eu' ) ) ],
			'shortcodes'   => [ 'icon' => '[ ]','label' => $s->get( 'admin_nav_shortcodes',   __( 'Shortcodes',          'web-pixel-studio-cookie-consent-eu' ) ) ],
			'gtm-template' => [ 'icon' => '🏷️', 'label' => $this->tx( 'GTM šablóna' ) ],
			'tools'        => [ 'icon' => '🔧', 'label' => $s->get( 'admin_nav_tools',        __( 'Nástroje',            'web-pixel-studio-cookie-consent-eu' ) ) ],
			'about'        => [ 'icon' => 'ℹ️', 'label' => $s->get( 'admin_nav_about',        __( 'O plugine',           'web-pixel-studio-cookie-consent-eu' ) ) ],
		];
		?>
		<div class="ccwps-wrap">

			<!-- ===== SIDEBAR ===== -->
			<aside class="ccwps-sidebar">
				<div class="ccwps-sidebar-logo">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="8.5" cy="10.5" r="1.5" fill="currentColor"/><circle cx="14.5" cy="8.5" r="1" fill="currentColor"/><circle cx="15" cy="15" r="1.5" fill="currentColor"/><circle cx="9" cy="15.5" r="1" fill="currentColor"/></svg>
					<div>
						<div class="ccwps-sidebar-name">Cookie Consent</div>
						<div class="ccwps-sidebar-version">v<?php echo esc_html( CCWPS_VERSION ); ?></div>
					</div>
				</div>

				<div class="ccwps-admin-lang-bar">
					<span class="ccwps-admin-lang-label">🌐 <?php echo esc_html( $s->get( 'admin_lang_label', __( 'Jazyk:', 'web-pixel-studio-cookie-consent-eu' ) ) ); ?></span>
					<?php
					$all_presets    = CCWPS_Language_Presets::get_all();
					$current_preset = $all_presets[ $admin_lang ] ?? $all_presets['sk'];
					$current_flag   = $current_preset['flag_file'] ?? 'SK';
					$current_label  = $current_preset['label'] ?? 'Slovenčina';
					?>
					<div class="ccwps-flag-picker" id="ccwps-flag-picker" data-current="<?php echo esc_attr( $admin_lang ); ?>">
						<button type="button" class="ccwps-flag-picker-current" aria-haspopup="listbox">
							<img src="<?php echo esc_url( CCWPS_PLUGIN_URL . 'admin/images/flags/' . $current_flag . '.svg' ); ?>" alt="" class="ccwps-fp-flag">
							<span class="ccwps-fp-current-label"><?php echo esc_html( $current_label ); ?></span>
							<span class="ccwps-fp-arrow">▾</span>
						</button>
						<div class="ccwps-flag-picker-dropdown" role="listbox">
							<?php foreach ( $all_presets as $code => $data ) : ?>
								<button type="button" class="ccwps-fp-option <?php echo $admin_lang === $code ? 'active' : ''; ?>" data-lang="<?php echo esc_attr( $code ); ?>" role="option">
									<img src="<?php echo esc_url( CCWPS_PLUGIN_URL . 'admin/images/flags/' . $data['flag_file'] . '.svg' ); ?>" alt="" class="ccwps-fp-flag">
									<?php echo esc_html( $data['label'] ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<nav class="ccwps-sidebar-nav">
					<?php foreach ( $nav_items as $slug => $item ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ccwps&tab=' . $slug ) ); ?>"
						   class="ccwps-nav-item <?php echo $active_tab === $slug ? 'active' : ''; ?>">
							<span class="ccwps-nav-icon"><?php echo esc_html( $item['icon'] ); ?></span>
							<span class="ccwps-nav-label"><?php echo esc_html( $item['label'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</nav>

				<!-- Preview buttons (always visible) -->
				<div class="ccwps-sidebar-preview">
					<div class="ccwps-sidebar-preview-title"><?php echo esc_html( $s->get( 'admin_preview_label', __( 'Náhľad:', 'web-pixel-studio-cookie-consent-eu' ) ) ); ?></div>
					<button type="button" class="button ccwps-btn-preview-full ccwps-btn-preview-action" id="ccwps-preview-banner">
						👁 <?php echo esc_html( $s->get( 'admin_preview_banner', __( 'Zobraziť banner', 'web-pixel-studio-cookie-consent-eu' ) ) ); ?>
					</button>
					<button type="button" class="button ccwps-btn-preview-full ccwps-btn-preview-action" id="ccwps-preview-modal">
						⚙️ <?php echo esc_html( $s->get( 'admin_preview_modal', __( 'Nastavenia cookies', 'web-pixel-studio-cookie-consent-eu' ) ) ); ?>
					</button>
					<button type="button" class="button button-primary ccwps-save-settings ccwps-btn-primary-action ccwps-btn-preview-full ccwps-btn-sidebar-save" id="ccwps-sidebar-save-settings">
						💾 <?php echo esc_html( $this->t( 'admin_sidebar_save_changes', 'Uložiť zmeny' ) ); ?>
					</button>
				</div>

				<div class="ccwps-sidebar-footer">
					<div class="ccwps-sidebar-powered">
						<?php esc_html_e( 'Funguje na základe', 'web-pixel-studio-cookie-consent-eu' ); ?>
						<a href="https://cookieconsent.orestbida.com/" target="_blank" rel="noopener">orest bida</a>
					</div>
					<div class="ccwps-sidebar-author">
						<?php esc_html_e( 'Bezplatný plugin vytvoril:', 'web-pixel-studio-cookie-consent-eu' ); ?><br>
						<a href="https://webpixelstudio.org" target="_blank" rel="noopener"><strong>Web Pixel Studio</strong></a>
						<div class="ccwps-sidebar-ratings">
							<div class="ccwps-sidebar-rating">
								<div class="ccwps-rating-title"><?php echo esc_html( $this->tx( 'Ohodnoťte plugin' ) ); ?></div>
								<a class="ccwps-rating-link" href="https://wordpress.org/support/plugin/web-pixel-studio-cookie-consent-eu/reviews/#new-post" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $this->tx( 'Ohodnoťte plugin' ) ); ?>">
									<span class="ccwps-rating-stars" aria-hidden="true">
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
									</span>
								</a>
							</div>
							<div class="ccwps-sidebar-rating">
								<div class="ccwps-rating-title"><?php echo esc_html( $this->tx( 'Ohodnoťte nás na Google' ) ); ?></div>
								<a class="ccwps-rating-link" href="https://g.page/r/CQbvkFVFGMEvEBM/review" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $this->tx( 'Ohodnoťte nás na Google' ) ); ?>">
									<span class="ccwps-rating-stars" aria-hidden="true">
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
										<span class="dashicons dashicons-star-filled"></span>
									</span>
								</a>
							</div>
						</div>
						<div class="ccwps-sidebar-social">
							<a href="https://www.facebook.com/wps.sk/" target="_blank" rel="noopener" aria-label="Facebook">
								<img src="<?php echo esc_url( CCWPS_PLUGIN_URL . 'admin/images/social/facebook-web-pixel-studio.webp' ); ?>" alt="Facebook">
							</a>
							<a href="https://www.instagram.com/tvorbawebov/" target="_blank" rel="noopener" aria-label="Instagram">
								<img src="<?php echo esc_url( CCWPS_PLUGIN_URL . 'admin/images/social/instagram-web-pixel-studio.webp' ); ?>" alt="Instagram">
							</a>
						</div>
					</div>
				</div>
			</aside>

			<!-- ===== MAIN ===== -->
			<main class="ccwps-main">
				<?php echo wp_kses_post( $import_notice ); ?>
				<div id="ccwps-notice" class="ccwps-notice" style="display:none;"></div>

				<?php
				switch ( $active_tab ) {
					case 'settings':     $this->tab_settings();     break;
					case 'appearance':   $this->tab_appearance();   break;
					case 'translations': $this->tab_translations(); break;
					case 'cookies':      $this->tab_cookies();      break;
					case 'blocking':     $this->tab_blocking();     break;
					case 'log':          $this->tab_log();          break;
					case 'shortcodes':   $this->tab_shortcodes();   break;
						case 'gtm-template': $this->tab_gtm_template(); break;
					case 'tools':        $this->tab_tools();        break;
					case 'about':        $this->tab_about();        break;
				}
				?>
			</main>
		</div>
		<?php
		$translated_html = $this->translate_admin_html( (string) ob_get_clean() );
		$ob_started = false;
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Full admin markup is generated by this class and escaped at source.
		echo $translated_html;
		} finally {
			if ( $ob_started ) {
				ob_end_clean();
			}
			if ( $locale_switched ) {
				restore_previous_locale();
			}
		}
	}

	/* ================================================
	   SETTINGS TAB
	   ================================================ */
	private function tab_settings(): void {
		$s    = $this->settings;
		$mode = $s->get( 'consent_mode_version', 'v2' );
		?>
		<div class="ccwps-page-header">
			<h1><?php echo esc_html( $this->t( 'admin_tab_settings_h1', 'Nastavenia správania' ) ); ?></h1>
		</div>
		<form id="ccwps-settings-form" class="ccwps-form">

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->t( 'admin_sect_banner_behavior', 'Správanie bannera' ) ); ?></h2>
			<table class="ccwps-table">
				<?php $this->trow_toggle( 'autorun', __( 'Automatické spustenie', 'web-pixel-studio-cookie-consent-eu' ), __( 'Banner sa zobrazí automaticky pri načítaní stránky.', 'web-pixel-studio-cookie-consent-eu' ), $s->get( 'autorun' ), __( '💡 Odporúčame zapnúť. Vypnuté = zobraziť manuálne cez <code>CookieConsentWPS.showBanner()</code>', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
				<?php $this->trow_toggle( 'force_consent', __( 'Vynútený súhlas', 'web-pixel-studio-cookie-consent-eu' ), __( 'Používateľ nemôže prehliadať web bez udelenia súhlasu.', 'web-pixel-studio-cookie-consent-eu' ), $s->get( 'force_consent' ), __( '⚠️ GDPR neukladá povinnosť vynútiť súhlas. Odporúčame VYPNÚŤ – používateľ má právo odmietnuť.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
				<?php $this->trow_toggle( 'auto_clear_cookies', __( 'Automatické vymazanie cookies', 'web-pixel-studio-cookie-consent-eu' ), __( 'Pri odvolaní súhlasu sa automaticky vymažú cookies danej kategórie.', 'web-pixel-studio-cookie-consent-eu' ), $s->get( 'auto_clear_cookies' ), __( '💡 Funguje len pre cookies deklarované v záložke Cookies. Odporúčame zapnúť.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
				<?php $this->trow_toggle( 'page_scripts', __( 'Správa skriptov na stránke', 'web-pixel-studio-cookie-consent-eu' ), __( 'Blokuje skripty tretích strán, kým používateľ neudelí súhlas.', 'web-pixel-studio-cookie-consent-eu' ), $s->get( 'page_scripts' ), __( '💡 Definujte pravidlá v záložke "Blokovanie skriptov". Plugin zmení typ skriptu na text/plain.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
				<?php $this->trow_toggle( 'hide_from_bots', __( 'Skryť pre robotov', 'web-pixel-studio-cookie-consent-eu' ), __( 'Banner sa nezobrazí vyhľadávačom a crawlerom.', 'web-pixel-studio-cookie-consent-eu' ), $s->get( 'hide_from_bots' ), __( '💡 Odporúčame zapnúť. Zlepšuje SEO skóre webu.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
				<?php $this->trow_toggle( 'reconsent', __( 'Opätovný súhlas', 'web-pixel-studio-cookie-consent-eu' ), __( 'Zobrazí banner znova, keď sa zmení zoznam cookies.', 'web-pixel-studio-cookie-consent-eu' ), $s->get( 'reconsent' ), __( '💡 Odporúčame zapnúť – GDPR vyžaduje nový súhlas po zmene účelu spracovania.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
				<?php $this->trow_toggle( 'record_consents', __( 'Zaznamenávať súhlasy', 'web-pixel-studio-cookie-consent-eu' ), __( 'Každý súhlas sa uloží do databázy (ID, IP, URL, čas).', 'web-pixel-studio-cookie-consent-eu' ), $s->get( 'record_consents' ), __( '💡 Odporúčame zapnúť – záznamy slúžia ako dôkaz súhlasu pre GDPR audit.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
				<?php $this->trow_toggle( 'frontend_detect_visitor_language', __( 'Jazyk podľa návštevníka', 'web-pixel-studio-cookie-consent-eu' ), __( 'Frontend cookie lišta sa pokúsi načítať jazyk návštevníka a zobraziť zodpovedajúci dostupný preklad.', 'web-pixel-studio-cookie-consent-eu' ), $s->get( 'frontend_detect_visitor_language' ), __( '💡 Ak jazyk návštevníka nie je dostupný, plugin použije English (GB). Ak je voľba vypnutá, frontend používa aktuálne uložené texty bannera.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
				<?php $this->trow_toggle( 'hide_empty_categories', __( 'Skryť prázdne kategórie', 'web-pixel-studio-cookie-consent-eu' ), __( 'V modáli preferencií sa skryjú kategórie bez deklarovaných cookies.', 'web-pixel-studio-cookie-consent-eu' ), $s->get( 'hide_empty_categories' ), __( '💡 Zapnite, keď máte vyplnené všetky kategórie v záložke Cookies.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->t( 'admin_sect_cookie_settings', 'Nastavenia cookie súhlasu' ) ); ?></h2>
			<table class="ccwps-table">
				<tr>
					<th><label for="delay"><?php esc_html_e( 'Oneskorenie zobrazenia (ms)', 'web-pixel-studio-cookie-consent-eu' ); ?></label><p class="desc"><?php esc_html_e( 'Čas v milisekundách pred zobrazením bannera.', 'web-pixel-studio-cookie-consent-eu' ); ?></p></th>
					<td><input type="number" name="delay" id="delay" value="<?php echo esc_attr( $s->get( 'delay' ) ); ?>" min="0" class="small-text"><?php $this->tip( __( '💡 Hodnota 0 = okamžite. Odporúčame 0–500 ms. Vyššie hodnoty môžu byť problém pri GDPR auditoch.', 'web-pixel-studio-cookie-consent-eu' ) ); ?></td>
				</tr>
				<tr>
					<th><label for="cookie_expiration"><?php esc_html_e( 'Platnosť súhlasu (dni)', 'web-pixel-studio-cookie-consent-eu' ); ?></label><p class="desc"><?php esc_html_e( 'Po koľkých dňoch vyprší súhlas a banner sa zobrazí znova.', 'web-pixel-studio-cookie-consent-eu' ); ?></p></th>
					<td><input type="number" name="cookie_expiration" id="cookie_expiration" value="<?php echo esc_attr( $s->get( 'cookie_expiration' ) ); ?>" min="1" class="small-text"><?php $this->tip( __( '💡 GDPR odporúča max. 13 mesiacov (390 dní). Predvolených 182 dní (6 mes.) je bezpečná hodnota.', 'web-pixel-studio-cookie-consent-eu' ) ); ?></td>
				</tr>
				<tr>
					<th><label for="cookie_path"><?php esc_html_e( 'Cesta cookie', 'web-pixel-studio-cookie-consent-eu' ); ?></label><p class="desc"><?php esc_html_e( 'Cesta, na ktorej bude cookie platná.', 'web-pixel-studio-cookie-consent-eu' ); ?></p></th>
					<td><input type="text" name="cookie_path" id="cookie_path" value="<?php echo esc_attr( $s->get( 'cookie_path' ) ); ?>" class="regular-text"><?php $this->tip( __( '💡 Nechajte "/" – cookie bude platná na celom webe.', 'web-pixel-studio-cookie-consent-eu' ) ); ?></td>
				</tr>
				<tr>
					<th><label for="cookie_domain"><?php esc_html_e( 'Doména cookie', 'web-pixel-studio-cookie-consent-eu' ); ?></label><p class="desc"><?php esc_html_e( 'Nechajte prázdne pre automatickú detekciu.', 'web-pixel-studio-cookie-consent-eu' ); ?></p></th>
					<td><input type="text" name="cookie_domain" id="cookie_domain" value="<?php echo esc_attr( $s->get( 'cookie_domain' ) ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $this->get_home_url_host() ); ?>"><?php $this->tip( __( '💡 Vyplňte ".mojeweb.sk" (s bodkou) ak chcete zdieľať súhlas medzi subdoménami.', 'web-pixel-studio-cookie-consent-eu' ) ); ?></td>
				</tr>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Google Consent Mode', 'web-pixel-studio-cookie-consent-eu' ); ?></h2>
			<div class="ccwps-info-box">
				<strong><?php esc_html_e( 'Čo je Consent Mode?', 'web-pixel-studio-cookie-consent-eu' ); ?></strong>
				<?php esc_html_e( ' Google Consent Mode umožňuje Googlu modelovať chýbajúce dáta, keď používatelia odmietnu analytické/reklamné cookies. Vaše kampane budú stále merateľné aj bez plného súhlasu.', 'web-pixel-studio-cookie-consent-eu' ); ?>
			</div>
			<table class="ccwps-table">
				<tr>
					<th><?php esc_html_e( 'Verzia Consent Mode', 'web-pixel-studio-cookie-consent-eu' ); ?></th>
					<td>
						<div class="ccwps-radio-group">
							<?php
							$radio_opts = [
								'off' => [ __( 'Vypnuté', 'web-pixel-studio-cookie-consent-eu' ), __( 'Consent Mode sa nepoužíva. Vhodné ak nepoužívate Google Analytics ani Ads.', 'web-pixel-studio-cookie-consent-eu' ), '' ],
								'v2'  => [ __( 'Consent Mode v2', 'web-pixel-studio-cookie-consent-eu' ), __( 'Štandard od marca 2024. Vyžadovaný pre Google Ads a GA4 v EÚ. Posiela signály ad_user_data a ad_personalization. Nutné pre remarketing a konverzie.', 'web-pixel-studio-cookie-consent-eu' ), 'recommended' ],
								'v3'  => [ __( 'Consent Mode v3', 'web-pixel-studio-cookie-consent-eu' ), __( 'Rozšírenie v2 o developer ID signál. Zahŕňa všetky funkcie v2 + nastavenie developer_id pre presnejšie modelovanie konverzií. Vhodné pre pokročilé Google Marketing Platform funkcie.', 'web-pixel-studio-cookie-consent-eu' ), 'new' ],
							];
							foreach ( $radio_opts as $val => [ $label, $desc, $badge ] ) :
								$selected = $mode === $val ? 'selected' : '';
							?>
							<label class="ccwps-radio-option <?php echo esc_attr( $selected ); ?>">
								<input type="radio" name="consent_mode_version" value="<?php echo esc_attr( $val ); ?>" <?php checked( $mode, $val ); ?>>
								<div>
									<strong>
										<?php echo esc_html( $label ); ?>
										<?php if ( $badge === 'recommended' ) : ?><span class="ccwps-badge-recommended"><?php esc_html_e( 'Odporúčané', 'web-pixel-studio-cookie-consent-eu' ); ?></span><?php endif; ?>
										<?php if ( $badge === 'new' ) : ?><span class="ccwps-badge-new"><?php esc_html_e( 'Nové', 'web-pixel-studio-cookie-consent-eu' ); ?></span><?php endif; ?>
									</strong>
									<span><?php echo esc_html( $desc ); ?></span>
								</div>
							</label>
							<?php endforeach; ?>
						</div>
						<?php $this->tip( __( '💡 Pre väčšinu webov stačí v2. v3 použite ak využívate pokročilé Google Marketing Platform funkcie. Obe verzie vyžadujú GTM alebo manuálne načítanie gtag.js.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
					</td>
				</tr>
				<tr>
					<th><label for="gtm_id"><?php esc_html_e( 'GTM Container ID', 'web-pixel-studio-cookie-consent-eu' ); ?></label><p class="desc"><?php esc_html_e( 'Voliteľné: Plugin načíta GTM automaticky.', 'web-pixel-studio-cookie-consent-eu' ); ?></p></th>
					<td><input type="text" name="gtm_id" id="gtm_id" value="<?php echo esc_attr( $s->get( 'gtm_id' ) ); ?>" class="regular-text" placeholder="GTM-XXXXXXX"><?php $this->tip( __( '💡 Vyplňte len ak GTM nenačítavate iným spôsobom. Ak GTM máte v téme alebo inom plugine, nechajte prázdne.', 'web-pixel-studio-cookie-consent-eu' ) ); ?></td>
				</tr>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->tx( 'Matomo Analytics (voliteľné)' ) ); ?></h2>
			<div class="ccwps-info-box">
				<strong><?php echo esc_html( $this->tx( 'Nastavenie Matomo merania' ) ); ?></strong>
				<?php echo ' ' . esc_html( $this->tx( 'Pridajte URL vašej Matomo inštancie a Site ID. Plugin vie podľa súhlasu prepínať medzi úplným meraním a anonymným meraním bez cookies.' ) ); ?>
			</div>
			<table class="ccwps-table">
				<tr>
					<th><label for="matomo_url"><?php echo esc_html( $this->tx( 'URL Matomo inštancie' ) ); ?></label><p class="desc"><?php echo esc_html( $this->tx( 'Napr. https://analytics.example.com/' ) ); ?></p></th>
					<td><input type="url" name="matomo_url" id="matomo_url" value="<?php echo esc_attr( $s->get( 'matomo_url' ) ); ?>" class="regular-text" placeholder="https://analytics.example.com/"><?php $this->tip( $this->tx( '💡 Zadajte základnú URL Matomo bez súboru matomo.js (napr. https://analytics.example.com/).' ) ); ?></td>
				</tr>
				<tr>
					<th><label for="matomo_site_id"><?php echo esc_html( $this->tx( 'Matomo Site ID' ) ); ?></label><p class="desc"><?php echo esc_html( $this->tx( 'Číselné ID webu v Matomo (napr. 1).' ) ); ?></p></th>
					<td><input type="number" name="matomo_site_id" id="matomo_site_id" value="<?php echo esc_attr( $s->get( 'matomo_site_id' ) ); ?>" min="1" class="small-text"><?php $this->tip( $this->tx( '💡 Nájdete ho v Matomo v časti Administration → Websites.' ) ); ?></td>
				</tr>
				<?php $this->trow_toggle( 'matomo_anonymous_without_consent', $this->tx( 'Povoliť anonymné meranie pri odmietnutí' ), $this->tx( 'Ak návštevník odmietne analytické cookies, Matomo bude merať bez cookies (anonymne).' ), $s->get( 'matomo_anonymous_without_consent', 0 ), $this->tx( '💡 Odporúčané pre weby, ktoré chcú základné štatistiky aj bez súhlasu. Ak je voľba vypnutá, Matomo sa spustí až po udelení analytického súhlasu.' ) ); ?>
			</table>
			<?php
			$matomo_shots = [];
			for ( $i = 1; $i <= 2; $i++ ) {
				$img_path = plugin_dir_path( __FILE__ ) . '../admin/images/' . $i . '-matomo.webp';
				if ( file_exists( $img_path ) ) {
					$matomo_shots[] = plugin_dir_url( __FILE__ ) . '../admin/images/' . $i . '-matomo.webp';
				}
			}
			if ( ! empty( $matomo_shots ) ) :
			?>
			<div style="margin-top:20px;">
				<p style="margin-bottom:12px;color:var(--cc-gray-600);font-size:13px;"><?php echo esc_html( $this->tx( 'Náhľady krokov inštalácie Matomo Tag v administrácii Matomo.' ) ); ?></p>
				<div class="ccwps-matomo-gallery">
					<?php foreach ( $matomo_shots as $index => $shot_url ) : ?>
						<?php $step_label = sprintf( $this->tx( 'Krok %d' ), $index + 1 ); ?>
						<figure class="ccwps-matomo-shot">
							<button
								type="button"
								class="ccwps-matomo-shot-trigger"
								data-ccwps-matomo-image="<?php echo esc_url( $shot_url ); ?>"
								data-ccwps-matomo-caption="<?php echo esc_attr( $step_label ); ?>"
							>
								<img src="<?php echo esc_url( $shot_url ); ?>" alt="<?php echo esc_attr( $step_label ); ?>">
							</button>
							<figcaption><?php echo esc_html( $step_label ); ?></figcaption>
						</figure>
					<?php endforeach; ?>
				</div>
				<div class="ccwps-gtm-lightbox" id="ccwps-matomo-lightbox" hidden>
					<div class="ccwps-gtm-lightbox-backdrop" data-ccwps-matomo-lightbox-close></div>
					<div class="ccwps-gtm-lightbox-dialog" role="dialog" aria-modal="true" aria-labelledby="ccwps-matomo-lightbox-caption">
						<button type="button" class="ccwps-gtm-lightbox-close" data-ccwps-matomo-lightbox-close aria-label="Close preview">&times;</button>
						<img src="" alt="" class="ccwps-gtm-lightbox-image" id="ccwps-matomo-lightbox-image">
						<div class="ccwps-gtm-lightbox-caption" id="ccwps-matomo-lightbox-caption"></div>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<div class="ccwps-form-actions">
			<button type="button" class="button button-primary ccwps-save-settings ccwps-btn-primary-action"><?php echo esc_html( $this->t( 'admin_btn_save_settings', 'Uložiť nastavenia' ) ); ?></button>
		</div>
		</form>
		<?php
	}

	/* ================================================
	   APPEARANCE TAB
	   ================================================ */
	private function tab_appearance(): void {
		$s = $this->settings;
		$detect_fonts_requested = false;
		$detect_nonce = $this->get_query_string( '_ccwps_nonce' );

		if ( '1' === $this->get_query_string( 'ccwps_detect_fonts' ) && '' !== $detect_nonce ) {
			$detect_fonts_requested = (bool) wp_verify_nonce( $detect_nonce, 'ccwps_detect_fonts' );
		}

		$font_choices = $this->get_font_family_choices( $detect_fonts_requested );
		$current_font = (string) $s->get( 'font_family' );
		$appearance_defaults = $this->get_appearance_defaults();
		$detect_fonts_url = wp_nonce_url(
			admin_url( 'admin.php?page=ccwps&tab=appearance&ccwps_detect_fonts=1' ),
			'ccwps_detect_fonts',
			'_ccwps_nonce'
		);

		if ( '' !== $current_font && ! $this->font_choice_exists( $font_choices, $current_font ) && $this->is_valid_font_family_choice( $current_font ) ) {
			$this->add_font_choice( $font_choices, 'current', $current_font );
		}
		?>
		<div class="ccwps-page-header">
			<h1><?php echo esc_html( $this->t( 'admin_tab_appearance_h1', 'Vzhľad bannera' ) ); ?></h1>
		</div>
		<form id="ccwps-settings-form" class="ccwps-form">

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->t( 'admin_sect_layout', 'Rozloženie a poloha bannera' ) ); ?></h2>
			<table class="ccwps-table">
				<tr>
					<th><label><?php esc_html_e( 'Typ bannera', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th>
					<td>
						<div class="ccwps-layout-picker">
							<?php foreach ( [ 'box' => '⬜ Box', 'bar' => '▬ Bar', 'cloud' => '☁️ Cloud' ] as $v => $l ) : ?>
							<label class="ccwps-layout-opt <?php echo $s->get( 'banner_layout' ) === $v ? 'active' : ''; ?>">
								<input type="radio" name="banner_layout" value="<?php echo esc_attr( $v ); ?>" <?php checked( $s->get( 'banner_layout' ), $v ); ?>>
								<?php echo esc_html( $l ); ?>
							</label>
							<?php endforeach; ?>
						</div>
						<?php $this->tip( __( '💡 Box – kompaktný popup v rohu. Bar – horizontálny pruh cez celú šírku. Cloud – zaoblený popup s väčším tieňom.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
					</td>
				</tr>
				<tr>
					<th><label for="banner_position"><?php esc_html_e( 'Poloha bannera', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th>
					<td>
						<select name="banner_position" id="banner_position">
							<?php foreach ( [
								'bottom-left'   => __( 'Dole vľavo', 'web-pixel-studio-cookie-consent-eu' ),
								'bottom-right'  => __( 'Dole vpravo', 'web-pixel-studio-cookie-consent-eu' ),
								'bottom-center' => __( 'Dole v strede', 'web-pixel-studio-cookie-consent-eu' ),
								'top-left'      => __( 'Hore vľavo', 'web-pixel-studio-cookie-consent-eu' ),
								'top-right'     => __( 'Hore vpravo', 'web-pixel-studio-cookie-consent-eu' ),
								'top-center'    => __( 'Hore v strede', 'web-pixel-studio-cookie-consent-eu' ),
								'middle-center' => __( 'Stred obrazovky', 'web-pixel-studio-cookie-consent-eu' ),
							] as $v => $l ) : ?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $s->get( 'banner_position' ), $v ); ?>><?php echo esc_html( $l ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->t( 'admin_sect_floating_icon', 'Plávajúca ikona' ) ); ?></h2>
			<table class="ccwps-table">
				<?php $this->trow_toggle( 'banner_show_icon', __( 'Zobraziť plávajúcu ikonu', 'web-pixel-studio-cookie-consent-eu' ), __( 'Po udelení súhlasu sa zobrazí ikona na opätovnú správu preferencií.', 'web-pixel-studio-cookie-consent-eu' ), $s->get( 'banner_show_icon' ), __( '💡 Odporúčame zapnúť – GDPR vyžaduje, aby mohol používateľ kedykoľvek odvolať súhlas. Po kliknutí na ikonu sa zobrazí panel s ID súhlasu a tlačidlami.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
				<?php $this->trow_toggle( 'floating_show_powered_by', $this->tx( 'Podporte nás nenápadným odkazom v plávajúcej ikone' ), '', $s->get( 'floating_show_powered_by', 0 ) ); ?>
				<tr>
					<th><label for="icon_position"><?php esc_html_e( 'Poloha ikony', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th>
					<td>
						<select name="icon_position" id="icon_position">
							<?php foreach ( [ 'bottom-right' => __( 'Dole vpravo', 'web-pixel-studio-cookie-consent-eu' ), 'bottom-left' => __( 'Dole vľavo', 'web-pixel-studio-cookie-consent-eu' ), 'top-right' => __( 'Hore vpravo', 'web-pixel-studio-cookie-consent-eu' ), 'top-left' => __( 'Hore vľavo', 'web-pixel-studio-cookie-consent-eu' ) ] as $v => $l ) : ?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $s->get( 'icon_position' ), $v ); ?>><?php echo esc_html( $l ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th>
						<label for="floating_icon_margin_y"><?php esc_html_e( 'Vertikálny margin ikony (px)', 'web-pixel-studio-cookie-consent-eu' ); ?></label>
						<p class="desc"><?php esc_html_e( 'Posunie ikonu vyššie alebo nižšie od základnej polohy podľa vybraného rohu.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
					</th>
					<td>
						<input type="number" name="floating_icon_margin_y" id="floating_icon_margin_y" value="<?php echo esc_attr( $s->get( 'floating_icon_margin_y', 0 ) ); ?>" min="0" max="500" class="small-text">
						<?php $this->tip( __( '💡 Pri dolnej polohe vyššia hodnota zdvihne ikonu hore. Pri hornej polohe vyššia hodnota ju posunie nižšie.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
					</td>
				</tr>
				<tr>
					<th>
						<label for="floating_icon_margin_y_mobile"><?php esc_html_e( 'Vertikálny margin ikony na mobile (px)', 'web-pixel-studio-cookie-consent-eu' ); ?></label>
						<p class="desc"><?php esc_html_e( 'Platí iba pre mobilné zariadenia (šírka do 520 px).', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
					</th>
					<td>
						<input type="number" name="floating_icon_margin_y_mobile" id="floating_icon_margin_y_mobile" value="<?php echo esc_attr( $s->get( 'floating_icon_margin_y_mobile', 0 ) ); ?>" min="0" max="500" class="small-text">
						<?php $this->tip( __( '💡 Použite, ak chcete mať na mobile inú výšku ikony než na desktope.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
					</td>
				</tr>
				<tr>
					<th><label for="icon_type"><?php esc_html_e( 'Typ ikony', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th>
					<td>
						<select name="icon_type" id="icon_type" class="ccwps-icon-type-select">
							<?php foreach ( [
								'cookie'  => '🍪 Cookie',
								'shield'  => '🛡 Štít',
								'settings'=> '⚙️ Nastavenia',
								'lock'    => '🔒 Zámok',
								'custom'  => __( '🖼 Vlastná ikona (obrázok)', 'web-pixel-studio-cookie-consent-eu' ),
							] as $v => $l ) : ?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $s->get( 'icon_type' ), $v ); ?>><?php echo esc_html( $l ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr id="ccwps-custom-icon-row" style="<?php echo $s->get( 'icon_type' ) === 'custom' ? '' : 'display:none;'; ?>">
					<th>
						<label><?php esc_html_e( 'Vlastná ikona', 'web-pixel-studio-cookie-consent-eu' ); ?></label>
						<p class="desc"><?php echo esc_html( $this->tx( 'Nahrajte alebo vyberte obrázok z knižnice médií. Odporúčaná veľkosť: 50×50 px. Podporované formáty: obrázky povolené vo WordPress (napr. JPG, JPEG, PNG, GIF, WebP). SVG iba ak je povolené vo WordPress.' ) ); ?></p>
					</th>
					<td>
						<div class="ccwps-icon-upload-wrap">
							<?php $custom_url = $s->get( 'icon_custom_url' ); ?>
							<?php if ( $custom_url ) : ?>
								<div class="ccwps-icon-preview" id="ccwps-icon-preview">
									<img src="<?php echo esc_url( $custom_url ); ?>" alt="<?php esc_attr_e( 'Vlastná ikona', 'web-pixel-studio-cookie-consent-eu' ); ?>">
								</div>
							<?php else : ?>
								<div class="ccwps-icon-preview ccwps-icon-preview--empty" id="ccwps-icon-preview">
									<span><?php esc_html_e( 'Žiadny obrázok', 'web-pixel-studio-cookie-consent-eu' ); ?></span>
								</div>
							<?php endif; ?>
							<input type="hidden" name="icon_custom_url" id="icon_custom_url" value="<?php echo esc_url( $custom_url ); ?>">
							<div class="ccwps-icon-upload-btns">
								<button type="button" class="button" id="ccwps-icon-upload-btn">
									<?php esc_html_e( '📂 Vybrať / nahrať obrázok', 'web-pixel-studio-cookie-consent-eu' ); ?>
								</button>
								<?php if ( $custom_url ) : ?>
									<button type="button" class="button button-link-delete" id="ccwps-icon-remove-btn">
										<?php esc_html_e( 'Odstrániť', 'web-pixel-studio-cookie-consent-eu' ); ?>
									</button>
								<?php else : ?>
									<button type="button" class="button button-link-delete" id="ccwps-icon-remove-btn" style="display:none;">
										<?php esc_html_e( 'Odstrániť', 'web-pixel-studio-cookie-consent-eu' ); ?>
									</button>
								<?php endif; ?>
							</div>
						</div>
						<?php $this->tip( $this->tx( '💡 Ikona sa zobrazí v kruhu s farbou pozadia (primárna farba). Pre najlepší výsledok použite biely alebo priehľadný obrázok 50×50 px.' ) ); ?>
					</td>
				</tr>
				<?php foreach ( [
					'floating_icon_bg'    => [ __( 'Pozadie floating ikony', 'web-pixel-studio-cookie-consent-eu' ), __( 'Farba kruhu floating ikony.', 'web-pixel-studio-cookie-consent-eu' ), '#1a73e8' ],
					'floating_icon_bg_hv' => [ __( 'Pozadie floating ikony (hover)', 'web-pixel-studio-cookie-consent-eu' ), __( 'Farba kruhu floating ikony po prejdení myšou.', 'web-pixel-studio-cookie-consent-eu' ), '#1557b0' ],
					'floating_icon_color' => [ __( 'Farba obsahu floating ikony', 'web-pixel-studio-cookie-consent-eu' ), __( 'Farba SVG ikony alebo obsahu vo floating ikone.', 'web-pixel-studio-cookie-consent-eu' ), '#ffffff' ],
					'floating_popup_bg'   => [ __( 'Pozadie popupu floating ikony', 'web-pixel-studio-cookie-consent-eu' ), __( 'Farba pozadia popupu po kliknutí na floating ikonu.', 'web-pixel-studio-cookie-consent-eu' ), '#ffffff' ],
					'floating_popup_text' => [ __( 'Farba textu popupu floating ikony', 'web-pixel-studio-cookie-consent-eu' ), __( 'Farba textu a obsahu popupu po kliknutí na floating ikonu.', 'web-pixel-studio-cookie-consent-eu' ), '#111827' ],
				] as $k => [ $lbl, $desc, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label><p class="desc"><?php echo esc_html( $desc ); ?></p></th>
					<td><?php $this->render_color_picker_with_reset( $k, (string) ( $s->get( $k ) ?: $def ), $def ); ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->t( 'admin_sect_banner_logo', 'Logo v banneri' ) ); ?></h2>
			<table class="ccwps-table">
				<?php $this->trow_toggle( 'banner_logo_show', __( 'Zobraziť logo v banneri', 'web-pixel-studio-cookie-consent-eu' ), __( 'V pravom hornom rohu cookie lišty sa zobrazí vaše logo.', 'web-pixel-studio-cookie-consent-eu' ), $s->get( 'banner_logo_show' ) ); ?>
				<tr id="ccwps-banner-logo-fields" style="<?php echo $s->get( 'banner_logo_show' ) ? '' : 'display:none;'; ?>">
					<th>
						<label><?php esc_html_e( 'Logo bannera', 'web-pixel-studio-cookie-consent-eu' ); ?></label>
						<p class="desc"><?php echo esc_html( $this->t( 'admin_banner_logo_desc', 'Nahrajte alebo vyberte logo z knižnice médií.' ) ); ?></p>
						<p class="desc" style="color: #666; font-size: 12px;"><?php echo esc_html( $this->tx( 'Podporované formáty: obrázky povolené vo WordPress (napr. JPG, JPEG, PNG, GIF, WebP). SVG iba ak je povolené vo WordPress.' ) ); ?></p>
					</th>
					<td>
						<div class="ccwps-icon-upload-wrap">
							<?php $logo_url = $s->get( 'banner_logo_url' ); ?>
							<?php if ( $logo_url ) : ?>
								<div class="ccwps-icon-preview" id="ccwps-banner-logo-preview" style="max-width: 200px; max-height: 200px;">
									<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Logo bannera', 'web-pixel-studio-cookie-consent-eu' ); ?>" style="max-width: 100%; max-height: 100%;">
								</div>
							<?php else : ?>
								<div class="ccwps-icon-preview ccwps-icon-preview--empty" id="ccwps-banner-logo-preview">
									<span><?php esc_html_e( 'Žiadny obrázok', 'web-pixel-studio-cookie-consent-eu' ); ?></span>
								</div>
							<?php endif; ?>
							<input type="hidden" name="banner_logo_url" id="banner_logo_url" value="<?php echo esc_url( $logo_url ); ?>">
							<div class="ccwps-icon-upload-btns">
								<button type="button" class="button" id="ccwps-banner-logo-upload-btn">
									<?php esc_html_e( '📂 Vybrať / nahrať obrázok', 'web-pixel-studio-cookie-consent-eu' ); ?>
								</button>
								<button type="button" class="button button-link-delete" id="ccwps-banner-logo-remove-btn" <?php echo $logo_url ? '' : 'style="display:none;"'; ?>>
									<?php esc_html_e( 'Odstrániť', 'web-pixel-studio-cookie-consent-eu' ); ?>
								</button>
							</div>
						</div>
					</td>
				</tr>
				<tr id="ccwps-banner-logo-url-row" style="<?php echo $s->get( 'banner_logo_show' ) ? '' : 'display:none;'; ?>">
					<th><label for="banner_logo_link_url"><?php echo esc_html( $this->tx( 'URL na kliknutie loga' ) ); ?></label></th>
					<td>
						<input type="url" name="banner_logo_link_url" id="banner_logo_link_url"
							value="<?php echo esc_url( $s->get( 'banner_logo_link_url' ) ?: '' ); ?>"
							placeholder="https://example.com"
							class="regular-text">
						<?php $this->tip( $this->tx( '💡 Ak zadáš URL, logo bude kliknuteľné. Nechaj prázdne, ak chceš aby bolo iba dekoratívne.' ) ); ?>
					</td>
				</tr>
				<tr id="ccwps-banner-logo-width-row" style="<?php echo $s->get( 'banner_logo_show' ) ? '' : 'display:none;'; ?>">
					<th><label for="banner_logo_width"><?php esc_html_e( 'Šírka loga (px)', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th>
					<td>
						<input type="number" name="banner_logo_width" id="banner_logo_width"
							value="<?php echo esc_attr( $s->get( 'banner_logo_width' ) ?: 40 ); ?>"
							min="20" max="100" class="small-text">
						<?php $this->tip( __( '💡 Šírka loga v pixeloch (20–100 px). Výška sa prispôsobí automaticky.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
					</td>
				</tr>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->t( 'admin_sect_typography', 'Typografia a zaoblenie' ) ); ?></h2>
			<table class="ccwps-table">
				<tr>
					<th><label for="font_family"><?php esc_html_e( 'Font', 'web-pixel-studio-cookie-consent-eu' ); ?></label><p class="desc"><?php esc_html_e( 'CSS font-family hodnota', 'web-pixel-studio-cookie-consent-eu' ); ?></p></th>
					<td>
						<a class="button" href="<?php echo esc_url( $detect_fonts_url ); ?>"><?php echo esc_html( $this->tx( 'Detekovať použité fonty' ) ); ?></a>
						<?php if ( $detect_fonts_requested ) : ?>
							<p class="description" style="margin-top:8px;"><?php echo esc_html( $this->tx( 'Detegované fonty boli aktualizované.' ) ); ?></p>
						<?php endif; ?>
						<select name="font_family" id="font_family" class="regular-text">
							<?php foreach ( $this->get_font_choice_group_labels() as $group_key => $group_label ) : ?>
								<?php if ( empty( $font_choices[ $group_key ] ) || ! is_array( $font_choices[ $group_key ] ) ) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<optgroup label="<?php echo esc_attr( $group_label ); ?>">
									<?php foreach ( $font_choices[ $group_key ] as $font_choice ) : ?>
										<option value="<?php echo esc_attr( $font_choice['value'] ); ?>" <?php selected( $current_font, $font_choice['value'] ); ?>><?php echo esc_html( $font_choice['label'] ); ?></option>
									<?php endforeach; ?>
								</optgroup>
							<?php endforeach; ?>
						</select>
						<p class="description" style="margin-top:8px;"><?php esc_html_e( 'Pole ponúka iba detegované fonty z témy, theme.json, Elementora a frontend CSS.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
						<?php $this->tip( __( '💡 V zozname sa zobrazuje iba názov fontu. Po výbere sa uloží kompletná font-family hodnota detegovaná na stránke. Voľba "Použiť font témy" zodpovedá hodnote inherit.', 'web-pixel-studio-cookie-consent-eu' ) ); ?>
					</td>
				</tr>
				<tr>
					<th><label for="btn_border_radius"><?php esc_html_e( 'Zaoblenie tlačidiel (px)', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th>
					<td><input type="number" name="btn_border_radius" id="btn_border_radius" value="<?php echo esc_attr( $s->get( 'btn_border_radius' ) ); ?>" min="0" max="50" class="small-text"><?php $this->tip( __( '💡 0 = hranaté, 8 = mierne zaoblené, 24+ = "pill" tvar.', 'web-pixel-studio-cookie-consent-eu' ) ); ?></td>
				</tr>
				<tr>
					<th><label for="banner_border_radius"><?php esc_html_e( 'Zaoblenie bannera / modálu (px)', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th>
					<td><input type="number" name="banner_border_radius" id="banner_border_radius" value="<?php echo esc_attr( $s->get( 'banner_border_radius' ) ?: 12 ); ?>" min="0" max="40" class="small-text"><?php $this->tip( __( '💡 Zaoblenie rohov bannera aj modálu. Predvolené: 12 px.', 'web-pixel-studio-cookie-consent-eu' ) ); ?></td>
				</tr>
			</table>
		</div>

		<div class="ccwps-card">
			<h2 class="ccwps-card-title-row">
				<span><?php echo esc_html( $this->tx( 'Právne odkazy v banneri a preferenciách' ) ); ?></span>
				<?php $this->spacing_trigger_button( 'banner-links', $this->tx( 'Upraviť medzery právnych odkazov' ) ); ?>
			</h2>
			<table class="ccwps-table">
				<?php $this->trow_toggle( 'add_cookie_policy_link', $this->tx( 'Pridať odkaz na Zásady používania cookies' ), '', $s->get( 'add_cookie_policy_link', 0 ) ); ?>
				<tr id="ccwps-cookie-policy-link-row" style="<?php echo $s->get( 'add_cookie_policy_link', 0 ) ? '' : 'display:none;'; ?>">
					<th><label for="cookie_policy_link_url"><?php echo esc_html( $this->tx( 'URL Zásad používania cookies' ) ); ?></label></th>
					<td>
						<input type="url" name="cookie_policy_link_url" id="cookie_policy_link_url"
							value="<?php echo esc_url( $s->get( 'cookie_policy_link_url' ) ?: '' ); ?>"
							placeholder="https://example.com/cookie-policy"
							class="regular-text">
					</td>
				</tr>
				<tr id="ccwps-cookie-policy-text-row" style="<?php echo $s->get( 'add_cookie_policy_link', 0 ) ? '' : 'display:none;'; ?>">
					<th><label for="cookie_policy_link_text"><?php echo esc_html( $this->tx( 'Text odkazu na Zásady používania cookies' ) ); ?></label></th>
					<td>
						<input type="text" name="cookie_policy_link_text" id="cookie_policy_link_text"
							value="<?php echo esc_attr( $s->get( 'cookie_policy_link_text' ) ?: 'Cookie Policy' ); ?>"
							placeholder="Cookie Policy"
							class="regular-text">
					</td>
				</tr>

				<?php $this->trow_toggle( 'add_privacy_policy_link', $this->tx( 'Pridať odkaz na Zásady ochrany osobných údajov' ), '', $s->get( 'add_privacy_policy_link', 0 ) ); ?>
				<tr id="ccwps-privacy-policy-link-row" style="<?php echo $s->get( 'add_privacy_policy_link', 0 ) ? '' : 'display:none;'; ?>">
					<th><label for="privacy_policy_link_url"><?php echo esc_html( $this->tx( 'URL Zásad ochrany osobných údajov' ) ); ?></label></th>
					<td>
						<input type="url" name="privacy_policy_link_url" id="privacy_policy_link_url"
							value="<?php echo esc_url( $s->get( 'privacy_policy_link_url' ) ?: '' ); ?>"
							placeholder="https://example.com/privacy-policy"
							class="regular-text">
					</td>
				</tr>
				<tr id="ccwps-privacy-policy-text-row" style="<?php echo $s->get( 'add_privacy_policy_link', 0 ) ? '' : 'display:none;'; ?>">
					<th><label for="privacy_policy_link_text"><?php echo esc_html( $this->tx( 'Text odkazu na Zásady ochrany osobných údajov' ) ); ?></label></th>
					<td>
						<input type="text" name="privacy_policy_link_text" id="privacy_policy_link_text"
							value="<?php echo esc_attr( $s->get( 'privacy_policy_link_text' ) ?: 'Privacy Policy' ); ?>"
							placeholder="Privacy Policy"
							class="regular-text">
					</td>
				</tr>
			</table>
		</div>

		<div class="ccwps-card">
			<h2 class="ccwps-card-title-row">
				<span><?php esc_html_e( 'Farby bannera', 'web-pixel-studio-cookie-consent-eu' ); ?></span>
				<?php $this->spacing_trigger_button( 'banner-text', $this->tx( 'Upraviť medzery textu bannera' ) ); ?>
			</h2>
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Nastavte farby pre pozadie a text cookie lišty (bannera).', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
			<table class="ccwps-table">
				<?php foreach ( [
					'bg_color'    => [ __( 'Pozadie bannera', 'web-pixel-studio-cookie-consent-eu' ),    __( 'Farba pozadia cookie lišty.', 'web-pixel-studio-cookie-consent-eu' ), '#ffffff' ],
					'banner_title_color' => [ __( 'Farba nadpisu bannera', 'web-pixel-studio-cookie-consent-eu' ), __( 'Farba hlavného nadpisu v banneri.', 'web-pixel-studio-cookie-consent-eu' ), '#111827' ],
					'text_color'  => [ __( 'Farba textu bannera', 'web-pixel-studio-cookie-consent-eu' ), __( 'Farba popisu a bežného textu v banneri.', 'web-pixel-studio-cookie-consent-eu' ), '#111827' ],
					'primary_color' => [ __( 'Primárna / akcentová farba', 'web-pixel-studio-cookie-consent-eu' ), __( 'Používa sa ako fallback farba pre tlačidlá a zvýraznenia.', 'web-pixel-studio-cookie-consent-eu' ), '#1a73e8' ],
				] as $k => [ $lbl, $desc, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label><p class="desc"><?php echo esc_html( $desc ); ?></p></th>
					<td><?php $this->render_color_picker_with_reset( $k, (string) ( $s->get( $k ) ?: $def ), $def ); ?></td>
				</tr>
				<?php endforeach; ?>
				<tr>
					<th><label for="cloud_bg_opacity"><?php esc_html_e( 'Priesvitnosť bannera Cloud (%)', 'web-pixel-studio-cookie-consent-eu' ); ?></label><p class="desc"><?php esc_html_e( 'Platí iba pre typ bannera Cloud. 0 = maximálne priehľadný, 100 = najmenej priehľadný.', 'web-pixel-studio-cookie-consent-eu' ); ?></p></th>
					<td><input type="number" name="cloud_bg_opacity" id="cloud_bg_opacity" value="<?php echo esc_attr( $s->get( 'cloud_bg_opacity' ) ?: 70 ); ?>" min="0" max="100" class="small-text"></td>
				</tr>
			</table>
		</div>

		<div class="ccwps-card">
			<h2 class="ccwps-card-title-row">
				<span><?php esc_html_e( 'Tlačidlo "Prijať všetky" (primárne)', 'web-pixel-studio-cookie-consent-eu' ); ?></span>
				<?php $this->spacing_trigger_button( 'banner-accept', $this->tx( 'Upraviť medzery tlačidla Prijať všetky' ) ); ?>
			</h2>
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Farebné nastavenia pre hlavné modré tlačidlo súhlasu. Prázdne pole = použije sa primárna farba.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
			<table class="ccwps-table">
				<?php
				$btn_primary = [
					'btn_primary_bg'     => [ __( 'Pozadie', 'web-pixel-studio-cookie-consent-eu' ),           '#1a73e8' ],
					'btn_primary_bg_hv'  => [ __( 'Pozadie (hover)', 'web-pixel-studio-cookie-consent-eu' ),    '#1557b0' ],
					'btn_primary_txt'    => [ __( 'Farba textu', 'web-pixel-studio-cookie-consent-eu' ),         '#ffffff' ],
					'btn_text_color'     => [ __( 'Farba textu (hover)', 'web-pixel-studio-cookie-consent-eu' ), '#ffffff' ],
				];
				foreach ( $btn_primary as $k => [ $lbl, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label></th>
					<td><?php $this->render_color_picker_with_reset( $k, (string) ( $s->get( $k ) ?: $def ), $def ); ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="ccwps-card">
			<h2 class="ccwps-card-title-row">
				<span><?php esc_html_e( 'Tlačidlo "Odmietnuť všetky" (primárne)', 'web-pixel-studio-cookie-consent-eu' ); ?></span>
				<?php $this->spacing_trigger_button( 'banner-reject', $this->tx( 'Upraviť medzery tlačidla Odmietnuť všetky' ) ); ?>
			</h2>
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Farebné nastavenia pre tlačidlo odmietnutia. Možnosti sú zjednotené s tlačidlom "Prijať všetky".', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
			<table class="ccwps-table">
				<?php
				$btn_ghost = [
					'btn_ghost_bg'     => [ __( 'Pozadie', 'web-pixel-studio-cookie-consent-eu' ),           '#1a73e8' ],
					'btn_ghost_bg_hv'  => [ __( 'Pozadie (hover)', 'web-pixel-studio-cookie-consent-eu' ),    '#1557b0' ],
					'btn_ghost_txt'    => [ __( 'Farba textu', 'web-pixel-studio-cookie-consent-eu' ),         '#ffffff' ],
					'btn_ghost_txt_hv' => [ __( 'Farba textu (hover)', 'web-pixel-studio-cookie-consent-eu' ), '#ffffff' ],
				];
				foreach ( $btn_ghost as $k => [ $lbl, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label></th>
					<td><?php $this->render_color_picker_with_reset( $k, (string) ( $s->get( $k ) ?: $def ), $def ); ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="ccwps-card">
			<h2 class="ccwps-card-title-row">
				<span><?php esc_html_e( 'Tlačidlo "Spravovať nastavenia" (outline)', 'web-pixel-studio-cookie-consent-eu' ); ?></span>
				<?php $this->spacing_trigger_button( 'banner-manage', $this->tx( 'Upraviť medzery tlačidla Spravovať nastavenia' ) ); ?>
			</h2>
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Farebné nastavenia pre tlačidlo správy preferencií.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
			<table class="ccwps-table">
				<?php
				$btn_outline = [
					'btn_outline_bg'     => [ __( 'Pozadie', 'web-pixel-studio-cookie-consent-eu' ),           'transparent' ],
					'btn_outline_bg_hv'  => [ __( 'Pozadie (hover)', 'web-pixel-studio-cookie-consent-eu' ),   '#1a73e8' ],
					'btn_outline_txt'    => [ __( 'Farba textu', 'web-pixel-studio-cookie-consent-eu' ),         '#1a73e8' ],
					'btn_outline_border' => [ __( 'Farba ohraničenia', 'web-pixel-studio-cookie-consent-eu' ),   '#1a73e8' ],
				];
				foreach ( $btn_outline as $k => [ $lbl, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label></th>
					<td>
						<?php if ( $k === 'btn_outline_bg' ) : ?>
							<div class="ccwps-color-with-transparent">
								<?php $this->render_color_picker_with_reset( $k, (string) ( $s->get( $k ) ?: $def ), $def ); ?>
								<label style="margin-left:8px;font-size:12px;color:#6b7280;"><input type="checkbox" class="ccwps-transparent-check" data-target="<?php echo esc_attr( $k ); ?>" <?php checked( !$s->get( $k ) || $s->get( $k ) === 'transparent', true ); ?>> <?php esc_html_e( 'Priehľadné', 'web-pixel-studio-cookie-consent-eu' ); ?></label>
							</div>
						<?php else : ?>
							<?php $this->render_color_picker_with_reset( $k, (string) ( $s->get( $k ) ?: $def ), $def ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="ccwps-card">
			<h2 class="ccwps-card-title-row">
				<span><?php esc_html_e( 'Okno modálu (Spravovať súhlas)', 'web-pixel-studio-cookie-consent-eu' ); ?></span>
				<?php $this->spacing_trigger_button( 'modal-body', $this->tx( 'Upraviť medzery obsahu modálu' ) ); ?>
			</h2>
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Farby pre okno nastavenia cookies, ktoré sa otvorí po kliknutí na "Spravovať nastavenia".', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
			<table class="ccwps-table">
				<?php
				$modal_colors = [
					'modal_bg'         => [ __( 'Pozadie modálu', 'web-pixel-studio-cookie-consent-eu' ),          '#ffffff' ],
					'modal_text'       => [ __( 'Farba textu', 'web-pixel-studio-cookie-consent-eu' ),               '#111827' ],
					'modal_consent_id_color' => [ __( 'Farba identifikátora súhlasu', 'web-pixel-studio-cookie-consent-eu' ), '#111827' ],
					'modal_header_bg'  => [ __( 'Pozadie hlavičky', 'web-pixel-studio-cookie-consent-eu' ),         '#ffffff' ],
					'modal_footer_bg'  => [ __( 'Pozadie päty', 'web-pixel-studio-cookie-consent-eu' ),             '#f9fafb' ],
					'modal_border'     => [ __( 'Farba ohraničení', 'web-pixel-studio-cookie-consent-eu' ),          '#e5e7eb' ],
					'cat_header_bg'    => [ __( 'Pozadie kategórie', 'web-pixel-studio-cookie-consent-eu' ),         '#f9fafb' ],
					'cat_header_bg_hv' => [ __( 'Pozadie kategórie (hover)', 'web-pixel-studio-cookie-consent-eu' ),'#f0f2f5' ],
					'toggle_on_color'  => [ __( 'Farba prepínača (zapnutý)', 'web-pixel-studio-cookie-consent-eu' ), '#1a73e8' ],
					'always_on_color'  => [ __( 'Farba textu "Vždy aktívne"', 'web-pixel-studio-cookie-consent-eu' ),'#1a73e8' ],
				];
				foreach ( $modal_colors as $k => [ $lbl, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label></th>
					<td><?php $this->render_color_picker_with_reset( $k, (string) ( $s->get( $k ) ?: $def ), $def ); ?></td>
				</tr>
				<?php endforeach; ?>
				<tr>
					<th><label for="modal_border_radius"><?php esc_html_e( 'Zaoblenie modálu (px)', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th>
					<td><input type="number" name="modal_border_radius" id="modal_border_radius" value="<?php echo esc_attr( $s->get( 'modal_border_radius' ) ?: 12 ); ?>" min="0" max="40" class="small-text"></td>
				</tr>
			</table>
		</div>

		<div class="ccwps-modal" id="ccwps-spacing-modal" style="display:none;">
			<div class="ccwps-modal-inner ccwps-spacing-modal-inner">
				<div class="ccwps-modal-header">
					<h3><?php echo esc_html( $this->tx( 'Vizuálne medzery' ) ); ?></h3>
					<button type="button" class="ccwps-modal-close" aria-label="<?php echo esc_attr( $this->tx( 'Zavrieť' ) ); ?>">&times;</button>
				</div>
				<div class="ccwps-modal-body">
					<p class="description" style="margin:0 0 16px;"><?php echo esc_html( $this->tx( 'Nastavenia menia iba tento plugin. Upravte medzery pre bannery a modál bez zásahu do zvyšku webu.' ) ); ?></p>
					<div class="ccwps-spacing-sections">
						<section class="ccwps-spacing-section" data-ccwps-spacing-group="banner-text">
							<h4><?php echo esc_html( $this->tx( 'Banner: texty' ) ); ?></h4>
							<table class="ccwps-table">
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Padding' ) ); ?></strong></td></tr>
								<tr><th><label for="banner_text_padding_t"><?php echo esc_html( $this->tx( 'Padding Top (px)' ) ); ?></label></th><td><input type="number" name="banner_text_padding_t" id="banner_text_padding_t" value="<?php echo esc_attr( $s->get( 'banner_text_padding_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_text_padding_r"><?php echo esc_html( $this->tx( 'Padding Right (px)' ) ); ?></label></th><td><input type="number" name="banner_text_padding_r" id="banner_text_padding_r" value="<?php echo esc_attr( $s->get( 'banner_text_padding_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_text_padding_b"><?php echo esc_html( $this->tx( 'Padding Bottom (px)' ) ); ?></label></th><td><input type="number" name="banner_text_padding_b" id="banner_text_padding_b" value="<?php echo esc_attr( $s->get( 'banner_text_padding_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_text_padding_l"><?php echo esc_html( $this->tx( 'Padding Left (px)' ) ); ?></label></th><td><input type="number" name="banner_text_padding_l" id="banner_text_padding_l" value="<?php echo esc_attr( $s->get( 'banner_text_padding_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Margin' ) ); ?></strong></td></tr>
								<tr><th><label for="banner_text_margin_t"><?php echo esc_html( $this->tx( 'Margin Top (px)' ) ); ?></label></th><td><input type="number" name="banner_text_margin_t" id="banner_text_margin_t" value="<?php echo esc_attr( $s->get( 'banner_text_margin_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_text_margin_r"><?php echo esc_html( $this->tx( 'Margin Right (px)' ) ); ?></label></th><td><input type="number" name="banner_text_margin_r" id="banner_text_margin_r" value="<?php echo esc_attr( $s->get( 'banner_text_margin_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_text_margin_b"><?php echo esc_html( $this->tx( 'Margin Bottom (px)' ) ); ?></label></th><td><input type="number" name="banner_text_margin_b" id="banner_text_margin_b" value="<?php echo esc_attr( $s->get( 'banner_text_margin_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_text_margin_l"><?php echo esc_html( $this->tx( 'Margin Left (px)' ) ); ?></label></th><td><input type="number" name="banner_text_margin_l" id="banner_text_margin_l" value="<?php echo esc_attr( $s->get( 'banner_text_margin_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
							</table>
						</section>
						<section class="ccwps-spacing-section" data-ccwps-spacing-group="banner-links">
							<h4><?php echo esc_html( $this->tx( 'Banner: právne odkazy' ) ); ?></h4>
							<table class="ccwps-table">
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Padding' ) ); ?></strong></td></tr>
								<tr><th><label for="banner_links_padding_t"><?php echo esc_html( $this->tx( 'Padding Top (px)' ) ); ?></label></th><td><input type="number" name="banner_links_padding_t" id="banner_links_padding_t" value="<?php echo esc_attr( $s->get( 'banner_links_padding_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_links_padding_r"><?php echo esc_html( $this->tx( 'Padding Right (px)' ) ); ?></label></th><td><input type="number" name="banner_links_padding_r" id="banner_links_padding_r" value="<?php echo esc_attr( $s->get( 'banner_links_padding_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_links_padding_b"><?php echo esc_html( $this->tx( 'Padding Bottom (px)' ) ); ?></label></th><td><input type="number" name="banner_links_padding_b" id="banner_links_padding_b" value="<?php echo esc_attr( $s->get( 'banner_links_padding_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_links_padding_l"><?php echo esc_html( $this->tx( 'Padding Left (px)' ) ); ?></label></th><td><input type="number" name="banner_links_padding_l" id="banner_links_padding_l" value="<?php echo esc_attr( $s->get( 'banner_links_padding_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Margin' ) ); ?></strong></td></tr>
								<tr><th><label for="banner_links_margin_t"><?php echo esc_html( $this->tx( 'Margin Top (px)' ) ); ?></label></th><td><input type="number" name="banner_links_margin_t" id="banner_links_margin_t" value="<?php echo esc_attr( $s->get( 'banner_links_margin_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_links_margin_r"><?php echo esc_html( $this->tx( 'Margin Right (px)' ) ); ?></label></th><td><input type="number" name="banner_links_margin_r" id="banner_links_margin_r" value="<?php echo esc_attr( $s->get( 'banner_links_margin_r' ) ); ?>" min="0" max="120" class="small-text"></td></tr>
								<tr><th><label for="banner_links_margin_b"><?php echo esc_html( $this->tx( 'Margin Bottom (px)' ) ); ?></label></th><td><input type="number" name="banner_links_margin_b" id="banner_links_margin_b" value="<?php echo esc_attr( $s->get( 'banner_links_margin_b' ) ); ?>" min="0" max="120" class="small-text"></td></tr>
								<tr><th><label for="banner_links_margin_l"><?php echo esc_html( $this->tx( 'Margin Left (px)' ) ); ?></label></th><td><input type="number" name="banner_links_margin_l" id="banner_links_margin_l" value="<?php echo esc_attr( $s->get( 'banner_links_margin_l' ) ); ?>" min="0" max="120" class="small-text"></td></tr>
							</table>
						</section>
						<section class="ccwps-spacing-section" data-ccwps-spacing-group="banner-accept">
							<h4><?php echo esc_html( $this->tx( 'Banner: tlačidlo Prijať všetky' ) ); ?></h4>
							<table class="ccwps-table">
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Padding' ) ); ?></strong></td></tr>
								<tr><th><label for="banner_accept_btn_padding_t"><?php echo esc_html( $this->tx( 'Padding Top (px)' ) ); ?></label></th><td><input type="number" name="banner_accept_btn_padding_t" id="banner_accept_btn_padding_t" value="<?php echo esc_attr( $s->get( 'banner_accept_btn_padding_t' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="banner_accept_btn_padding_r"><?php echo esc_html( $this->tx( 'Padding Right (px)' ) ); ?></label></th><td><input type="number" name="banner_accept_btn_padding_r" id="banner_accept_btn_padding_r" value="<?php echo esc_attr( $s->get( 'banner_accept_btn_padding_r' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="banner_accept_btn_padding_b"><?php echo esc_html( $this->tx( 'Padding Bottom (px)' ) ); ?></label></th><td><input type="number" name="banner_accept_btn_padding_b" id="banner_accept_btn_padding_b" value="<?php echo esc_attr( $s->get( 'banner_accept_btn_padding_b' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="banner_accept_btn_padding_l"><?php echo esc_html( $this->tx( 'Padding Left (px)' ) ); ?></label></th><td><input type="number" name="banner_accept_btn_padding_l" id="banner_accept_btn_padding_l" value="<?php echo esc_attr( $s->get( 'banner_accept_btn_padding_l' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Margin' ) ); ?></strong></td></tr>
								<tr><th><label for="banner_accept_btn_margin_t"><?php echo esc_html( $this->tx( 'Margin Top (px)' ) ); ?></label></th><td><input type="number" name="banner_accept_btn_margin_t" id="banner_accept_btn_margin_t" value="<?php echo esc_attr( $s->get( 'banner_accept_btn_margin_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_accept_btn_margin_r"><?php echo esc_html( $this->tx( 'Margin Right (px)' ) ); ?></label></th><td><input type="number" name="banner_accept_btn_margin_r" id="banner_accept_btn_margin_r" value="<?php echo esc_attr( $s->get( 'banner_accept_btn_margin_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_accept_btn_margin_b"><?php echo esc_html( $this->tx( 'Margin Bottom (px)' ) ); ?></label></th><td><input type="number" name="banner_accept_btn_margin_b" id="banner_accept_btn_margin_b" value="<?php echo esc_attr( $s->get( 'banner_accept_btn_margin_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_accept_btn_margin_l"><?php echo esc_html( $this->tx( 'Margin Left (px)' ) ); ?></label></th><td><input type="number" name="banner_accept_btn_margin_l" id="banner_accept_btn_margin_l" value="<?php echo esc_attr( $s->get( 'banner_accept_btn_margin_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
							</table>
						</section>
						<section class="ccwps-spacing-section" data-ccwps-spacing-group="banner-reject">
							<h4><?php echo esc_html( $this->tx( 'Banner: tlačidlo Odmietnuť všetky' ) ); ?></h4>
							<table class="ccwps-table">
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Padding' ) ); ?></strong></td></tr>
								<tr><th><label for="banner_reject_btn_padding_t"><?php echo esc_html( $this->tx( 'Padding Top (px)' ) ); ?></label></th><td><input type="number" name="banner_reject_btn_padding_t" id="banner_reject_btn_padding_t" value="<?php echo esc_attr( $s->get( 'banner_reject_btn_padding_t' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="banner_reject_btn_padding_r"><?php echo esc_html( $this->tx( 'Padding Right (px)' ) ); ?></label></th><td><input type="number" name="banner_reject_btn_padding_r" id="banner_reject_btn_padding_r" value="<?php echo esc_attr( $s->get( 'banner_reject_btn_padding_r' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="banner_reject_btn_padding_b"><?php echo esc_html( $this->tx( 'Padding Bottom (px)' ) ); ?></label></th><td><input type="number" name="banner_reject_btn_padding_b" id="banner_reject_btn_padding_b" value="<?php echo esc_attr( $s->get( 'banner_reject_btn_padding_b' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="banner_reject_btn_padding_l"><?php echo esc_html( $this->tx( 'Padding Left (px)' ) ); ?></label></th><td><input type="number" name="banner_reject_btn_padding_l" id="banner_reject_btn_padding_l" value="<?php echo esc_attr( $s->get( 'banner_reject_btn_padding_l' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Margin' ) ); ?></strong></td></tr>
								<tr><th><label for="banner_reject_btn_margin_t"><?php echo esc_html( $this->tx( 'Margin Top (px)' ) ); ?></label></th><td><input type="number" name="banner_reject_btn_margin_t" id="banner_reject_btn_margin_t" value="<?php echo esc_attr( $s->get( 'banner_reject_btn_margin_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_reject_btn_margin_r"><?php echo esc_html( $this->tx( 'Margin Right (px)' ) ); ?></label></th><td><input type="number" name="banner_reject_btn_margin_r" id="banner_reject_btn_margin_r" value="<?php echo esc_attr( $s->get( 'banner_reject_btn_margin_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_reject_btn_margin_b"><?php echo esc_html( $this->tx( 'Margin Bottom (px)' ) ); ?></label></th><td><input type="number" name="banner_reject_btn_margin_b" id="banner_reject_btn_margin_b" value="<?php echo esc_attr( $s->get( 'banner_reject_btn_margin_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_reject_btn_margin_l"><?php echo esc_html( $this->tx( 'Margin Left (px)' ) ); ?></label></th><td><input type="number" name="banner_reject_btn_margin_l" id="banner_reject_btn_margin_l" value="<?php echo esc_attr( $s->get( 'banner_reject_btn_margin_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
							</table>
						</section>
						<section class="ccwps-spacing-section" data-ccwps-spacing-group="banner-manage">
							<h4><?php echo esc_html( $this->tx( 'Banner: tlačidlo Spravovať nastavenia' ) ); ?></h4>
							<table class="ccwps-table">
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Padding' ) ); ?></strong></td></tr>
								<tr><th><label for="banner_manage_btn_padding_t"><?php echo esc_html( $this->tx( 'Padding Top (px)' ) ); ?></label></th><td><input type="number" name="banner_manage_btn_padding_t" id="banner_manage_btn_padding_t" value="<?php echo esc_attr( $s->get( 'banner_manage_btn_padding_t' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="banner_manage_btn_padding_r"><?php echo esc_html( $this->tx( 'Padding Right (px)' ) ); ?></label></th><td><input type="number" name="banner_manage_btn_padding_r" id="banner_manage_btn_padding_r" value="<?php echo esc_attr( $s->get( 'banner_manage_btn_padding_r' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="banner_manage_btn_padding_b"><?php echo esc_html( $this->tx( 'Padding Bottom (px)' ) ); ?></label></th><td><input type="number" name="banner_manage_btn_padding_b" id="banner_manage_btn_padding_b" value="<?php echo esc_attr( $s->get( 'banner_manage_btn_padding_b' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="banner_manage_btn_padding_l"><?php echo esc_html( $this->tx( 'Padding Left (px)' ) ); ?></label></th><td><input type="number" name="banner_manage_btn_padding_l" id="banner_manage_btn_padding_l" value="<?php echo esc_attr( $s->get( 'banner_manage_btn_padding_l' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Margin' ) ); ?></strong></td></tr>
								<tr><th><label for="banner_manage_btn_margin_t"><?php echo esc_html( $this->tx( 'Margin Top (px)' ) ); ?></label></th><td><input type="number" name="banner_manage_btn_margin_t" id="banner_manage_btn_margin_t" value="<?php echo esc_attr( $s->get( 'banner_manage_btn_margin_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_manage_btn_margin_r"><?php echo esc_html( $this->tx( 'Margin Right (px)' ) ); ?></label></th><td><input type="number" name="banner_manage_btn_margin_r" id="banner_manage_btn_margin_r" value="<?php echo esc_attr( $s->get( 'banner_manage_btn_margin_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_manage_btn_margin_b"><?php echo esc_html( $this->tx( 'Margin Bottom (px)' ) ); ?></label></th><td><input type="number" name="banner_manage_btn_margin_b" id="banner_manage_btn_margin_b" value="<?php echo esc_attr( $s->get( 'banner_manage_btn_margin_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="banner_manage_btn_margin_l"><?php echo esc_html( $this->tx( 'Margin Left (px)' ) ); ?></label></th><td><input type="number" name="banner_manage_btn_margin_l" id="banner_manage_btn_margin_l" value="<?php echo esc_attr( $s->get( 'banner_manage_btn_margin_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr>
									<th><label for="banner_btn_gap"><?php echo esc_html( $this->tx( 'Spoločná medzera medzi tlačidlami (px)' ) ); ?></label></th>
									<td><input type="number" name="banner_btn_gap" id="banner_btn_gap" value="<?php echo esc_attr( $s->get( 'banner_btn_gap' ) ); ?>" min="0" max="80" class="small-text"></td>
								</tr>
							</table>
						</section>
						<section class="ccwps-spacing-section" data-ccwps-spacing-group="modal-body">
							<h4><?php echo esc_html( $this->tx( 'Modál: obsah' ) ); ?></h4>
							<table class="ccwps-table">
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Padding' ) ); ?></strong></td></tr>
								<tr><th><label for="modal_body_padding_t"><?php echo esc_html( $this->tx( 'Padding Top (px)' ) ); ?></label></th><td><input type="number" name="modal_body_padding_t" id="modal_body_padding_t" value="<?php echo esc_attr( $s->get( 'modal_body_padding_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_body_padding_r"><?php echo esc_html( $this->tx( 'Padding Right (px)' ) ); ?></label></th><td><input type="number" name="modal_body_padding_r" id="modal_body_padding_r" value="<?php echo esc_attr( $s->get( 'modal_body_padding_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_body_padding_b"><?php echo esc_html( $this->tx( 'Padding Bottom (px)' ) ); ?></label></th><td><input type="number" name="modal_body_padding_b" id="modal_body_padding_b" value="<?php echo esc_attr( $s->get( 'modal_body_padding_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_body_padding_l"><?php echo esc_html( $this->tx( 'Padding Left (px)' ) ); ?></label></th><td><input type="number" name="modal_body_padding_l" id="modal_body_padding_l" value="<?php echo esc_attr( $s->get( 'modal_body_padding_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Margin' ) ); ?></strong></td></tr>
								<tr><th><label for="modal_body_margin_t"><?php echo esc_html( $this->tx( 'Margin Top (px)' ) ); ?></label></th><td><input type="number" name="modal_body_margin_t" id="modal_body_margin_t" value="<?php echo esc_attr( $s->get( 'modal_body_margin_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_body_margin_r"><?php echo esc_html( $this->tx( 'Margin Right (px)' ) ); ?></label></th><td><input type="number" name="modal_body_margin_r" id="modal_body_margin_r" value="<?php echo esc_attr( $s->get( 'modal_body_margin_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_body_margin_b"><?php echo esc_html( $this->tx( 'Margin Bottom (px)' ) ); ?></label></th><td><input type="number" name="modal_body_margin_b" id="modal_body_margin_b" value="<?php echo esc_attr( $s->get( 'modal_body_margin_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_body_margin_l"><?php echo esc_html( $this->tx( 'Margin Left (px)' ) ); ?></label></th><td><input type="number" name="modal_body_margin_l" id="modal_body_margin_l" value="<?php echo esc_attr( $s->get( 'modal_body_margin_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Modál: popis' ) ); ?></strong></td></tr>
								<tr><th><label for="modal_desc_margin_t"><?php echo esc_html( $this->tx( 'Margin Top (px)' ) ); ?></label></th><td><input type="number" name="modal_desc_margin_t" id="modal_desc_margin_t" value="<?php echo esc_attr( $s->get( 'modal_desc_margin_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_desc_margin_r"><?php echo esc_html( $this->tx( 'Margin Right (px)' ) ); ?></label></th><td><input type="number" name="modal_desc_margin_r" id="modal_desc_margin_r" value="<?php echo esc_attr( $s->get( 'modal_desc_margin_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_desc_margin_b"><?php echo esc_html( $this->tx( 'Margin Bottom (px)' ) ); ?></label></th><td><input type="number" name="modal_desc_margin_b" id="modal_desc_margin_b" value="<?php echo esc_attr( $s->get( 'modal_desc_margin_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_desc_margin_l"><?php echo esc_html( $this->tx( 'Margin Left (px)' ) ); ?></label></th><td><input type="number" name="modal_desc_margin_l" id="modal_desc_margin_l" value="<?php echo esc_attr( $s->get( 'modal_desc_margin_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr>
									<th><label for="modal_btn_gap"><?php echo esc_html( $this->tx( 'Medzera medzi tlačidlami (px)' ) ); ?></label></th>
									<td><input type="number" name="modal_btn_gap" id="modal_btn_gap" value="<?php echo esc_attr( $s->get( 'modal_btn_gap' ) ); ?>" min="0" max="80" class="small-text"></td>
								</tr>
							</table>
						</section>
						<section class="ccwps-spacing-section" data-ccwps-spacing-group="modal-save">
							<h4><?php echo esc_html( $this->tx( 'Modál: tlačidlo Uložiť nastavenia' ) ); ?></h4>
							<table class="ccwps-table">
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Padding' ) ); ?></strong></td></tr>
								<tr><th><label for="modal_save_btn_padding_t"><?php echo esc_html( $this->tx( 'Padding Top (px)' ) ); ?></label></th><td><input type="number" name="modal_save_btn_padding_t" id="modal_save_btn_padding_t" value="<?php echo esc_attr( $s->get( 'modal_save_btn_padding_t' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="modal_save_btn_padding_r"><?php echo esc_html( $this->tx( 'Padding Right (px)' ) ); ?></label></th><td><input type="number" name="modal_save_btn_padding_r" id="modal_save_btn_padding_r" value="<?php echo esc_attr( $s->get( 'modal_save_btn_padding_r' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="modal_save_btn_padding_b"><?php echo esc_html( $this->tx( 'Padding Bottom (px)' ) ); ?></label></th><td><input type="number" name="modal_save_btn_padding_b" id="modal_save_btn_padding_b" value="<?php echo esc_attr( $s->get( 'modal_save_btn_padding_b' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="modal_save_btn_padding_l"><?php echo esc_html( $this->tx( 'Padding Left (px)' ) ); ?></label></th><td><input type="number" name="modal_save_btn_padding_l" id="modal_save_btn_padding_l" value="<?php echo esc_attr( $s->get( 'modal_save_btn_padding_l' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Margin' ) ); ?></strong></td></tr>
								<tr><th><label for="modal_save_btn_margin_t"><?php echo esc_html( $this->tx( 'Margin Top (px)' ) ); ?></label></th><td><input type="number" name="modal_save_btn_margin_t" id="modal_save_btn_margin_t" value="<?php echo esc_attr( $s->get( 'modal_save_btn_margin_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_save_btn_margin_r"><?php echo esc_html( $this->tx( 'Margin Right (px)' ) ); ?></label></th><td><input type="number" name="modal_save_btn_margin_r" id="modal_save_btn_margin_r" value="<?php echo esc_attr( $s->get( 'modal_save_btn_margin_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_save_btn_margin_b"><?php echo esc_html( $this->tx( 'Margin Bottom (px)' ) ); ?></label></th><td><input type="number" name="modal_save_btn_margin_b" id="modal_save_btn_margin_b" value="<?php echo esc_attr( $s->get( 'modal_save_btn_margin_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_save_btn_margin_l"><?php echo esc_html( $this->tx( 'Margin Left (px)' ) ); ?></label></th><td><input type="number" name="modal_save_btn_margin_l" id="modal_save_btn_margin_l" value="<?php echo esc_attr( $s->get( 'modal_save_btn_margin_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
							</table>
						</section>
						<section class="ccwps-spacing-section" data-ccwps-spacing-group="modal-reject">
							<h4><?php echo esc_html( $this->tx( 'Modál: tlačidlo Odmietnuť všetky' ) ); ?></h4>
							<table class="ccwps-table">
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Padding' ) ); ?></strong></td></tr>
								<tr><th><label for="modal_reject_btn_padding_t"><?php echo esc_html( $this->tx( 'Padding Top (px)' ) ); ?></label></th><td><input type="number" name="modal_reject_btn_padding_t" id="modal_reject_btn_padding_t" value="<?php echo esc_attr( $s->get( 'modal_reject_btn_padding_t' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="modal_reject_btn_padding_r"><?php echo esc_html( $this->tx( 'Padding Right (px)' ) ); ?></label></th><td><input type="number" name="modal_reject_btn_padding_r" id="modal_reject_btn_padding_r" value="<?php echo esc_attr( $s->get( 'modal_reject_btn_padding_r' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="modal_reject_btn_padding_b"><?php echo esc_html( $this->tx( 'Padding Bottom (px)' ) ); ?></label></th><td><input type="number" name="modal_reject_btn_padding_b" id="modal_reject_btn_padding_b" value="<?php echo esc_attr( $s->get( 'modal_reject_btn_padding_b' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="modal_reject_btn_padding_l"><?php echo esc_html( $this->tx( 'Padding Left (px)' ) ); ?></label></th><td><input type="number" name="modal_reject_btn_padding_l" id="modal_reject_btn_padding_l" value="<?php echo esc_attr( $s->get( 'modal_reject_btn_padding_l' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Margin' ) ); ?></strong></td></tr>
								<tr><th><label for="modal_reject_btn_margin_t"><?php echo esc_html( $this->tx( 'Margin Top (px)' ) ); ?></label></th><td><input type="number" name="modal_reject_btn_margin_t" id="modal_reject_btn_margin_t" value="<?php echo esc_attr( $s->get( 'modal_reject_btn_margin_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_reject_btn_margin_r"><?php echo esc_html( $this->tx( 'Margin Right (px)' ) ); ?></label></th><td><input type="number" name="modal_reject_btn_margin_r" id="modal_reject_btn_margin_r" value="<?php echo esc_attr( $s->get( 'modal_reject_btn_margin_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_reject_btn_margin_b"><?php echo esc_html( $this->tx( 'Margin Bottom (px)' ) ); ?></label></th><td><input type="number" name="modal_reject_btn_margin_b" id="modal_reject_btn_margin_b" value="<?php echo esc_attr( $s->get( 'modal_reject_btn_margin_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_reject_btn_margin_l"><?php echo esc_html( $this->tx( 'Margin Left (px)' ) ); ?></label></th><td><input type="number" name="modal_reject_btn_margin_l" id="modal_reject_btn_margin_l" value="<?php echo esc_attr( $s->get( 'modal_reject_btn_margin_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
							</table>
						</section>
						<section class="ccwps-spacing-section" data-ccwps-spacing-group="modal-accept">
							<h4><?php echo esc_html( $this->tx( 'Modál: tlačidlo Prijať všetky' ) ); ?></h4>
							<table class="ccwps-table">
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Padding' ) ); ?></strong></td></tr>
								<tr><th><label for="modal_accept_btn_padding_t"><?php echo esc_html( $this->tx( 'Padding Top (px)' ) ); ?></label></th><td><input type="number" name="modal_accept_btn_padding_t" id="modal_accept_btn_padding_t" value="<?php echo esc_attr( $s->get( 'modal_accept_btn_padding_t' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="modal_accept_btn_padding_r"><?php echo esc_html( $this->tx( 'Padding Right (px)' ) ); ?></label></th><td><input type="number" name="modal_accept_btn_padding_r" id="modal_accept_btn_padding_r" value="<?php echo esc_attr( $s->get( 'modal_accept_btn_padding_r' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="modal_accept_btn_padding_b"><?php echo esc_html( $this->tx( 'Padding Bottom (px)' ) ); ?></label></th><td><input type="number" name="modal_accept_btn_padding_b" id="modal_accept_btn_padding_b" value="<?php echo esc_attr( $s->get( 'modal_accept_btn_padding_b' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><th><label for="modal_accept_btn_padding_l"><?php echo esc_html( $this->tx( 'Padding Left (px)' ) ); ?></label></th><td><input type="number" name="modal_accept_btn_padding_l" id="modal_accept_btn_padding_l" value="<?php echo esc_attr( $s->get( 'modal_accept_btn_padding_l' ) ); ?>" min="0" max="80" class="small-text"></td></tr>
								<tr><td colspan="2"><strong><?php echo esc_html( $this->tx( 'Margin' ) ); ?></strong></td></tr>
								<tr><th><label for="modal_accept_btn_margin_t"><?php echo esc_html( $this->tx( 'Margin Top (px)' ) ); ?></label></th><td><input type="number" name="modal_accept_btn_margin_t" id="modal_accept_btn_margin_t" value="<?php echo esc_attr( $s->get( 'modal_accept_btn_margin_t' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_accept_btn_margin_r"><?php echo esc_html( $this->tx( 'Margin Right (px)' ) ); ?></label></th><td><input type="number" name="modal_accept_btn_margin_r" id="modal_accept_btn_margin_r" value="<?php echo esc_attr( $s->get( 'modal_accept_btn_margin_r' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_accept_btn_margin_b"><?php echo esc_html( $this->tx( 'Margin Bottom (px)' ) ); ?></label></th><td><input type="number" name="modal_accept_btn_margin_b" id="modal_accept_btn_margin_b" value="<?php echo esc_attr( $s->get( 'modal_accept_btn_margin_b' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
								<tr><th><label for="modal_accept_btn_margin_l"><?php echo esc_html( $this->tx( 'Margin Left (px)' ) ); ?></label></th><td><input type="number" name="modal_accept_btn_margin_l" id="modal_accept_btn_margin_l" value="<?php echo esc_attr( $s->get( 'modal_accept_btn_margin_l' ) ); ?>" min="0" max="100" class="small-text"></td></tr>
							</table>
						</section>
					</div>
				</div>
				<div class="ccwps-modal-footer">
					<button type="button" class="button ccwps-btn-secondary-action ccwps-reset-spacing-only"><?php echo esc_html( $this->tx( '↺ Resetovať tieto medzery' ) ); ?></button>
					<button type="button" class="button button-primary ccwps-modal-close"><?php echo esc_html( $this->tx( 'Zavrieť' ) ); ?></button>
				</div>
			</div>
		</div>

		<div class="ccwps-card">
			<h2 class="ccwps-card-title-row">
				<span><?php esc_html_e( 'Tlačidlo [ccwps_manage_consent]', 'web-pixel-studio-cookie-consent-eu' ); ?></span>
			</h2>
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Farebné a vzhľadové nastavenia pre shortcode tlačidlo [ccwps_manage_consent], ktoré môžete vložiť kdekoľvek na stránku.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
			<table class="ccwps-table">
				<?php
				$manage_btn_fields = [
					'manage_btn_bg'        => [ __( 'Pozadie', 'web-pixel-studio-cookie-consent-eu' ),             '#1a73e8' ],
					'manage_btn_bg_hv'     => [ __( 'Pozadie (hover)', 'web-pixel-studio-cookie-consent-eu' ),     '#1557b0' ],
					'manage_btn_txt'       => [ __( 'Farba textu', 'web-pixel-studio-cookie-consent-eu' ),         '#ffffff' ],
					'manage_btn_txt_hv'    => [ __( 'Farba textu (hover)', 'web-pixel-studio-cookie-consent-eu' ), '#ffffff' ],
					'manage_btn_border'    => [ __( 'Farba ohraničenia', 'web-pixel-studio-cookie-consent-eu' ),   '#1a73e8' ],
					'manage_btn_border_hv' => [ __( 'Farba ohraničenia (hover)', 'web-pixel-studio-cookie-consent-eu' ), '#1557b0' ],
				];
				foreach ( $manage_btn_fields as $k => [ $lbl, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label></th>
					<td><?php $this->render_color_picker_with_reset( $k, (string) ( $s->get( $k ) ?: $def ), $def ); ?></td>
				</tr>
				<?php endforeach; ?>
				<tr>
					<th><label for="manage_btn_border_width"><?php esc_html_e( 'Hrúbka ohraničenia (px)', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th>
					<td><input type="number" name="manage_btn_border_width" id="manage_btn_border_width" value="<?php echo esc_attr( $s->get( 'manage_btn_border_width', 2 ) ); ?>" min="0" max="10" class="small-text"><?php $this->tip( __( '💡 0 = bez ohraničenia. Predvolená hodnota: 2 px.', 'web-pixel-studio-cookie-consent-eu' ) ); ?></td>
				</tr>
				<tr>
					<th><label for="manage_btn_radius"><?php esc_html_e( 'Zaoblenie rohov (px)', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th>
					<td><input type="number" name="manage_btn_radius" id="manage_btn_radius" value="<?php echo esc_attr( $s->get( 'manage_btn_radius', 8 ) ); ?>" min="0" max="50" class="small-text"><?php $this->tip( __( '💡 0 = hranaté, 8 = mierne zaoblené, 24+ = "pill" tvar.', 'web-pixel-studio-cookie-consent-eu' ) ); ?></td>
				</tr>
			</table>
		</div>

		<div class="ccwps-form-actions">
			<button type="button" class="button ccwps-btn-secondary-action ccwps-reset-appearance"><?php echo esc_html( $this->tx( '↺ Obnoviť predvolené' ) ); ?></button>
			<button type="button" class="button button-primary ccwps-save-settings ccwps-btn-primary-action"><?php echo esc_html( $this->t( 'admin_btn_save_appearance', 'Uložiť vzhľad' ) ); ?></button>
		</div>
		</form>
		<?php
	}

	private function get_appearance_defaults(): array {
		return [
			'banner_layout'       => 'box',
			'banner_position'     => 'middle-center',
			'banner_show_icon'    => '1',
			'floating_show_powered_by' => '0',
			'icon_position'       => 'bottom-left',
			'floating_icon_margin_y' => '0',
			'floating_icon_margin_y_mobile' => '0',
			'icon_type'           => 'cookie',
			'icon_custom_url'     => '',
			'banner_logo_show'    => '0',
			'banner_logo_url'     => '',
			'banner_logo_link_url'=> '',
			'banner_logo_width'   => '40',
			'add_cookie_policy_link' => '0',
			'cookie_policy_link_url' => '',
			'cookie_policy_link_text' => 'Cookie Policy',
			'add_privacy_policy_link' => '0',
			'privacy_policy_link_url' => '',
			'privacy_policy_link_text' => 'Privacy Policy',
			'banner_text_padding_t' => '0',
			'banner_text_padding_r' => '0',
			'banner_text_padding_b' => '0',
			'banner_text_padding_l' => '0',
			'banner_text_margin_t' => '0',
			'banner_text_margin_r' => '0',
			'banner_text_margin_b' => '5',
			'banner_text_margin_l' => '0',
			'banner_links_padding_t' => '0',
			'banner_links_padding_r' => '0',
			'banner_links_padding_b' => '0',
			'banner_links_padding_l' => '0',
			'banner_links_margin_t' => '0',
			'banner_links_margin_r' => '0',
			'banner_links_margin_b' => '30',
			'banner_links_margin_l' => '0',
			'banner_accept_btn_padding_t' => '11',
			'banner_accept_btn_padding_r' => '20',
			'banner_accept_btn_padding_b' => '11',
			'banner_accept_btn_padding_l' => '20',
			'banner_accept_btn_margin_t' => '0',
			'banner_accept_btn_margin_r' => '0',
			'banner_accept_btn_margin_b' => '0',
			'banner_accept_btn_margin_l' => '0',
			'banner_reject_btn_padding_t' => '11',
			'banner_reject_btn_padding_r' => '20',
			'banner_reject_btn_padding_b' => '11',
			'banner_reject_btn_padding_l' => '20',
			'banner_reject_btn_margin_t' => '0',
			'banner_reject_btn_margin_r' => '0',
			'banner_reject_btn_margin_b' => '0',
			'banner_reject_btn_margin_l' => '0',
			'banner_manage_btn_padding_t' => '11',
			'banner_manage_btn_padding_r' => '20',
			'banner_manage_btn_padding_b' => '11',
			'banner_manage_btn_padding_l' => '20',
			'banner_manage_btn_margin_t' => '0',
			'banner_manage_btn_margin_r' => '0',
			'banner_manage_btn_margin_b' => '0',
			'banner_manage_btn_margin_l' => '0',
			'banner_btn_gap'       => '10',
			'modal_body_padding_t' => '24',
			'modal_body_padding_r' => '28',
			'modal_body_padding_b' => '24',
			'modal_body_padding_l' => '28',
			'modal_body_margin_t' => '0',
			'modal_body_margin_r' => '0',
			'modal_body_margin_b' => '0',
			'modal_body_margin_l' => '0',
			'modal_save_btn_padding_t' => '11',
			'modal_save_btn_padding_r' => '20',
			'modal_save_btn_padding_b' => '11',
			'modal_save_btn_padding_l' => '20',
			'modal_save_btn_margin_t' => '0',
			'modal_save_btn_margin_r' => '0',
			'modal_save_btn_margin_b' => '0',
			'modal_save_btn_margin_l' => '0',
			'modal_reject_btn_padding_t' => '11',
			'modal_reject_btn_padding_r' => '20',
			'modal_reject_btn_padding_b' => '11',
			'modal_reject_btn_padding_l' => '20',
			'modal_reject_btn_margin_t' => '0',
			'modal_reject_btn_margin_r' => '0',
			'modal_reject_btn_margin_b' => '0',
			'modal_reject_btn_margin_l' => '0',
			'modal_accept_btn_padding_t' => '11',
			'modal_accept_btn_padding_r' => '20',
			'modal_accept_btn_padding_b' => '11',
			'modal_accept_btn_padding_l' => '20',
			'modal_accept_btn_margin_t' => '0',
			'modal_accept_btn_margin_r' => '0',
			'modal_accept_btn_margin_b' => '0',
			'modal_accept_btn_margin_l' => '0',
			'modal_btn_gap'        => '10',
			'modal_desc_margin_t' => '0',
			'modal_desc_margin_r' => '0',
			'modal_desc_margin_b' => '6',
			'modal_desc_margin_l' => '0',
			'floating_icon_bg'    => '#1a73e8',
			'floating_icon_bg_hv' => '#1557b0',
			'floating_icon_color' => '#ffffff',
			'floating_popup_bg'   => '#ffffff',
			'floating_popup_text' => '#111827',
			'font_family'         => '\'Poppins\', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
			'btn_border_radius'   => '8',
			'banner_border_radius'=> '12',
			'modal_border_radius' => '12',
			'bg_color'            => '#ffffff',
			'cloud_bg_opacity'    => '70',
			'banner_title_color'  => '#111827',
			'text_color'          => '#111827',
			'primary_color'       => '#1a73e8',
			'btn_primary_bg'      => '#1a73e8',
			'btn_primary_bg_hv'   => '#1557b0',
			'btn_primary_txt'     => '#ffffff',
			'btn_text_color'      => '#ffffff',
			'btn_ghost_bg'        => '#1a73e8',
			'btn_ghost_bg_hv'     => '#1557b0',
			'btn_ghost_txt'       => '#ffffff',
			'btn_ghost_txt_hv'    => '#ffffff',
			'btn_outline_bg'      => 'transparent',
			'btn_outline_bg_hv'   => '#1a73e8',
			'btn_outline_txt'     => '#1a73e8',
			'btn_outline_border'  => '#1a73e8',
			'modal_bg'            => '#ffffff',
			'modal_text'          => '#111827',
			'modal_consent_id_color' => '#111827',
			'modal_header_bg'     => '#ffffff',
			'modal_footer_bg'     => '#f9fafb',
			'modal_border'        => '#e5e7eb',
			'cat_header_bg'       => '#f9fafb',
			'cat_header_bg_hv'    => '#f0f2f5',
			'toggle_on_color'     => '#1a73e8',
			'always_on_color'     => '#1a73e8',
		];
	}

	private function render_color_picker_with_reset( string $key, string $value, string $default ): void {
		?>
		<div class="ccwps-color-field-wrap">
			<input
				type="text"
				name="<?php echo esc_attr( $key ); ?>"
				id="<?php echo esc_attr( $key ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				class="ccwps-color-picker"
				data-default-color="<?php echo esc_attr( $default ); ?>"
			>
			<button
				type="button"
				class="button ccwps-color-reset"
				data-target="<?php echo esc_attr( $key ); ?>"
				data-default="<?php echo esc_attr( $default ); ?>"
				title="<?php echo esc_attr( $this->tx( 'Obnoviť predvolenú farbu' ) ); ?>"
				aria-label="<?php echo esc_attr( $this->tx( 'Obnoviť predvolenú farbu' ) ); ?>"
			>↺</button>
		</div>
		<?php
	}

	private function spacing_trigger_button( string $focus, string $label ): void {
		?>
		<button type="button" class="ccwps-spacing-trigger" data-ccwps-spacing-focus="<?php echo esc_attr( $focus ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
			<span aria-hidden="true">⚙</span>
		</button>
		<?php
	}

	/* ================================================
	   TRANSLATIONS TAB
	   ================================================ */
	private function tab_translations(): void {
		$s = $this->settings;
		$fields = [
			__( 'Banner', 'web-pixel-studio-cookie-consent-eu' ) => [
				'lang_banner_title'       => $this->t( 'admin_trans_banner_title', 'Nadpis bannera' ),
				'lang_banner_description' => $this->t( 'admin_trans_banner_desc', 'Popis bannera' ),
				'lang_accept_all'         => $this->t( 'admin_trans_accept_all', 'Tlačidlo "Prijať všetky"' ),
				'lang_reject_all'         => $this->t( 'admin_trans_reject_all', 'Tlačidlo "Odmietnuť všetky"' ),
				'lang_manage_preferences' => $this->t( 'admin_trans_manage_pref', 'Tlačidlo "Prispôsobiť"' ),
			],
			$this->t( 'admin_trans_sect_modal', 'Modál preferencií' ) => [
				'lang_save_preferences'   => $this->t( 'admin_trans_save_pref', 'Tlačidlo "Uložiť nastavenia"' ),
				'lang_close'              => $this->t( 'admin_trans_close', 'Tlačidlo "Zavrieť"' ),
				'lang_consent_id_label'   => $this->t( 'admin_trans_consent_id', 'Popis ID súhlasu' ),
				'lang_always_on'          => $this->t( 'admin_trans_always_on', 'Štítok "Vždy aktívne"' ),
			],
			$this->t( 'admin_trans_sect_cookie_table', 'Tabuľka cookies' ) => [
				'lang_cookie_name'        => $this->t( 'admin_trans_col_name', 'Stĺpec Názov' ),
				'lang_cookie_domain'      => $this->t( 'admin_trans_col_domain', 'Stĺpec Doména' ),
				'lang_cookie_expiration'  => $this->t( 'admin_trans_col_expiry', 'Stĺpec Platnosť' ),
				'lang_cookie_description' => $this->t( 'admin_trans_col_desc', 'Stĺpec Popis' ),
			],
			$this->t( 'admin_trans_sect_categories', 'Kategórie cookies' ) => [
				'lang_necessary_title'    => $this->t( 'admin_trans_necessary_title', 'Nevyhnutné – Nadpis' ),
				'lang_necessary_desc'     => $this->t( 'admin_trans_necessary_desc', 'Nevyhnutné – Popis' ),
				'lang_analytics_title'    => $this->t( 'admin_trans_analytics_title', 'Analytické – Nadpis' ),
				'lang_analytics_desc'     => $this->t( 'admin_trans_analytics_desc', 'Analytické – Popis' ),
				'lang_targeting_title'    => $this->t( 'admin_trans_targeting_title', 'Marketingové – Nadpis' ),
				'lang_targeting_desc'     => $this->t( 'admin_trans_targeting_desc', 'Marketingové – Popis' ),
				'lang_preferences_title'  => $this->t( 'admin_trans_preferences_title', 'Preferenčné – Nadpis' ),
				'lang_preferences_desc'   => $this->t( 'admin_trans_preferences_desc', 'Preferenčné – Popis' ),
			],
		];
		$textareas = [ 'lang_banner_description', 'lang_necessary_desc', 'lang_analytics_desc', 'lang_targeting_desc', 'lang_preferences_desc' ];
		?>
		<div class="ccwps-page-header"><h1><?php echo esc_html( $this->t( 'admin_tab_translations_h1', 'Preklady frontendu' ) ); ?></h1></div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->t( 'admin_sect_lang_presets', 'Rýchle jazykové predvoľby' ) ); ?></h2>
			<p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Kliknite na jazyk – všetky polia sa vyplnia automaticky. Potom kliknite Uložiť.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
			<div class="ccwps-lang-grid">
				<?php foreach ( CCWPS_Language_Presets::get_all() as $code => $data ) : ?>
					<button type="button" class="ccwps-lang-btn button" data-lang="<?php echo esc_attr( $code ); ?>">
						<img src="<?php echo esc_url( CCWPS_PLUGIN_URL . 'admin/images/flags/' . $data['flag_file'] . '.svg' ); ?>" alt="<?php echo esc_attr( $data['label'] ); ?>" class="ccwps-lang-flag">
						<?php echo esc_html( $data['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>

		<form id="ccwps-settings-form" class="ccwps-form">
			<?php foreach ( $fields as $section => $items ) : ?>
			<div class="ccwps-card">
				<h2><?php echo esc_html( $section ); ?></h2>
				<table class="ccwps-table">
					<?php foreach ( $items as $key => $label ) : ?>
					<tr>
						<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<?php if ( in_array( $key, $textareas, true ) ) : ?>
								<textarea name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" rows="3" class="large-text" data-lang-key="<?php echo esc_attr( $key ); ?>"><?php echo esc_textarea( $s->get( $key ) ); ?></textarea>
							<?php else : ?>
								<input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $s->get( $key ) ); ?>" class="regular-text" data-lang-key="<?php echo esc_attr( $key ); ?>">
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
			</div>
			<?php endforeach; ?>
			<div class="ccwps-form-actions">
				<button type="button" class="button button-primary ccwps-save-settings ccwps-btn-primary-action"><?php echo esc_html( $this->t( 'admin_btn_save_translations', 'Uložiť preklady' ) ); ?></button>
			</div>
		</form>
		<?php
	}

	/* ================================================
	   COOKIES TAB
	   ================================================ */
	private function tab_cookies(): void {
		$this->ensure_required_plugin_cookies();
		$cookies    = $this->cookie_manager->get_all();
		$categories = [ 'necessary', 'analytics', 'targeting', 'preferences' ];
		?>
		<div class="ccwps-page-header">
			<h1><?php echo esc_html( $this->t( 'admin_tab_cookies_h1', 'Deklarácia cookies' ) ); ?></h1>
			<div class="ccwps-header-actions">
				<button type="button" class="button ccwps-btn-secondary-action" id="ccwps-request-preset"><?php echo esc_html( $this->tx( 'Žiadosť o pridanie predvoľby' ) ); ?></button>
				<button type="button" class="button button-primary ccwps-btn-primary-action" id="ccwps-add-cookie">+ <?php echo esc_html( $this->t( 'admin_btn_add_cookie', 'Pridať cookie' ) ); ?></button>
				<button type="button" class="button ccwps-btn-secondary-action" id="ccwps-preview-cookie-list">📋 <?php echo esc_html( $this->t( 'admin_btn_show_list', 'Zobraziť zoznam' ) ); ?></button>
			</div>
		</div>
		<div class="ccwps-card">
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Popis cookie je viditeľný návštevníkom v modáli preferencií – pomáha im pochopiť, na čo daná cookie slúži.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
			<div class="ccwps-bulk-actions" id="ccwps-cookies-bulk-actions" style="margin-bottom:12px;display:none;">
				<button type="button" class="button button-link-delete ccwps-btn-danger-action" id="ccwps-delete-cookies-bulk"><?php echo esc_html( $this->tx( '🗑️ Odstrániť vybrané' ) ); ?></button>
				<span class="ccwps-bulk-count" id="ccwps-cookies-bulk-count"></span>
			</div>
			<div class="ccwps-table-scroll">
				<table class="wp-list-table widefat striped ccwps-data-table">
					<thead><tr>
						<th style="width:40px;"><input type="checkbox" class="ccwps-cookies-select-all" title="<?php echo esc_attr( $this->tx( 'Vybrať všetko / Zrušiť výber' ) ); ?>"></th>
						<th><?php echo esc_html( $this->t( 'admin_col_name', 'Názov' ) ); ?></th>
						<th><?php echo esc_html( $this->t( 'admin_col_domain', 'Doména' ) ); ?></th>
						<th><?php echo esc_html( $this->t( 'admin_col_expiry', 'Platnosť' ) ); ?></th>
						<th><?php echo esc_html( $this->t( 'admin_col_description', 'Popis' ) ); ?> <span class="ccwps-tag-info"><?php esc_html_e( 'viditeľný návštevníkom', 'web-pixel-studio-cookie-consent-eu' ); ?></span></th>
						<th><?php echo esc_html( $this->t( 'admin_col_category', 'Kategória' ) ); ?></th>
						<th><?php esc_html_e( 'Regex', 'web-pixel-studio-cookie-consent-eu' ); ?></th>
						<th><?php echo esc_html( $this->t( 'admin_col_actions', 'Akcie' ) ); ?></th>
					</tr></thead>
					<tbody>
					<?php if ( empty( $cookies ) ) : ?>
						<tr><td colspan="8" style="text-align:center;padding:24px;"><?php esc_html_e( 'Žiadne cookies. Kliknite "+ Pridať cookie".', 'web-pixel-studio-cookie-consent-eu' ); ?></td></tr>
					<?php else : foreach ( $cookies as $c ) : ?>
						<tr class="ccwps-cookies-row" data-cookie-name="<?php echo esc_attr( $c['name'] ); ?>">
							<td style="width:40px;"><input type="checkbox" class="ccwps-cookie-checkbox" data-id="<?php echo esc_attr( $c['id'] ); ?>"></td>
							<td><strong><?php echo esc_html( $c['name'] ); ?></strong></td>
							<td><?php echo esc_html( $c['domain'] ); ?></td>
							<td><?php echo esc_html( $c['expiration'] ); ?></td>
							<td title="<?php echo esc_attr( $c['description'] ); ?>"><span class="ccwps-desc-preview"><?php echo esc_html( $c['description'] ? ( mb_strlen( $c['description'] ) > 70 ? mb_substr( $c['description'], 0, 70 ) . '…' : $c['description'] ) : '—' ); ?></span></td>
							<td><span class="ccwps-badge ccwps-badge-<?php echo esc_attr( $c['category'] ); ?>"><?php echo esc_html( ucfirst( $c['category'] ) ); ?></span></td>
							<td><?php echo $c['is_regex'] ? '<span class="ccwps-badge ccwps-badge-regex">Regex</span>' : '—'; ?></td>
							<td>
								<button class="button button-small ccwps-edit-cookie" data-row='<?php echo esc_attr( wp_json_encode( $c ) ); ?>'><?php echo esc_html( $this->t( 'admin_btn_edit', 'Upraviť' ) ); ?></button>
								<button class="button button-small button-link-delete ccwps-delete-cookie" data-id="<?php echo esc_attr( $c['id'] ); ?>"><?php echo esc_html( $this->t( 'admin_btn_delete', 'Zmazať' ) ); ?></button>
							</td>
						</tr>
					<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Cookie edit modal -->
		<div id="ccwps-cookie-modal" class="ccwps-modal" style="display:none;">
			<div class="ccwps-modal-inner">
				<div class="ccwps-modal-header"><h3 id="ccwps-cookie-modal-title"><?php echo esc_html( $this->t( 'admin_btn_add_cookie', 'Pridať cookie' ) ); ?></h3><button type="button" class="ccwps-modal-close">×</button></div>
				<div class="ccwps-modal-body">
					<input type="hidden" id="ccwps-cookie-id">
					<table class="ccwps-table">
						<tr><th><label for="c-preset"><?php echo esc_html( $this->tx( 'Predpripravené predvoľby' ) ); ?></label></th><td><div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;"><select id="c-preset" style="min-width:260px;"><option value=""><?php echo esc_html( $this->tx( 'Vyberte predvoľbu' ) ); ?></option><option value="google_necessary"><?php echo esc_html( $this->tx( 'Google Necessary' ) ); ?></option><option value="google_analytics"><?php echo esc_html( $this->tx( 'GA4 + GTM' ) ); ?></option><option value="google_targeting"><?php echo esc_html( $this->tx( 'Google Targeting' ) ); ?></option><option value="google_preferences"><?php echo esc_html( $this->tx( 'Google Preferences' ) ); ?></option><option value="google_ads"><?php echo esc_html( $this->tx( 'Google Ads' ) ); ?></option><option value="facebook_pixel"><?php echo esc_html( $this->tx( 'Facebook Pixel' ) ); ?></option><option value="pixel_your_site"><?php echo esc_html( $this->tx( 'Pixel Your Site' ) ); ?></option><option value="matomo_analytics"><?php echo esc_html( $this->tx( 'Matomo Analytics' ) ); ?></option><option value="matomo_tag_manager"><?php echo esc_html( $this->tx( 'Matomo Tag Manager' ) ); ?></option><option value="hotjar"><?php echo esc_html( $this->tx( 'Hotjar' ) ); ?></option><option value="sourcebuster"><?php echo esc_html( $this->tx( 'Sourcebuster (sbjs)' ) ); ?></option></select><button type="button" class="button button-primary ccwps-btn-primary-action" id="ccwps-apply-cookie-preset"><?php echo esc_html( $this->tx( 'Použiť predvoľbu' ) ); ?></button></div></td></tr>
						<tr><th><label for="c-name"><?php esc_html_e( 'Názov', 'web-pixel-studio-cookie-consent-eu' ); ?> <span class="required">*</span></label></th><td><input type="text" id="c-name" class="regular-text" placeholder="napr. _ga"></td></tr>
						<tr><th><label for="c-domain"><?php esc_html_e( 'Doména', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th><td><input type="text" id="c-domain" class="regular-text" placeholder="<?php echo esc_attr( $this->get_home_url_host() ); ?>"></td></tr>
						<tr><th><label for="c-expiration"><?php esc_html_e( 'Platnosť', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th><td><input type="text" id="c-expiration" class="regular-text" placeholder="napr. 2 roky, Relácia"></td></tr>
						<tr><th><label for="c-path"><?php esc_html_e( 'Cesta', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th><td><input type="text" id="c-path" class="regular-text" value="/"></td></tr>
						<tr><th><label for="c-description"><?php esc_html_e( 'Popis', 'web-pixel-studio-cookie-consent-eu' ); ?></label><p class="desc"><?php esc_html_e( 'Viditeľný návštevníkom v modáli preferencií.', 'web-pixel-studio-cookie-consent-eu' ); ?></p></th><td><textarea id="c-description" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'napr. Používa sa Google Analytics na rozlíšenie návštevníkov.', 'web-pixel-studio-cookie-consent-eu' ); ?>"></textarea></td></tr>
						<tr><th><label for="c-category"><?php esc_html_e( 'Kategória', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th><td><select id="c-category"><?php foreach ( $categories as $cat ) : ?><option value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( ucfirst( $cat ) ); ?></option><?php endforeach; ?></select></td></tr>
						<tr><th><label for="c-is-regex"><?php esc_html_e( 'Je Regex?', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th><td><label class="ccwps-toggle"><input type="checkbox" id="c-is-regex"><span class="ccwps-toggle-slider"></span></label></td></tr>
					</table>
				</div>
				<div class="ccwps-modal-footer"><button type="button" class="button ccwps-modal-close ccwps-btn-secondary-action"><?php echo esc_html( $this->t( 'admin_btn_cancel', 'Zrušiť' ) ); ?></button><button type="button" class="button button-primary ccwps-btn-primary-action" id="ccwps-save-cookie"><?php echo esc_html( $this->t( 'admin_btn_save', 'Uložiť' ) ); ?></button></div>
			</div>
		</div>

		<!-- Request preset modal -->
		<div id="ccwps-request-preset-modal" class="ccwps-modal" style="display:none;">
			<div class="ccwps-modal-inner" style="max-width:680px;">
				<div class="ccwps-modal-header"><h3><?php echo esc_html( $this->tx( 'Žiadosť o pridanie predvoľby' ) ); ?></h3><button type="button" class="ccwps-modal-close">×</button></div>
				<div class="ccwps-modal-body">
					<table class="ccwps-table">
						<tr>
							<th><label for="ccwps-request-email"><?php esc_html_e( 'Email', 'web-pixel-studio-cookie-consent-eu' ); ?> <span class="required">*</span></label></th>
							<td><input type="email" id="ccwps-request-email" class="regular-text" placeholder="name@example.com" maxlength="190"></td>
						</tr>
						<tr>
							<th><label for="ccwps-request-subject"><?php esc_html_e( 'Predmet', 'web-pixel-studio-cookie-consent-eu' ); ?> <span class="required">*</span></label></th>
							<td><input type="text" id="ccwps-request-subject" class="large-text" maxlength="150" placeholder="napr. Nová predvoľba: LinkedIn Insight Tag"></td>
						</tr>
						<tr>
							<th><label for="ccwps-request-message"><?php esc_html_e( 'Text', 'web-pixel-studio-cookie-consent-eu' ); ?> <span class="required">*</span></label></th>
							<td><textarea id="ccwps-request-message" rows="6" class="large-text" maxlength="4000" placeholder="Opíšte, akú predvoľbu potrebujete, cookies alebo script source domény."></textarea></td>
						</tr>
					</table>
					<input type="text" id="ccwps-request-company" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">
					<p class="description" style="margin-top:10px;"><?php echo esc_html( $this->tx( 'Email použijeme iba na vybavenie tejto žiadosti.' ) ); ?></p>
				</div>
				<div class="ccwps-modal-footer"><button type="button" class="button ccwps-modal-close ccwps-btn-secondary-action"><?php echo esc_html( $this->t( 'admin_btn_cancel', 'Zrušiť' ) ); ?></button><button type="button" class="button button-primary ccwps-btn-primary-action" id="ccwps-send-preset-request"><?php echo esc_html( $this->tx( 'Odoslať žiadosť' ) ); ?></button></div>
			</div>
		</div>

		<!-- Cookie list preview modal -->
		<div id="ccwps-cookie-list-modal" class="ccwps-modal" style="display:none;">
			<div class="ccwps-modal-inner" style="width:720px;max-width:95vw;">
				<div class="ccwps-modal-header"><h3><?php esc_html_e( 'Náhľad zoznamu cookies', 'web-pixel-studio-cookie-consent-eu' ); ?></h3><button type="button" class="ccwps-modal-close">×</button></div>
				<div class="ccwps-modal-body" id="ccwps-cookie-list-preview"></div>
			</div>
		</div>
		<?php
	}

	/* ================================================
	   BLOCKING TAB
	   ================================================ */
	private function tab_blocking(): void {
		$rules      = $this->block_manager->get_all();
		$categories = [ 'analytics', 'targeting', 'preferences' ];
		?>
		<div class="ccwps-page-header">
			<h1><?php echo esc_html( $this->t( 'admin_tab_blocking_h1', 'Blokovanie skriptov' ) ); ?></h1>
			<button type="button" class="button button-primary ccwps-btn-primary-action" id="ccwps-add-block">+ <?php echo esc_html( $this->t( 'admin_btn_add_rule', 'Pridať pravidlo' ) ); ?></button>
		</div>
		<div class="ccwps-card">
			<div class="ccwps-info-box"><?php esc_html_e( 'Definujte zdroje skriptov, ktoré sa majú blokovať, kým používateľ neudelí súhlas pre danú kategóriu. Plugin zmení typ skriptu na text/plain, čo zabrání jeho spusteniu.', 'web-pixel-studio-cookie-consent-eu' ); ?></div>
			<div class="ccwps-bulk-actions" id="ccwps-blocks-bulk-actions" style="margin-bottom:12px;display:none;">
				<button type="button" class="button button-link-delete ccwps-btn-danger-action" id="ccwps-delete-blocks-bulk"><?php echo esc_html( $this->tx( '🗑️ Odstrániť vybrané' ) ); ?></button>
				<span class="ccwps-bulk-count" id="ccwps-blocks-bulk-count"></span>
			</div>
			<table class="wp-list-table widefat striped ccwps-data-table">
				<thead><tr><th style="width:40px;"><input type="checkbox" class="ccwps-blocks-select-all" title="<?php echo esc_attr( $this->tx( 'Vybrať všetko / Zrušiť výber' ) ); ?>"></th><th><?php echo esc_html( $this->t( 'admin_col_script_source', 'Zdroj skriptu' ) ); ?></th><th style="width:130px"><?php echo esc_html( $this->t( 'admin_col_category', 'Kategória' ) ); ?></th><th style="width:70px"><?php esc_html_e( 'Regex', 'web-pixel-studio-cookie-consent-eu' ); ?></th><th style="width:120px"><?php echo esc_html( $this->t( 'admin_col_actions', 'Akcie' ) ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $rules ) ) : ?>
					<tr><td colspan="5" style="text-align:center;padding:24px;"><?php esc_html_e( 'Žiadne pravidlá blokovania.', 'web-pixel-studio-cookie-consent-eu' ); ?></td></tr>
				<?php else : foreach ( $rules as $r ) : ?>
					<tr class="ccwps-blocks-row">
						<td style="width:40px;"><input type="checkbox" class="ccwps-block-checkbox" data-id="<?php echo esc_attr( $r['id'] ); ?>"></td>
						<td><code><?php echo esc_html( $r['script_source'] ); ?></code></td>
						<td><span class="ccwps-badge ccwps-badge-<?php echo esc_attr( $r['category'] ); ?>"><?php echo esc_html( ucfirst( $r['category'] ) ); ?></span></td>
						<td><?php echo $r['is_regex'] ? '<span class="ccwps-badge ccwps-badge-regex">Regex</span>' : '—'; ?></td>
						<td><button class="button button-small ccwps-edit-block" data-row='<?php echo esc_attr( wp_json_encode( $r ) ); ?>'><?php echo esc_html( $this->t( 'admin_btn_edit', 'Upraviť' ) ); ?></button> <button class="button button-small button-link-delete ccwps-delete-block" data-id="<?php echo esc_attr( $r['id'] ); ?>"><?php echo esc_html( $this->t( 'admin_btn_delete', 'Zmazať' ) ); ?></button></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<div id="ccwps-block-modal" class="ccwps-modal" style="display:none;">
			<div class="ccwps-modal-inner">
				<div class="ccwps-modal-header"><h3 id="ccwps-block-modal-title"><?php echo esc_html( $this->t( 'admin_btn_add_rule', 'Pridať pravidlo' ) ); ?></h3><button type="button" class="ccwps-modal-close">×</button></div>
				<div class="ccwps-modal-body">
					<input type="hidden" id="ccwps-block-id">
					<table class="ccwps-table">
						<tr><th><label for="b-preset"><?php echo esc_html( $this->tx( 'Predpripravené predvoľby' ) ); ?></label></th><td><div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;"><select id="b-preset" style="min-width:260px;"><option value=""><?php echo esc_html( $this->tx( 'Vyberte predvoľbu' ) ); ?></option><option value="ga"><?php echo esc_html( $this->tx( 'Google Analytics' ) ); ?></option><option value="gtm"><?php echo esc_html( $this->tx( 'Google Tag Manager' ) ); ?></option><option value="g_targeting"><?php echo esc_html( $this->tx( 'Google Targeting' ) ); ?></option><option value="g_preferences"><?php echo esc_html( $this->tx( 'Google Preferences' ) ); ?></option><option value="gads"><?php echo esc_html( $this->tx( 'Google Ads' ) ); ?></option><option value="fb"><?php echo esc_html( $this->tx( 'Facebook Pixel' ) ); ?></option><option value="pys"><?php echo esc_html( $this->tx( 'Pixel Your Site' ) ); ?></option><option value="mtm_analytics"><?php echo esc_html( $this->tx( 'Matomo Analytics' ) ); ?></option><option value="mtm_tag_manager"><?php echo esc_html( $this->tx( 'Matomo Tag Manager' ) ); ?></option><option value="hotjar"><?php echo esc_html( $this->tx( 'Hotjar' ) ); ?></option><option value="sourcebuster"><?php echo esc_html( $this->tx( 'Sourcebuster (sbjs)' ) ); ?></option></select><button type="button" class="button button-primary ccwps-btn-primary-action" id="ccwps-apply-block-preset"><?php echo esc_html( $this->tx( 'Použiť predvoľbu' ) ); ?></button></div></td></tr>
						<tr><th><label for="b-source"><?php esc_html_e( 'Zdroj skriptu', 'web-pixel-studio-cookie-consent-eu' ); ?> <span class="required">*</span></label><p class="desc"><?php esc_html_e( 'Fragment URL, napr. "google-analytics.com"', 'web-pixel-studio-cookie-consent-eu' ); ?></p></th><td><input type="text" id="b-source" class="large-text" placeholder="napr. google-analytics.com"></td></tr>
						<tr><th><label for="b-category"><?php esc_html_e( 'Kategória', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th><td><select id="b-category"><?php foreach ( $categories as $cat ) : ?><option value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( ucfirst( $cat ) ); ?></option><?php endforeach; ?></select></td></tr>
						<tr><th><label for="b-is-regex"><?php esc_html_e( 'Je Regex?', 'web-pixel-studio-cookie-consent-eu' ); ?></label></th><td><label class="ccwps-toggle"><input type="checkbox" id="b-is-regex"><span class="ccwps-toggle-slider"></span></label></td></tr>
					</table>
				</div>
				<div class="ccwps-modal-footer"><button type="button" class="button ccwps-modal-close ccwps-btn-secondary-action"><?php echo esc_html( $this->t( 'admin_btn_cancel', 'Zrušiť' ) ); ?></button><button type="button" class="button button-primary ccwps-btn-primary-action" id="ccwps-save-block"><?php echo esc_html( $this->t( 'admin_btn_save', 'Uložiť' ) ); ?></button></div>
			</div>
		</div>
		<?php
	}

	/* ================================================
	   LOG TAB
	   ================================================ */
	private function tab_log(): void {
		$per_page   = 30;
		$page       = $this->get_query_int( 'log_page', 1 );
		$search_date = sanitize_text_field( $this->get_query_string( 'log_date' ) );
		$search_id   = sanitize_text_field( $this->get_query_string( 'log_id' ) );
		$filters     = array_filter( [ 'date' => $search_date, 'consent_id' => $search_id ] );
		$records     = $this->log->get_all( $per_page, $page, $filters );
		$total       = $this->log->count( $filters );
		$total_all   = empty( $filters ) ? $total : $this->log->count();
		$pages       = (int) ceil( $total / $per_page );

		$pag_extra = '';
		if ( $search_date ) $pag_extra .= '&log_date=' . rawurlencode( $search_date );
		if ( $search_id )   $pag_extra .= '&log_id='   . rawurlencode( $search_id );
		?>
		<div class="ccwps-page-header">
			<h1><?php echo esc_html( $this->t( 'admin_tab_log_h1', 'Záznamy súhlasov' ) ); ?> <span class="ccwps-count">(<?php echo esc_html( number_format( $total_all ) ); ?>)</span></h1>
			<div class="ccwps-header-actions">
				<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=ccwps_export_csv&_wpnonce=' . wp_create_nonce( 'ccwps_export_csv' ) ) ); ?>" class="button ccwps-btn-secondary-action"><?php echo esc_html( $this->t( 'admin_btn_export_csv', 'Exportovať CSV' ) ); ?></a>
				<button type="button" class="button button-link-delete ccwps-btn-danger-action" id="ccwps-clear-log"><?php echo esc_html( $this->t( 'admin_btn_clear_log', 'Vymazať všetky záznamy' ) ); ?></button>
			</div>
		</div>
		<div class="ccwps-card">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ccwps-log-search">
				<input type="hidden" name="page" value="ccwps">
				<input type="hidden" name="tab" value="log">
				<input type="date" name="log_date" value="<?php echo esc_attr( $search_date ); ?>" class="ccwps-log-search-date">
				<input type="text" name="log_id" value="<?php echo esc_attr( $search_id ); ?>" placeholder="<?php esc_attr_e( 'ID súhlasu…', 'web-pixel-studio-cookie-consent-eu' ); ?>" class="ccwps-log-search-id regular-text">
				<button type="submit" class="button button-primary"><?php echo esc_html( $this->tx( 'Hľadať' ) ); ?></button>
				<?php if ( ! empty( $filters ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ccwps&tab=log' ) ); ?>" class="button"><?php echo esc_html( $this->tx( 'Zrušiť filter' ) ); ?></a>
					<span class="ccwps-log-search-results"><?php printf( esc_html( $this->tx( 'Nájdené: %d záznamov' ) ), $total ); ?></span>
				<?php endif; ?>
			</form>
			<?php if ( empty( $records ) ) : ?>
				<p style="padding:16px;"><?php echo esc_html( empty( $filters ) ? $this->tx( 'Žiadne záznamy súhlasov.' ) : $this->tx( 'Žiadne záznamy nezodpovedajú vyhľadávaniu.' ) ); ?></p>
			<?php else : ?>
				<div class="ccwps-table-scroll">
					<table class="wp-list-table widefat striped ccwps-data-table ccwps-log-table">
						<thead><tr>
							<th><?php echo esc_html( $this->t( 'admin_col_date', 'Dátum' ) ); ?></th>
							<th><?php echo esc_html( $this->t( 'admin_col_consent_id', 'ID súhlasu' ) ); ?></th>
							<th>URL</th>
							<th><?php echo esc_html( $this->t( 'admin_col_ip', 'IP adresa' ) ); ?></th>
							<th><?php echo esc_html( $this->tx( 'Zariadenie' ) ); ?></th>
							<th><?php esc_html_e( 'Nev.', 'web-pixel-studio-cookie-consent-eu' ); ?></th>
							<th><?php esc_html_e( 'Anal.', 'web-pixel-studio-cookie-consent-eu' ); ?></th>
							<th><?php esc_html_e( 'Market.', 'web-pixel-studio-cookie-consent-eu' ); ?></th>
							<th><?php esc_html_e( 'Pref.', 'web-pixel-studio-cookie-consent-eu' ); ?></th>
							<th><?php echo esc_html( $this->t( 'admin_col_updated', 'Aktualizácia' ) ); ?></th>
						</tr></thead>
						<tbody>
							<?php foreach ( $records as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['recorded_at'] ); ?></td>
								<td><code style="font-size:10px;"><?php echo esc_html( $row['consent_id'] ); ?></code></td>
								<td class="ccwps-cell-url" title="<?php echo esc_attr( $row['url'] ); ?>"><?php echo esc_html( $row['url'] ); ?></td>
								<td><?php echo esc_html( $this->anonymize_ip( $row['ip_address'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( $row['device_info'] ?? '—' ); ?></td>
								<td><?php echo $row['necessary']   ? '<span class="ccwps-dot green"></span>' : '<span class="ccwps-dot red"></span>'; ?></td>
								<td><?php echo $row['analytics']   ? '<span class="ccwps-dot green"></span>' : '<span class="ccwps-dot red"></span>'; ?></td>
								<td><?php echo $row['targeting']   ? '<span class="ccwps-dot green"></span>' : '<span class="ccwps-dot red"></span>'; ?></td>
								<td><?php echo $row['preferences'] ? '<span class="ccwps-dot green"></span>' : '<span class="ccwps-dot red"></span>'; ?></td>
								<td><?php echo esc_html( $row['updated_at'] ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php if ( $pages > 1 ) :
					$pag_base = admin_url( 'admin.php?page=ccwps&tab=log' . $pag_extra . '&log_page=' );
					$pag_w    = 5;
					$pag_half = (int) floor( $pag_w / 2 );
					$pag_s    = max( 1, $page - $pag_half );
					$pag_e    = min( $pages, $pag_s + $pag_w - 1 );
					if ( $pag_e - $pag_s + 1 < $pag_w ) {
						$pag_s = max( 1, $pag_e - $pag_w + 1 );
					}
				?>
				<div class="ccwps-pagination">
					<?php if ( $page > 1 ) : ?>
						<a href="<?php echo esc_url( $pag_base . '1' ); ?>" class="ccwps-pag-nav" title="<?php esc_attr_e( 'Prvá stránka', 'web-pixel-studio-cookie-consent-eu' ); ?>">|&#171;</a>
						<a href="<?php echo esc_url( $pag_base . ( $page - 1 ) ); ?>" class="ccwps-pag-nav" title="<?php esc_attr_e( 'Predošlá', 'web-pixel-studio-cookie-consent-eu' ); ?>">&#8249;</a>
					<?php else : ?>
						<span class="ccwps-pag-nav ccwps-pag-disabled">|&#171;</span>
						<span class="ccwps-pag-nav ccwps-pag-disabled">&#8249;</span>
					<?php endif; ?>

					<?php for ( $i = $pag_s; $i <= $pag_e; $i++ ) : ?>
						<?php if ( $i === $page ) : ?>
							<span class="ccwps-pag-page ccwps-pag-current"><?php echo esc_html( $i ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( $pag_base . $i ); ?>" class="ccwps-pag-page"><?php echo esc_html( $i ); ?></a>
						<?php endif; ?>
					<?php endfor; ?>

					<?php if ( $pag_e < $pages ) : ?>
						<?php if ( $pag_e < $pages - 1 ) : ?>
							<span class="ccwps-pag-ellipsis">…</span>
						<?php endif; ?>
						<a href="<?php echo esc_url( $pag_base . $pages ); ?>" class="ccwps-pag-page"><?php echo esc_html( $pages ); ?></a>
					<?php endif; ?>

					<?php if ( $page < $pages ) : ?>
						<a href="<?php echo esc_url( $pag_base . ( $page + 1 ) ); ?>" class="ccwps-pag-nav" title="<?php esc_attr_e( 'Nasledujúca', 'web-pixel-studio-cookie-consent-eu' ); ?>">&#8250;</a>
						<a href="<?php echo esc_url( $pag_base . $pages ); ?>" class="ccwps-pag-nav" title="<?php esc_attr_e( 'Posledná stránka', 'web-pixel-studio-cookie-consent-eu' ); ?>">&#187;|</a>
					<?php else : ?>
						<span class="ccwps-pag-nav ccwps-pag-disabled">&#8250;</span>
						<span class="ccwps-pag-nav ccwps-pag-disabled">&#187;|</span>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ================================================
	   SHORTCODES TAB
	   ================================================ */
	private function tab_shortcodes(): void {
		?>
		<div class="ccwps-page-header"><h1><?php esc_html_e( 'Shortcodes', 'web-pixel-studio-cookie-consent-eu' ); ?></h1></div>
		<div class="ccwps-card">
			<h2><?php esc_html_e( 'ID súhlasu návštevníka', 'web-pixel-studio-cookie-consent-eu' ); ?></h2>
			<p><?php esc_html_e( 'Zobrazí jedinečné ID súhlasu aktuálneho návštevníka. Vhodné pre zásady ochrany osobných údajov – umožní používateľovi preukázať, kedy a ako súhlasil.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
			<div class="ccwps-shortcode-box"><code>[ccwps_consent_id]</code><button class="button button-small ccwps-copy-btn" data-copy="[ccwps_consent_id]"><?php echo esc_html( $this->t( 'admin_btn_copy', 'Kopírovať' ) ); ?></button></div>
			<table class="ccwps-sc-params"><thead><tr><th><?php esc_html_e( 'Parameter', 'web-pixel-studio-cookie-consent-eu' ); ?></th><th><?php esc_html_e( 'Predvolené', 'web-pixel-studio-cookie-consent-eu' ); ?></th><th><?php esc_html_e( 'Popis', 'web-pixel-studio-cookie-consent-eu' ); ?></th></tr></thead>
			<tbody>
				<tr><td><code>label</code></td><td><code><?php echo esc_html( $this->tx( (string) $this->settings->get( 'lang_consent_id_label', 'ID vášho súhlasu' ) ) ); ?></code></td><td><?php esc_html_e( 'Text pred ID.', 'web-pixel-studio-cookie-consent-eu' ); ?></td></tr>
				<tr><td><code>wrapper</code></td><td><code>p</code></td><td><?php esc_html_e( 'HTML obal (p, div, span…)', 'web-pixel-studio-cookie-consent-eu' ); ?></td></tr>
			</tbody></table>
		</div>
		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Zoznam cookies používaných na webe', 'web-pixel-studio-cookie-consent-eu' ); ?></h2>
			<p><?php esc_html_e( 'Automaticky zobrazí prehľadnú tabuľku všetkých deklarovaných cookies, rozdelenú podľa kategórií. Vložte na stránku s cookie politikou.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
			<div class="ccwps-shortcode-box"><code>[ccwps_cookie_list]</code><button class="button button-small ccwps-copy-btn" data-copy="[ccwps_cookie_list]"><?php echo esc_html( $this->t( 'admin_btn_copy', 'Kopírovať' ) ); ?></button></div>
			<table class="ccwps-sc-params"><thead><tr><th><?php esc_html_e( 'Parameter', 'web-pixel-studio-cookie-consent-eu' ); ?></th><th><?php esc_html_e( 'Predvolené', 'web-pixel-studio-cookie-consent-eu' ); ?></th><th><?php esc_html_e( 'Popis', 'web-pixel-studio-cookie-consent-eu' ); ?></th></tr></thead>
			<tbody>
				<tr><td><code>category</code></td><td><code>(všetky)</code></td><td><?php esc_html_e( 'Filtruj: necessary, analytics, targeting, preferences', 'web-pixel-studio-cookie-consent-eu' ); ?></td></tr>
			</tbody></table>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Tlačidlo správy súhlasu', 'web-pixel-studio-cookie-consent-eu' ); ?></h2>
			<p><?php esc_html_e( 'Vloží tlačidlo, ktoré otvorí modál nastavenia cookies. Vhodné pre stránky so zásadami ochrany osobných údajov a právne dokumenty – umožní návštevníkovi kedykoľvek zmeniť preferencie.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
			<div class="ccwps-shortcode-box"><code>[ccwps_manage_consent]</code><button class="button button-small ccwps-copy-btn" data-copy="[ccwps_manage_consent]"><?php echo esc_html( $this->t( 'admin_btn_copy', 'Kopírovať' ) ); ?></button></div>
			<table class="ccwps-sc-params"><thead><tr><th><?php esc_html_e( 'Parameter', 'web-pixel-studio-cookie-consent-eu' ); ?></th><th><?php esc_html_e( 'Predvolené', 'web-pixel-studio-cookie-consent-eu' ); ?></th><th><?php esc_html_e( 'Popis', 'web-pixel-studio-cookie-consent-eu' ); ?></th></tr></thead>
			<tbody>
				<tr><td><code>label</code></td><td><code><?php echo esc_html( $this->tx( (string) $this->settings->get( 'lang_manage_preferences', 'Customize' ) ) ); ?></code></td><td><?php esc_html_e( 'Text tlačidla (predvolene: text z nastavení Prekladov).', 'web-pixel-studio-cookie-consent-eu' ); ?></td></tr>
				<tr><td><code>class</code></td><td><code>ccwps-manage-consent-btn</code></td><td><?php esc_html_e( 'CSS trieda pre vlastné štýlovanie.', 'web-pixel-studio-cookie-consent-eu' ); ?></td></tr>
				<tr><td><code>id</code></td><td><code>(prázdne)</code></td><td><?php esc_html_e( 'Voliteľné HTML id atribút.', 'web-pixel-studio-cookie-consent-eu' ); ?></td></tr>
			</tbody></table>
		</div>
		<?php
	}

	/* ================================================
	   TOOLS TAB
	   ================================================ */
	private function tab_tools(): void {
		$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=ccwps_export_settings' ), 'ccwps_export_settings' );
		?>
		<div class="ccwps-page-header"><h1><?php echo esc_html( $this->t( 'admin_tab_tools_h1', 'Nástroje' ) ); ?></h1></div>
		<div class="ccwps-tools-grid">
			<div class="ccwps-card">
				<h2><?php echo esc_html( $this->t( 'admin_sect_export', 'Export nastavení' ) ); ?></h2>
				<p class="description"><?php esc_html_e( 'Exportuje všetky nastavenia do JSON súboru. Vhodné pre presun medzi servermi alebo zálohu.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
				<div class="ccwps-tool-action"><a href="<?php echo esc_url( $export_url ); ?>" class="button button-primary">⬇ <?php echo esc_html( $this->t( 'admin_btn_export_json', 'Stiahnuť settings.json' ) ); ?></a></div>
			</div>
			<div class="ccwps-card">
				<h2><?php echo esc_html( $this->t( 'admin_sect_import', 'Import nastavení' ) ); ?></h2>
				<p class="description"><?php esc_html_e( 'Nahrajte predtým exportovaný settings.json. Prepisuje aktuálnu konfiguráciu.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'ccwps_import_settings', '_wpnonce_import' ); ?>
					<input type="hidden" name="action" value="ccwps_import_settings">
					<div class="ccwps-file-drop" id="ccwps-file-drop">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
						<span id="ccwps-file-name"><?php esc_html_e( 'Kliknite alebo presuňte settings.json sem', 'web-pixel-studio-cookie-consent-eu' ); ?></span>
						<input type="file" id="ccwps-import-file" name="ccwps_import_file" accept=".json,application/json">
					</div>
					<div class="ccwps-tool-action"><button type="submit" class="button button-primary" id="ccwps-import-btn" disabled>⬆ <?php echo esc_html( $this->t( 'admin_btn_import_json', 'Importovať nastavenia' ) ); ?></button></div>
				</form>
			</div>
			<div class="ccwps-card">
				<h2 style="color:#dc2626;"><?php echo esc_html( $this->t( 'admin_sect_reset', 'Reset na predvolené' ) ); ?></h2>
				<p class="description"><?php esc_html_e( 'Resetuje všetky nastavenia na predvolené hodnoty (SK jazyk, predvolený vzhľad). Deklarácie cookies, pravidlá blokovania a záznamy súhlasov nie sú ovplyvnené.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
				<div class="ccwps-tool-action"><button type="button" class="button" id="ccwps-reset-defaults" style="border-color:#dc2626;color:#dc2626;">↺ <?php echo esc_html( $this->t( 'admin_btn_reset', 'Resetovať nastavenia' ) ); ?></button></div>
			</div>
			<div class="ccwps-card">
				<h2><?php echo esc_html( $this->t( 'admin_sect_plugin_info', 'Informácie o plugine' ) ); ?></h2>
				<table class="ccwps-table">
					<tr><th><?php esc_html_e( 'Verzia', 'web-pixel-studio-cookie-consent-eu' ); ?></th><td><strong><?php echo esc_html( CCWPS_VERSION ); ?></strong></td></tr>
					<tr><th>WordPress</th><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
					<tr><th>PHP</th><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Záznamy súhlasov', 'web-pixel-studio-cookie-consent-eu' ); ?></th><td><?php echo esc_html( number_format( $this->log->count() ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Deklarácie cookies', 'web-pixel-studio-cookie-consent-eu' ); ?></th><td><?php echo esc_html( count( $this->cookie_manager->get_all() ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Pravidlá blokovania', 'web-pixel-studio-cookie-consent-eu' ); ?></th><td><?php echo esc_html( count( $this->block_manager->get_all() ) ); ?></td></tr>
				</table>
			</div>
		</div>
		<?php
	}

	/* ================================================
	   GTM TEMPLATE TAB
	   ================================================ */
	private function tab_gtm_template(): void {
		$v2_url       = CCWPS_PLUGIN_URL . 'gtm-template/web-pixel-studio-cookie-consent-eu.tpl';
		$v3_url       = CCWPS_PLUGIN_URL . 'gtm-template/web-pixel-studio-cookie-consent-eu-v3.tpl';
		$settings_url = admin_url( 'admin.php?page=ccwps&tab=settings' );
		$screenshots  = $this->get_gtm_screenshot_urls();
		?>
		<div class="ccwps-page-header"><h1><?php echo esc_html( $this->tx( 'GTM šablóna' ) ); ?></h1></div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->tx( 'Import šablóny do Google Tag Managera' ) ); ?></h2>
			<p><?php echo esc_html( $this->tx( 'V tejto záložke si môžete stiahnuť pripravenú GTM šablónu pre Consent Mode a importovať ju do svojho kontajnera.' ) ); ?></p>
			<p class="description"><?php echo esc_html( $this->tx( 'Ak ešte nemáte dokončené základné nastavenia pluginu, najprv skontrolujte Consent Mode verziu a GTM Container ID v záložke Nastavenia.' ) ); ?> <a href="<?php echo esc_url( $settings_url ); ?>"><?php echo esc_html( $this->tx( 'Otvoriť nastavenia pluginu' ) ); ?></a>.</p>
		</div>

		<div class="ccwps-tools-grid ccwps-gtm-downloads">
			<div class="ccwps-card ccwps-gtm-card">
				<div class="ccwps-gtm-badge"><?php echo esc_html( $this->tx( 'Odporúčané pre väčšinu webov' ) ); ?></div>
				<h2><?php echo esc_html( $this->tx( 'Consent Mode v2' ) ); ?></h2>
				<p><?php echo esc_html( $this->tx( 'Šablóna pre štandardný Consent Mode v2. Obsahuje consent signály required pre Google Ads a GA4 v EÚ.' ) ); ?></p>
				<div class="ccwps-tool-action">
					<a href="<?php echo esc_url( $v2_url ); ?>" class="button button-primary ccwps-btn-primary-action" download>⬇ <?php echo esc_html( $this->tx( 'Stiahnuť šablónu v2' ) ); ?></a>
				</div>
			</div>
			<div class="ccwps-card ccwps-gtm-card">
				<div class="ccwps-gtm-badge ccwps-gtm-badge-alt"><?php echo esc_html( $this->tx( 'Rozšírená verzia' ) ); ?></div>
				<h2><?php echo esc_html( $this->tx( 'Consent Mode v3' ) ); ?></h2>
				<p><?php echo esc_html( $this->tx( 'Rozšírená šablóna s podporou ads_data_redaction, url_passthrough a developer_id pre pokročilé Google Marketing Platform scenáre.' ) ); ?></p>
				<div class="ccwps-tool-action">
					<a href="<?php echo esc_url( $v3_url ); ?>" class="button ccwps-btn-secondary-action" download>⬇ <?php echo esc_html( $this->tx( 'Stiahnuť šablónu v3' ) ); ?></a>
				</div>
			</div>
		</div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->tx( 'Postup importu' ) ); ?></h2>
			<ol class="ccwps-gtm-steps">
				<li><?php echo esc_html( $this->tx( 'Stiahnite si požadovanú verziu šablóny podľa toho, či používate Consent Mode v2 alebo v3.' ) ); ?></li>
				<li><?php echo esc_html( $this->tx( 'V Google Tag Manageri otvorte Templates a v sekcii Tag Templates kliknite na New.' ) ); ?></li>
				<li><?php echo esc_html( $this->tx( 'V menu vpravo hore zvoľte Import a vyberte stiahnutý .tpl súbor.' ) ); ?></li>
				<li><?php echo esc_html( $this->tx( 'Po importe vytvorte nový tag z danej šablóny a doplňte polia podľa vašej implementácie.' ) ); ?></li>
				<li><?php echo esc_html( $this->tx( 'Použite Preview režim, skontrolujte consent signály a potom publikujte kontajner.' ) ); ?></li>
			</ol>
		</div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->tx( 'Dôležité odporúčania po importe' ) ); ?></h2>
			<ul class="ccwps-gtm-checklist">
				<li><?php echo esc_html( $this->tx( 'V plugine ponechajte rovnakú verziu Consent Mode, akú ste importovali do GTM.' ) ); ?></li>
				<li><?php echo esc_html( $this->tx( 'Ak načítavate GTM cez inú tému alebo plugin, pole GTM Container ID nechajte prázdne.' ) ); ?></li>
				<li><?php echo esc_html( $this->tx( 'Pre Google Analytics skontrolujte analytics_storage, pre Google Ads najmä ad_storage, ad_user_data a ad_personalization.' ) ); ?></li>
				<li><?php echo esc_html( $this->tx( 'Overte správanie cez Google Tag Assistant a potvrďte, že sa tagy bez súhlasu nespúšťajú.' ) ); ?></li>
			</ul>
			<div class="ccwps-info-box ccwps-gtm-note">
				<strong><?php echo esc_html( $this->tx( 'Tip:' ) ); ?></strong>
				<?php echo esc_html( $this->tx( 'Ak potrebujete len štandardnú implementáciu pre Google Ads a GA4, vo väčšine prípadov vám bude stačiť šablóna v2.' ) ); ?>
			</div>
		</div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->tx( 'Screenshoty postupu' ) ); ?></h2>
			<p><?php echo esc_html( $this->tx( 'Náhľady obrazovky znázorňujú postup importu GTM šablóny a inštaláciu značky v Google Tag Manager.' ) ); ?></p>
			<?php if ( ! empty( $screenshots ) ) : ?>
				<div class="ccwps-gtm-gallery">
					<?php foreach ( $screenshots as $index => $screenshot_url ) : ?>
						<?php $step_label = sprintf( $this->tx( 'Krok %d' ), $index + 1 ); ?>
						<figure class="ccwps-gtm-shot">
							<button
								type="button"
								class="ccwps-gtm-shot-trigger"
								data-ccwps-lightbox-image="<?php echo esc_url( $screenshot_url ); ?>"
								data-ccwps-lightbox-caption="<?php echo esc_attr( $step_label ); ?>"
							>
								<img src="<?php echo esc_url( $screenshot_url ); ?>" alt="<?php echo esc_attr( $step_label ); ?>">
							</button>
							<figcaption><?php echo esc_html( $step_label ); ?></figcaption>
						</figure>
					<?php endforeach; ?>
				</div>
				<div class="ccwps-gtm-lightbox" id="ccwps-gtm-lightbox" hidden>
					<div class="ccwps-gtm-lightbox-backdrop" data-ccwps-lightbox-close></div>
					<div class="ccwps-gtm-lightbox-dialog" role="dialog" aria-modal="true" aria-labelledby="ccwps-gtm-lightbox-caption">
						<button type="button" class="ccwps-gtm-lightbox-close" data-ccwps-lightbox-close aria-label="Close preview">&times;</button>
						<img src="" alt="" class="ccwps-gtm-lightbox-image" id="ccwps-gtm-lightbox-image">
						<div class="ccwps-gtm-lightbox-caption" id="ccwps-gtm-lightbox-caption"></div>
					</div>
				</div>
			<?php else : ?>
				<div class="ccwps-info-box ccwps-gtm-note">
					<strong><?php echo esc_html( $this->tx( 'Screenshoty neboli nájdené.' ) ); ?></strong>
					<?php echo esc_html( $this->tx( 'Pridajte obrázky do admin/images a pomenujte ich napríklad template-screenshot-1.webp, template-screenshot-2.webp.' ) ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function get_gtm_screenshot_urls(): array {
		$files = glob( CCWPS_PLUGIN_DIR . 'admin/images/template-screenshot-*.*' );
		if ( false === $files || empty( $files ) ) {
			return [];
		}

		natsort( $files );

		$urls = [];
		foreach ( $files as $file ) {
			$urls[] = CCWPS_PLUGIN_URL . 'admin/images/' . basename( $file );
		}

		return $urls;
	}

	/* ================================================
	   ABOUT TAB
	   ================================================ */
	private function tab_about(): void {
		?>
		<div class="ccwps-page-header"><h1><?php esc_html_e( 'O plugine Web Pixel Studio Cookie Consent for EU', 'web-pixel-studio-cookie-consent-eu' ); ?></h1></div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Popis pluginu', 'web-pixel-studio-cookie-consent-eu' ); ?></h2>
			<p><?php echo esc_html( $this->tx( 'Web Pixel Studio Cookie Consent for EU je bezplatný WordPress plugin pre správu súhlasov s cookies (GDPR / ePrivacy). Poskytuje plne prispôsobiteľný banner a modál preferencií, zaznamenáva súhlasy pre GDPR audit, blokuje skripty tretích strán, podporuje Google Consent Mode v2 a v3 a obsahuje Matomo režim so súhlasom.' ) ); ?></p>
			<p style="margin-top:10px;"><?php echo esc_html( $this->tx( 'Plugin je postavený na knižnici orest bida (cookieconsent) a rozširuje ju o WordPress admin panel, databázové záznamy súhlasov, blokovanie skriptov, právne odkazy v banneri a preferenciách, pokročilé vizuálne nastavenia medzier a spoľahlivý import/export medzi webmi.' ) ); ?></p>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Hlavné funkcie', 'web-pixel-studio-cookie-consent-eu' ); ?></h2>
			<table class="ccwps-table">
				<?php
				$features = [
					[ '🍪', __( 'Cookie banner', 'web-pixel-studio-cookie-consent-eu' ),            __( 'Prispôsobiteľný popup/bar/cloud banner s výberom polohy na stránke.', 'web-pixel-studio-cookie-consent-eu' ) ],
					[ '⚙️', __( 'Modál preferencií', 'web-pixel-studio-cookie-consent-eu' ),         __( 'Používateľ si môže zvoliť, ktoré kategórie cookies povolí (nevyhnutné, analytické, marketingové, preferenčné).', 'web-pixel-studio-cookie-consent-eu' ) ],
					[ '📋', __( 'Záznamy súhlasov', 'web-pixel-studio-cookie-consent-eu' ),          __( 'Každý súhlas sa uloží do databázy s ID, IP adresou, URL a časovou pečiatkou. Export do CSV.', 'web-pixel-studio-cookie-consent-eu' ) ],
					[ '🚫', __( 'Blokovanie skriptov', 'web-pixel-studio-cookie-consent-eu' ),       __( 'Skripty tretích strán (napr. Google Analytics, Meta Pixel) sa zablokujú, kým používateľ neudelí súhlas.', 'web-pixel-studio-cookie-consent-eu' ) ],
					[ '🧩', __( 'Predpripravené sady cookies a skriptov', 'web-pixel-studio-cookie-consent-eu' ), __( 'Hotové predvoľby jedným kliknutím doplnia bežné Google a Meta cookies spolu so súvisiacimi pravidlami blokovania.', 'web-pixel-studio-cookie-consent-eu' ) ],
					[ '🔗', $this->tx( 'Právne odkazy v banneri a modáli' ), $this->tx( 'Voliteľné odkazy na Zásady používania cookies a Zásady ochrany osobných údajov s vlastným textom a URL adresou.' ) ],
					[ '📐', $this->tx( 'Pokročilé vizuálne medzery' ), $this->tx( 'Samostatné padding/margin nastavenia pre texty, právne odkazy a každé tlačidlo v bannery aj v modáli vrátane rýchleho resetu iba pre medzery.' ) ],
					[ '🌐', __( 'Consent Mode v2 / v3', 'web-pixel-studio-cookie-consent-eu' ),      __( 'Automaticky nastaví default denied stav a aktualizuje Google signály po udelení súhlasu.', 'web-pixel-studio-cookie-consent-eu' ) ],
					[ '🎨', __( 'Plné prispôsobenie vzhľadu', 'web-pixel-studio-cookie-consent-eu' ),__( 'Farby, font, zaoblenie tlačidiel, rozloženie bannera, poloha floating ikony.', 'web-pixel-studio-cookie-consent-eu' ) ],
					[ '🌍', __( '9 jazykových predvolieb', 'web-pixel-studio-cookie-consent-eu' ),   __( 'SK, EN, CS, DE, FR, ES, PL, HU, IT. Všetky texty sú plne editovateľné z admin panelu.', 'web-pixel-studio-cookie-consent-eu' ) ],
					[ '🔗', __( 'Shortcodes', 'web-pixel-studio-cookie-consent-eu' ),                __( '[ccwps_consent_id] – zobrazí ID súhlasu návštevníka. [ccwps_cookie_list] – tabuľka deklarovaných cookies. [ccwps_manage_consent] – tlačidlo na správu súhlasu.', 'web-pixel-studio-cookie-consent-eu' ) ],
					[ '💾', __( 'Export/Import nastavení', 'web-pixel-studio-cookie-consent-eu' ),   $this->tx( 'Nastavenia je možné exportovať do JSON a importovať na inom webe vrátane mapovania domén cookies na aktuálnu doménu webu.' ) ],
					[ '🤖', __( 'Detekcia robotov', 'web-pixel-studio-cookie-consent-eu' ),          __( 'Banner sa automaticky skryje pred vyhľadávačmi a crawlermi.', 'web-pixel-studio-cookie-consent-eu' ) ],
					[ '🔄', __( 'Opätovný súhlas', 'web-pixel-studio-cookie-consent-eu' ),           __( 'Ak sa zmení zoznam cookies, plugin automaticky požiada o nový súhlas.', 'web-pixel-studio-cookie-consent-eu' ) ],
					[ '🛡', __( 'Floating ikona', 'web-pixel-studio-cookie-consent-eu' ),             __( 'Plávajúca ikona umožní používateľovi kedykoľvek zmeniť preferencie. Po kliknutí zobrazí ID súhlasu a dátum.', 'web-pixel-studio-cookie-consent-eu' ) ],
				];
				foreach ( $features as [ $icon, $title, $desc ] ) :
				?>
				<tr>
					<th style="width:200px;"><?php echo esc_html( $icon . ' ' . $title ); ?></th>
					<td><?php echo esc_html( $desc ); ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Postup nastavenia', 'web-pixel-studio-cookie-consent-eu' ); ?></h2>
			<?php
			$steps = [
				[ '1', __( 'Nastavenia', 'web-pixel-studio-cookie-consent-eu' ), __( 'V záložke Nastavenia zapnite Zaznamenávať súhlasy, Opätovný súhlas, Skryť pre robotov a Automatické spustenie. Vyberte verziu Consent Mode (v2 pre väčšinu webov). Ak používate GTM, vyplňte GTM Container ID.', 'web-pixel-studio-cookie-consent-eu' ) ],
				[ '2', __( 'Preklady', 'web-pixel-studio-cookie-consent-eu' ), __( 'V záložke Preklady vyberte jazyk pomocou rýchlych predvolieb alebo upravte texty manuálne. Kliknite Uložiť preklady.', 'web-pixel-studio-cookie-consent-eu' ) ],
				[ '3', __( 'Vzhľad', 'web-pixel-studio-cookie-consent-eu' ), __( 'V záložke Vzhľad nastavte farby, rozloženie bannera a polohu plávajúcej ikony. Použite tlačidlá Náhľad na overenie zmien.', 'web-pixel-studio-cookie-consent-eu' ) ],
				[ '4', __( 'Deklarácia cookies', 'web-pixel-studio-cookie-consent-eu' ), __( 'V záložke Cookies pridajte všetky cookies, ktoré váš web používa. Pre každú cookie vyplňte názov, doménu, platnosť, popis a kategóriu. Popis bude viditeľný návštevníkovi.', 'web-pixel-studio-cookie-consent-eu' ) ],
				[ '5', __( 'Blokovanie skriptov', 'web-pixel-studio-cookie-consent-eu' ), __( 'V záložke Blokovanie skriptov pridajte pravidlá pre skripty, ktoré chcete blokovať. Napr. pre Google Analytics pridajte "google-analytics.com" → kategória Analytické.', 'web-pixel-studio-cookie-consent-eu' ) ],
				[ '6', __( 'Cookie politika', 'web-pixel-studio-cookie-consent-eu' ), __( 'Na stránku s Cookie politikou vložte [ccwps_cookie_list] pre automatickú tabuľku cookies, [ccwps_consent_id] pre zobrazenie ID súhlasu a [ccwps_manage_consent] pre tlačidlo zmeny preferencií.', 'web-pixel-studio-cookie-consent-eu' ) ],
				[ '7', __( 'Overenie', 'web-pixel-studio-cookie-consent-eu' ), __( 'Otvorte web v incognito okne a overte, že sa banner zobrazí, súhlas sa zaznamená v záložke Záznamy súhlasov a floating ikona funguje správne.', 'web-pixel-studio-cookie-consent-eu' ) ],
			];
			foreach ( $steps as [ $num, $title, $desc ] ) :
			?>
			<div class="ccwps-step">
				<div class="ccwps-step-num"><?php echo esc_html( $num ); ?></div>
				<div class="ccwps-step-content">
					<strong><?php echo esc_html( $title ); ?></strong>
					<p><?php echo esc_html( $desc ); ?></p>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Nastavenie Google Tag Manager + Consent Mode', 'web-pixel-studio-cookie-consent-eu' ); ?></h2>
			<div class="ccwps-about-steps">
				<div class="ccwps-about-step">
					<h4><?php esc_html_e( 'Možnosť A: Plugin načíta GTM automaticky', 'web-pixel-studio-cookie-consent-eu' ); ?></h4>
					<p><?php esc_html_e( 'V záložke Nastavenia vyplňte GTM Container ID (napr. GTM-XXXXXXX). Plugin automaticky vloží GTM kód do hlavičky stránky spolu s Consent Mode default stavom (všetky signály = denied). Po udelení súhlasu sa GTM aktualizuje pomocou gtag(\'consent\', \'update\', ...).', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
				</div>
				<div class="ccwps-about-step">
					<h4><?php esc_html_e( 'Možnosť B: GTM máte nainštalovaný inak (téma, iný plugin)', 'web-pixel-studio-cookie-consent-eu' ); ?></h4>
					<p><?php esc_html_e( 'Nechajte GTM Container ID prázdne. Plugin automaticky vloží Consent Mode default stav pred GTM kód, takže default denied signály sa nastavia skôr, ako GTM spustí akékoľvek tagy. Toto je správne správanie podľa Google dokumentácie.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
				</div>
				<div class="ccwps-about-step">
					<h4><?php esc_html_e( 'Nastavenie tagov v GTM', 'web-pixel-studio-cookie-consent-eu' ); ?></h4>
					<p><?php esc_html_e( 'V GTM nastavte pre každý tag Consent Settings. Pre Google Analytics (GA4): analytics_storage = Required. Pre Google Ads: ad_storage, ad_user_data, ad_personalization = Required. Tagy sa spustia len po udelení príslušného súhlasu.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
				</div>
				<div class="ccwps-about-step">
					<h4><?php esc_html_e( 'Overenie pomocou Google Tag Assistant', 'web-pixel-studio-cookie-consent-eu' ); ?></h4>
					<p><?php esc_html_e( 'Otvorte Google Tag Assistant a overte, že pri odmietnutí cookies sú tagy v stave "Consent Blocked" a po udelení súhlasu sa aktivujú. Skontrolujte tiež Google Analytics Realtime report.', 'web-pixel-studio-cookie-consent-eu' ); ?></p>
				</div>
			</div>
		</div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->tx( 'Matomo integrácia a režimy súhlasu' ) ); ?></h2>
			<p><?php echo esc_html( $this->tx( 'Plugin podporuje aj Matomo Analytics vrátane režimu bez cookies. V Nastaveniach môžete zvoliť, či sa má pri odmietnutí analytických cookies meranie úplne vypnúť, alebo bežať v anonymnom režime bez ukladania cookies.' ) ); ?></p>
			<div class="ccwps-about-steps" style="margin-top:14px;">
				<div class="ccwps-about-step">
					<h4><?php echo esc_html( $this->tx( 'Režim 1: Bez merania po odmietnutí (predvolené)' ) ); ?></h4>
					<p><?php echo esc_html( $this->tx( 'Ak je táto voľba aktívna, Matomo sa nespustí, kým návštevník neudelí analytický súhlas. Po neskoršom udelení súhlasu sa automaticky aktivuje plné meranie.' ) ); ?></p>
				</div>
				<div class="ccwps-about-step">
					<h4><?php echo esc_html( $this->tx( 'Režim 2: Anonymné meranie bez cookies po odmietnutí' ) ); ?></h4>
					<p><?php echo esc_html( $this->tx( 'Ak túto voľbu zapnete, Matomo po odmietnutí stále zbiera základné štatistiky bez cookies. Po udelení analytického súhlasu sa prepne na plné meranie s cookies.' ) ); ?></p>
				</div>
			</div>
			<div class="ccwps-info-box" style="margin-top:14px;">
				<strong><?php echo esc_html( $this->tx( 'Odporúčanie pre nasadenie' ) ); ?></strong>
				<?php echo ' ' . esc_html( $this->tx( 'Ak si nie ste istí právnym základom, ponechajte predvolený režim bez merania po odmietnutí. Anonymný režim zapnite iba v prípade, že je to v súlade s vašimi právnymi požiadavkami.' ) ); ?>
			</div>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Autor a podpora', 'web-pixel-studio-cookie-consent-eu' ); ?></h2>
			<p><?php esc_html_e( 'Plugin vytvoril Web Pixel Studio. Pre otázky, návrhy alebo hlásenie chýb nás kontaktujte na:', 'web-pixel-studio-cookie-consent-eu' ); ?> <a href="mailto:contact@webpixelstudio.org">contact@webpixelstudio.org</a></p>
			<p style="margin-top:8px;"><?php esc_html_e( 'Plugin je bezplatný a open-source pod licenciou GPL-2.0. Postavený na:', 'web-pixel-studio-cookie-consent-eu' ); ?> <a href="https://cookieconsent.orestbida.com/" target="_blank" rel="noopener">cookieconsent.orestbida.com</a></p>
		</div>
		<?php
	}

	/* ================================================
	   HELPERS
	   ================================================ */
	private function t( string $key, string $fallback ): string {
		$value = $this->settings->get( $key, $fallback );

		if ( null === $value || '' === $value ) {
			return $fallback;
		}

		return is_string( $value ) ? $value : (string) $fallback;
	}

	private function tx( string $text ): string {
		$lang = (string) $this->settings->get( 'admin_lang', 'sk' );
		$translated = CCWPS_Language_Presets::translate_admin_text( $lang, $text );

		if ( null === $translated || '' === $translated ) {
			return $text;
		}

		return $translated;
	}

	private function translate_admin_html( string $html ): string {
		$lang = (string) $this->settings->get( 'admin_lang', 'sk' );
		if ( 'sk' === $lang ) {
			return $html;
		}

		$phrase_map = CCWPS_Language_Presets::get_admin_text_map( $lang );
		$value_map  = CCWPS_Language_Presets::get_admin_strings_value_map( $lang );

		if ( empty( $phrase_map ) && empty( $value_map ) ) {
			return $html;
		}

		$build_effective_map = static function( array $map ): array {
			$effective_map = $map;
			foreach ( $map as $from => $to ) {
				$trimmed      = trim( (string) $from );
				$ltrim        = ltrim( (string) $from );
				$nbsp         = str_replace( ' ', '&nbsp;', (string) $from );
				$escaped_from = esc_html( (string) $from );
				$escaped_to   = esc_html( (string) $to );
				if ( $trimmed !== $from && ! isset( $effective_map[ $trimmed ] ) ) {
					$effective_map[ $trimmed ] = $to;
				}
				if ( $ltrim !== $from && ! isset( $effective_map[ $ltrim ] ) ) {
					$effective_map[ $ltrim ] = $to;
				}
				if ( $nbsp !== $from && ! isset( $effective_map[ $nbsp ] ) ) {
					$effective_map[ $nbsp ] = $to;
				}
				if ( $escaped_from !== $from && ! isset( $effective_map[ $escaped_from ] ) ) {
					$effective_map[ $escaped_from ] = $escaped_to;
				}
			}

			return $effective_map;
		};

		// 1) Replace long phrase map first to prevent partial token substitutions.
		if ( ! empty( $phrase_map ) ) {
			$html = strtr( $html, $build_effective_map( $phrase_map ) );
		}

		// 2) Replace shorter value-level labels/tokens from admin_strings.
		if ( ! empty( $value_map ) ) {
			$html = strtr( $html, $build_effective_map( $value_map ) );
		}

		return $html;
	}

	private function switch_admin_locale(): bool {
		if ( ! function_exists( 'switch_to_locale' ) ) {
			return false;
		}

		$admin_lang = (string) $this->settings->get( 'admin_lang', 'sk' );
		$locale_map = [
			'sk' => 'sk_SK',
			'en' => 'en_US',
			'cs' => 'cs_CZ',
			'de' => 'de_DE',
			'fr' => 'fr_FR',
			'es' => 'es_ES',
			'pl' => 'pl_PL',
			'hu' => 'hu_HU',
			'it' => 'it_IT',
		];

		$target_locale = $locale_map[ $admin_lang ] ?? 'sk_SK';
		$current       = determine_locale();

		if ( $current === $target_locale ) {
			return false;
		}

		return switch_to_locale( $target_locale );
	}

	private function get_home_url_host(): string {
		return (string) wp_parse_url( home_url(), PHP_URL_HOST );
	}

	private function ensure_required_plugin_cookies(): void {
		$existing = $this->cookie_manager->get_all();
		$names    = array_map(
			static fn( array $row ): string => strtolower( (string) ( $row['name'] ?? '' ) ),
			$existing
		);

		$host = $this->get_home_url_host();
		if ( '' === $host ) {
			$host = 'localhost';
		}

		$shared_host = preg_replace( '/^www\./i', '', ltrim( $host, '.' ) );

		$required = [
			[
				'name'        => 'ccwps_consent',
				'domain'      => '.' . $shared_host,
				'expiration'  => $this->translate_required_cookie_duration( '1 year' ),
				'path'        => '/',
				'description' => $this->translate_required_cookie_description( 'ccwps_consent' ),
				'is_regex'    => 0,
				'category'    => 'necessary',
			],
			[
				'name'        => 'ccwps_version',
				'domain'      => ltrim( $host, '.' ),
				'expiration'  => $this->translate_required_cookie_duration( '6 months' ),
				'path'        => '/',
				'description' => $this->translate_required_cookie_description( 'ccwps_version' ),
				'is_regex'    => 0,
				'category'    => 'necessary',
			],
		];

		$existing_by_name = [];
		foreach ( $existing as $row ) {
			$name = strtolower( (string) ( $row['name'] ?? '' ) );
			if ( '' !== $name ) {
				$existing_by_name[ $name ] = $row;
			}
		}

		foreach ( $required as $cookie ) {
			$cookie_name = strtolower( $cookie['name'] );

			if ( in_array( $cookie_name, $names, true ) ) {
				$existing_row = $existing_by_name[ $cookie_name ] ?? null;
				if ( is_array( $existing_row ) && ! empty( $existing_row['id'] ) ) {
					$this->cookie_manager->update( (int) $existing_row['id'], $cookie );
				}
				continue;
			}

			$this->cookie_manager->insert( $cookie );
		}
	}

	private function get_required_cookie_translations(): array {
		return [
			'durations' => [
				'1 year' => [
					'sk' => '1 rok',
					'en' => '1 year',
					'cs' => '1 rok',
					'de' => '1 Jahr',
					'fr' => '1 an',
					'es' => '1 año',
					'pl' => '1 rok',
					'hu' => '1 év',
					'it' => '1 anno',
				],
				'6 months' => [
					'sk' => '6 mesiacov',
					'en' => '6 months',
					'cs' => '6 měsíců',
					'de' => '6 Monate',
					'fr' => '6 mois',
					'es' => '6 meses',
					'pl' => '6 miesięcy',
					'hu' => '6 hónap',
					'it' => '6 mesi',
				],
			],
			'descriptions' => [
				'ccwps_consent' => [
					'sk' => 'Ukladá voľby súhlasu návštevníka pre kategórie cookies, aby ich plugin vedel rešpektovať a znovu použiť.',
					'en' => 'Stores the visitor\'s consent choices for cookie categories so the plugin can respect and reapply them.',
					'cs' => 'Ukládá volby souhlasu návštěvníka pro kategorie cookies, aby je plugin mohl respektovat a znovu použít.',
					'de' => 'Speichert die Einwilligungsentscheidungen des Besuchers für Cookie-Kategorien, damit das Plugin sie respektieren und erneut anwenden kann.',
					'fr' => 'Stocke les choix de consentement du visiteur pour les catégories de cookies afin que le plugin puisse les respecter et les réappliquer.',
					'es' => 'Almacena las elecciones de consentimiento del visitante para las categorías de cookies para que el plugin pueda respetarlas y volver a aplicarlas.',
					'pl' => 'Przechowuje wybory zgody użytkownika dla kategorii cookies, aby wtyczka mogła je respektować i ponownie zastosować.',
					'hu' => 'Tárolja a látogató hozzájárulási beállításait a sütikategóriákhoz, hogy a bővítmény tiszteletben tartsa és újra alkalmazza őket.',
					'it' => 'Memorizza le scelte di consenso del visitatore per le categorie di cookie, così il plugin può rispettarle e riapplicarle.',
				],
				'ccwps_version' => [
					'sk' => 'Ukladá verziu konfigurácie súhlasu v plugine, aby sa po zmene nastavení vedel znovu vyžiadať súhlas.',
					'en' => 'Stores the current consent configuration version to detect changes and request consent again when needed.',
					'cs' => 'Ukládá verzi konfigurace souhlasu v pluginu, aby po změně nastavení mohl znovu vyžádat souhlas.',
					'de' => 'Speichert die aktuelle Version der Einwilligungskonfiguration, um Änderungen zu erkennen und bei Bedarf erneut um Zustimmung zu bitten.',
					'fr' => 'Stocke la version actuelle de la configuration du consentement afin de détecter les changements et de redemander le consentement si nécessaire.',
					'es' => 'Almacena la versión actual de la configuración del consentimiento para detectar cambios y solicitar el consentimiento de nuevo cuando sea necesario.',
					'pl' => 'Przechowuje bieżącą wersję konfiguracji zgody, aby wykrywać zmiany i w razie potrzeby ponownie poprosić o zgodę.',
					'hu' => 'Tárolja a hozzájárulási beállítások aktuális verzióját, hogy a módosításokat észlelje, és szükség esetén újra hozzájárulást kérjen.',
					'it' => 'Memorizza la versione corrente della configurazione del consenso per rilevare modifiche e richiedere nuovamente il consenso quando necessario.',
				],
			],
		];
	}

	private function get_required_cookie_language(): string {
		$lang_code = strtolower( (string) $this->settings->get( 'admin_lang', 'sk' ) );
		$base_lang = strtok( $lang_code, '-_' );

		return is_string( $base_lang ) && '' !== $base_lang ? $base_lang : 'sk';
	}

	private function translate_required_cookie_duration( string $duration ): string {
		$translations = $this->get_required_cookie_translations()['durations'][ $duration ] ?? [];
		$lang_code     = $this->get_required_cookie_language();

		return $translations[ $lang_code ] ?? $translations['en'] ?? $duration;
	}

	private function translate_required_cookie_description( string $cookie_name ): string {
		$translations = $this->get_required_cookie_translations()['descriptions'][ $cookie_name ] ?? [];
		$lang_code     = $this->get_required_cookie_language();

		return $translations[ $lang_code ] ?? $translations['en'] ?? '';
	}

	private function get_font_family_choices( bool $include_frontend = false ): array {
		$cache_key = 'ccwps_font_choices_v3_' . md5( home_url( '/' ) . '|' . ( $include_frontend ? 'manual' : 'base' ) );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$choices = [
			'theme'        => [],
			'theme_json'   => [],
			'elementor'    => [],
			'frontend_css' => [],
			'current'      => [],
		];
		$this->add_font_choice( $choices, 'theme', 'inherit' );

		$this->collect_font_choices_from_global_styles( $choices );
		$this->collect_font_choices_from_elementor( $choices );

		if ( $include_frontend ) {
			$this->collect_font_choices_from_frontend( $choices );
		}

		set_transient( $cache_key, $choices, 15 * MINUTE_IN_SECONDS );

		return $choices;
	}

	private function add_font_choice( array &$choices, string $group, string $value ): void {
		$value = $this->normalize_font_family_choice( $value );

		if ( ! $this->is_valid_font_family_choice( $value ) ) {
			return;
		}

		if ( ! isset( $choices[ $group ] ) || ! is_array( $choices[ $group ] ) ) {
			$choices[ $group ] = [];
		}

		$label = $this->get_font_family_choice_label( $value );
		$key   = strtolower( $label );

		foreach ( $choices as $group_choices ) {
			if ( ! is_array( $group_choices ) ) {
				continue;
			}

			foreach ( $group_choices as $existing_choice ) {
				if ( ! is_array( $existing_choice ) ) {
					continue;
				}

				$existing_label = isset( $existing_choice['label'] ) ? strtolower( (string) $existing_choice['label'] ) : '';
				$existing_value = isset( $existing_choice['value'] ) ? (string) $existing_choice['value'] : '';

				if ( $existing_value === $value || $existing_label === $key ) {
					return;
				}
			}
		}

		$choices[ $group ][] = [
			'value' => $value,
			'label' => $label,
		];
	}

	private function collect_font_choices_from_global_styles( array &$choices ): void {
		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return;
		}

		$global_settings = wp_get_global_settings();
		$this->extract_font_choices_from_data( $global_settings, $choices, 'theme_json' );
	}

	private function collect_font_choices_from_elementor( array &$choices ): void {
		$active_kit_id = (int) get_option( 'elementor_active_kit' );
		if ( $active_kit_id > 0 ) {
			$kit_settings = get_post_meta( $active_kit_id, '_elementor_page_settings', true );
			if ( is_array( $kit_settings ) ) {
				$this->extract_font_choices_from_data( $kit_settings, $choices, 'elementor' );
			}
		}

		$legacy_typography = get_option( 'elementor_scheme_typography' );
		if ( is_array( $legacy_typography ) ) {
			$this->extract_font_choices_from_data( $legacy_typography, $choices, 'elementor' );
		}
	}

	private function extract_font_choices_from_data( $data, array &$choices, string $group ): void {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( is_string( $key ) && is_string( $value ) && $this->is_font_family_key( $key ) ) {
					$this->maybe_add_font_string( $value, $choices, $group );
				}

				$this->extract_font_choices_from_data( $value, $choices, $group );
			}

			return;
		}
	}

	private function maybe_add_font_string( string $value, array &$choices, string $group ): void {
		$this->add_font_choice( $choices, $group, $value );
	}

	private function is_font_family_key( string $key ): bool {
		return 1 === preg_match( '/(^font_family$|^font-family$|fontfamily$|typography_font_family|font_family_)/i', $key );
	}

	private function normalize_font_family_choice( string $value ): string {
		$value = trim( preg_replace( '/\s+/', ' ', html_entity_decode( $value, ENT_QUOTES, 'UTF-8' ) ) );
		$value = preg_replace( '/!important$/i', '', $value );
		$value = trim( rtrim( (string) $value, "; \t\n\r\0\x0B" ) );

		if ( 0 === stripos( $value, 'font-family:' ) ) {
			$value = trim( substr( $value, 12 ) );
		}

		return $value;
	}

	private function get_font_family_choice_label( string $value ): string {
		if ( 'inherit' === $value ) {
			return (string) __( 'Použiť font témy', 'web-pixel-studio-cookie-consent-eu' );
		}

		$parts = array_map( 'trim', explode( ',', $value ) );
		$first = $parts[0] ?? $value;

		return trim( $first, "\"' " );
	}

	private function get_font_choice_group_labels(): array {
		return [
			'theme'        => $this->t( 'Téma', 'Téma' ),
			'theme_json'   => $this->t( 'Theme.json', 'Theme.json' ),
			'elementor'    => $this->t( 'Elementor', 'Elementor' ),
			'frontend_css' => $this->t( 'Frontend CSS', 'Frontend CSS' ),
			'current'      => $this->t( 'Aktuálne uložené', 'Aktuálne uložené' ),
		];
	}

	private function font_choice_exists( array $choices, string $value ): bool {
		$value = $this->normalize_font_family_choice( $value );

		foreach ( $choices as $group_choices ) {
			if ( ! is_array( $group_choices ) ) {
				continue;
			}

			foreach ( $group_choices as $choice ) {
				if ( is_array( $choice ) && isset( $choice['value'] ) && (string) $choice['value'] === $value ) {
					return true;
				}
			}
		}

		return false;
	}

	private function is_valid_font_family_choice( string $value ): bool {
		if ( '' === $value || strlen( $value ) > 200 ) {
			return false;
		}

		if ( 'inherit' === $value ) {
			return true;
		}

		if (
			preg_match( '/[{}:]/', $value )
			|| false !== stripos( $value, 'gradient(' )
			|| false !== stripos( $value, 'var(' )
			|| false !== stripos( $value, 'url(' )
			|| false !== stripos( $value, '@font-face' )
			|| false !== stripos( $value, '--' )
			|| false !== stripos( $value, 'rgb(' )
			|| false !== stripos( $value, 'rgba(' )
			|| false !== stripos( $value, 'hsl(' )
			|| false !== stripos( $value, 'hsla(' )
			|| false !== strpos( $value, '#' )
		) {
			return false;
		}

		$parts = array_map( 'trim', explode( ',', $value ) );
		if ( empty( $parts ) ) {
			return false;
		}

		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				return false;
			}

			if ( ! preg_match( '/^(?:"[A-Za-z0-9 \-_]+"|\'[A-Za-z0-9 \-_]+\'|[A-Za-z][A-Za-z0-9 \-_]*)$/', $part ) ) {
				return false;
			}
		}

		return true;
	}

	private function collect_font_choices_from_frontend( array &$choices ): void {
		$response = wp_remote_get(
			home_url( '/' ),
			[
				'timeout'    => 4,
				'redirection'=> 3,
				'user-agent' => 'CCWPS Font Detector',
			]
		);

		if ( is_wp_error( $response ) ) {
			return;
		}

		$html = (string) wp_remote_retrieve_body( $response );
		if ( '' === $html ) {
			return;
		}

		if ( preg_match_all( '#<style[^>]*>(.*?)</style>#is', $html, $style_matches ) ) {
			foreach ( $style_matches[1] as $css ) {
				$this->extract_font_choices_from_css( (string) $css, $choices, 'frontend_css' );
			}
		}

		if ( preg_match_all( '#<link[^>]+rel=["\'][^"\']*stylesheet[^"\']*["\'][^>]+href=["\']([^"\']+)["\']#i', $html, $link_matches ) ) {
			$home_host = $this->get_home_url_host();
			foreach ( array_unique( $link_matches[1] ) as $href ) {
				$css_url = $this->normalize_frontend_asset_url( (string) $href );
				if ( '' === $css_url ) {
					continue;
				}

			if ( false !== stripos( $css_url, '/web-pixel-studio-cookie-consent-eu/' ) ) {

				$asset_host = (string) wp_parse_url( $css_url, PHP_URL_HOST );
				if ( '' !== $asset_host && '' !== $home_host && $asset_host !== $home_host ) {
					continue;
				}

				$css_response = wp_remote_get(
					$css_url,
					[
						'timeout'    => 4,
						'redirection'=> 3,
						'user-agent' => 'CCWPS Font Detector',
					]
				);

				if ( is_wp_error( $css_response ) ) {
					continue;
				}

				$css = (string) wp_remote_retrieve_body( $css_response );
				if ( '' !== $css ) {
					$this->extract_font_choices_from_css( $css, $choices, $this->get_font_choice_group_from_asset_url( $css_url ) );
				}
			}
		}
	}
	}

	private function get_font_choice_group_from_asset_url( string $css_url ): string {
		$path = (string) wp_parse_url( $css_url, PHP_URL_PATH );
		$path = strtolower( $path );

		if ( false !== strpos( $path, '/elementor/' ) ) {
			return 'elementor';
		}

		if ( false !== strpos( $path, '/themes/' ) ) {
			return 'theme';
		}

		return 'frontend_css';
	}

	private function normalize_frontend_asset_url( string $url ): string {
		$url = trim( html_entity_decode( $url, ENT_QUOTES, 'UTF-8' ) );

		if ( '' === $url ) {
			return '';
		}

		if ( 0 === strpos( $url, '//' ) ) {
			$scheme = is_ssl() ? 'https:' : 'http:';
			return $scheme . $url;
		}

		if ( preg_match( '#^https?://#i', $url ) ) {
			return $url;
		}

		if ( '/' === $url[0] ) {
			return home_url( $url );
		}

		return home_url( '/' . ltrim( $url, '/' ) );
	}

	private function extract_font_choices_from_css( string $css, array &$choices, string $group ): void {
		if ( preg_match_all( '/(?:font-family|--wp--preset--font-family--[a-z0-9_-]+)\s*:\s*([^;}{]+)[;}]?/i', $css, $matches ) ) {
			foreach ( $matches[1] as $font_value ) {
				$this->add_font_choice( $choices, $group, trim( (string) $font_value ) );
			}
		}
	}

	private function get_query_string( string $key, string $default = '' ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin UI state.
		if ( ! isset( $_GET[ $key ] ) ) {
			return $default;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin UI state.
		return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
	}

	private function get_query_key( string $key, string $default = '' ): string {
		return sanitize_key( $this->get_query_string( $key, $default ) );
	}

	private function get_query_int( string $key, int $default = 0 ): int {
		return max( 1, (int) $this->get_query_string( $key, (string) $default ) );
	}

	private function anonymize_ip( string $ip ): string {
		if ( '' === $ip ) {
			return '—';
		}
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return substr( $ip, 0, (int) strrpos( $ip, '.' ) ) . '.xxx';
		}
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$parts = explode( ':', $ip );
			return implode( ':', array_slice( $parts, 0, 4 ) ) . '::xxxx';
		}
		return '***';
	}

	private function get_import_tmp_name(): string {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES['ccwps_import_file']['tmp_name'] ) ) {
			return '';
		}

		$tmp_name = sanitize_text_field( wp_unslash( $_FILES['ccwps_import_file']['tmp_name'] ) );
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing

		return $tmp_name;
	}

	private function is_valid_import_setting_key( $key ): bool {
		if ( ! is_string( $key ) || '' === $key ) {
			return false;
		}

		if ( 1 !== preg_match( '/^[a-z0-9_]+$/i', $key ) ) {
			return false;
		}

		return ! in_array( $key, [ 'db_version', 'registered_keys' ], true );
	}

	private function sanitize_import_setting_value( string $key, $value ) {
		// Color keys - accept hex colors (including 'transparent')
		$color_keys = [
			'primary_color', 'banner_title_color', 'text_color', 'bg_color', 'btn_text_color',
			'floating_icon_bg', 'floating_icon_bg_hv', 'floating_icon_color', 'floating_popup_bg', 'floating_popup_text',
			'btn_primary_bg', 'btn_primary_bg_hv', 'btn_primary_txt',
			'btn_ghost_bg', 'btn_ghost_bg_hv', 'btn_ghost_txt', 'btn_ghost_txt_hv',
			'btn_outline_bg', 'btn_outline_bg_hv', 'btn_outline_txt', 'btn_outline_border',
			'modal_bg', 'modal_header_bg', 'modal_footer_bg', 'modal_border', 'modal_text', 'modal_consent_id_color',
			'cat_header_bg', 'cat_header_bg_hv', 'toggle_on_color', 'always_on_color',
		];
		
		// URL keys - accept full URLs
		$url_keys = [ 'icon_custom_url', 'banner_logo_url', 'banner_logo_link_url', 'cookie_policy_link_url', 'privacy_policy_link_url', 'matomo_url' ];
		
		// HTML keys - allow sanitized HTML (wp_kses_post)
		$html_keys = [ 'lang_banner_description', 'lang_necessary_desc', 'lang_analytics_desc', 'lang_targeting_desc', 'lang_preferences_desc' ];
		
		// Boolean keys (accept 0/1/true/false)
		$bool_keys = [
			'autorun', 'force_consent', 'auto_clear_cookies', 'page_scripts',
			'hide_from_bots', 'reconsent', 'record_consents',
			'frontend_detect_visitor_language', 'hide_empty_categories',
			'banner_show_icon', 'floating_show_powered_by', 'banner_logo_show', 'add_cookie_policy_link', 'add_privacy_policy_link', 'matomo_anonymous_without_consent',
		];
		
		// Integer keys (positive integers)
		$int_keys = [
			'delay', 'cookie_expiration', 'matomo_site_id',
			'btn_border_radius', 'banner_border_radius', 'modal_border_radius', 'banner_logo_width', 'cloud_bg_opacity',
			'floating_icon_margin_y', 'floating_icon_margin_y_mobile',
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
		];
		
		// String keys with predefined values (enums)
		$enum_keys = [
			'consent_mode_version' => [ 'off', 'v2', 'v3' ],
			'banner_layout'        => [ 'box', 'bar', 'cloud' ],
			'banner_position'      => [ 'bottom-left', 'bottom-right', 'bottom-center', 'top-left', 'top-right', 'top-center', 'middle-center' ],
			'icon_position'        => [ 'bottom-right', 'bottom-left', 'top-right', 'top-left' ],
			'icon_type'            => [ 'cookie', 'shield', 'settings', 'lock', 'custom' ],
			'admin_lang'           => [ 'sk', 'en', 'cs', 'de', 'fr', 'es', 'pl', 'hu', 'it' ],
		];
		
		// Shadow/font keys - free text strings
		$text_keys = [ 'banner_shadow', 'font_family', 'cookie_path', 'cookie_domain', 'gtm_id' ];
		
		// Language text fields - sanitize as text
		$lang_text_keys = [
			'lang_banner_title', 'lang_accept_all', 'lang_reject_all', 'lang_manage_preferences',
			'lang_save_preferences', 'lang_close', 'lang_necessary_title', 'lang_analytics_title',
			'lang_targeting_title', 'lang_preferences_title', 'lang_powered_by', 'lang_consent_id_label',
			'lang_always_on', 'lang_cookie_name', 'lang_cookie_domain', 'lang_cookie_expiration',
			'lang_cookie_description',
		];

		// Handle color keys
		if ( in_array( $key, $color_keys, true ) ) {
			if ( ! is_string( $value ) ) {
				return '';
			}

			if ( 'transparent' === strtolower( $value ) ) {
				return 'transparent';
			}

			$color = sanitize_hex_color( $value );
			return is_string( $color ) ? $color : '';
		}

		// Handle URL keys
		if ( in_array( $key, $url_keys, true ) ) {
			return esc_url_raw( is_scalar( $value ) ? (string) $value : '' );
		}

		// Handle HTML keys
		if ( in_array( $key, $html_keys, true ) ) {
			return wp_kses_post( is_scalar( $value ) ? (string) $value : '' );
		}

		// Handle boolean keys
		if ( in_array( $key, $bool_keys, true ) ) {
			if ( is_bool( $value ) ) {
				return $value ? 1 : 0;
			}
			if ( is_int( $value ) ) {
				return $value > 0 ? 1 : 0;
			}
			if ( is_string( $value ) ) {
				return in_array( strtolower( $value ), [ '1', 'true', 'yes' ], true ) ? 1 : 0;
			}
			return 0;
		}

		// Handle integer keys
		if ( in_array( $key, $int_keys, true ) ) {
			$int_val = (int) $value;

			if ( 'cloud_bg_opacity' === $key ) {
				return (string) max( 0, min( 100, $int_val ) );
			}

			if ( in_array( $key, [ 'floating_icon_margin_y', 'floating_icon_margin_y_mobile' ], true ) ) {
				return (string) max( 0, min( 500, $int_val ) );
			}

			return (string) max( 0, $int_val );
		}

		// Handle enum keys
		if ( isset( $enum_keys[ $key ] ) ) {
			$str_val = sanitize_text_field( (string) $value );
			if ( in_array( $str_val, $enum_keys[ $key ], true ) ) {
				return $str_val;
			}
			// Return default value if not valid
			return '';
		}

		// Handle text keys (banner_shadow, font_family, cookie_path, etc.)
		if ( in_array( $key, $text_keys, true ) ) {
			return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
		}

		// Handle language text keys
		if ( in_array( $key, $lang_text_keys, true ) ) {
			return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
		}

		// Handle arrays (legacy support)
		if ( is_array( $value ) ) {
			$sanitized = map_deep( $value, 'sanitize_text_field' );
			return wp_json_encode( $sanitized );
		}

		// Handle booleans (fallback)
		if ( is_bool( $value ) ) {
			return $value ? 1 : 0;
		}

		// Handle numbers (fallback)
		if ( is_int( $value ) || is_float( $value ) ) {
			return (string) $value;
		}

		// Default: sanitize as text
		return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
	}

	private function trow_toggle( string $name, string $title, string $desc, $value, string $tip = '' ): void {
		?>
		<tr>
			<th><?php echo esc_html( $title ); ?><?php if ( $desc ) : ?><p class="desc"><?php echo esc_html( $desc ); ?></p><?php endif; ?></th>
			<td>
				<label class="ccwps-toggle"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $value, 1 ); ?>><span class="ccwps-toggle-slider"></span></label>
				<?php if ( $tip ) $this->tip( $tip ); ?>
			</td>
		</tr>
		<?php
	}

	private function tip( string $text ): void {
		?>
		<details class="ccwps-tip">
			<summary><?php echo esc_html( $this->t( 'admin_tip_label', 'Tip / Nápoveda' ) ); ?></summary>
			<p><?php echo wp_kses( $text, [ 'code' => [], 'strong' => [], 'em' => [] ] ); ?></p>
		</details>
		<?php
	}

	/* ================================================
	   EXPORT / IMPORT
	   ================================================ */
	public function export_csv(): void {
		check_admin_referer( 'ccwps_export_csv' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="consent-log-' . gmdate( 'Y-m-d' ) . '.csv"' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->log->export_csv();
		exit;
	}

	public function export_settings(): void {
		check_admin_referer( 'ccwps_export_settings' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		$data = [
			'_plugin' => 'web-pixel-studio-cookie-consent-eu',
			'_plugin_aliases' => $this->get_supported_import_plugin_ids(),
			'_version' => CCWPS_VERSION,
			'_date' => gmdate( 'c' ),
			'_site' => home_url(),
			'settings' => $this->settings->get_all(),
			'cookies' => $this->cookie_manager->get_all(),
			'block_rules' => $this->block_manager->get_all(),
		];
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="ccwps-settings-' . gmdate( 'Y-m-d' ) . '.json"' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	public function import_settings(): void {
		check_admin_referer( 'ccwps_import_settings', '_wpnonce_import' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		$redirect = admin_url( 'admin.php?page=ccwps&tab=tools' );
		$tmp_name = $this->get_import_tmp_name();
		if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) { wp_safe_redirect( $redirect . '&ccwps_imported=0' ); exit; }
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$decoded = json_decode( file_get_contents( $tmp_name ), true );
		if ( ! is_array( $decoded ) ) { wp_safe_redirect( $redirect . '&ccwps_imported=0' ); exit; }

		$payload = $this->normalize_import_payload( $decoded );
		if ( null === $payload || empty( $payload['settings'] ) ) { wp_safe_redirect( $redirect . '&ccwps_imported=0' ); exit; }

		$import_ok = true;

		foreach ( (array) $payload['settings'] as $key => $value ) {
			$normalized_key = $this->map_legacy_import_setting_key( $key );

			if ( ! $this->is_valid_import_setting_key( $normalized_key ) ) {
				continue;
			}

			$sanitized_value = $this->sanitize_import_setting_value( $normalized_key, $value );
			$this->settings->set( $normalized_key, $sanitized_value );
		}

		if ( 'bar' === (string) $this->settings->get( 'banner_layout', 'box' ) ) {
			$bar_positions = [ 'top-center', 'bottom-center' ];
			$position      = sanitize_key( (string) $this->settings->get( 'banner_position', 'bottom-center' ) );

			if ( ! in_array( $position, $bar_positions, true ) ) {
				$this->settings->set( 'banner_position', 'bottom-center' );
			}
		}

		if ( ! empty( $payload['cookies'] ) ) {
			$import_ok = $this->cookie_manager->replace_all(
				$this->remap_import_cookie_domains(
					(array) $payload['cookies'],
					(string) ( $payload['source_site'] ?? '' )
				)
			) && $import_ok;
		}

		if ( ! empty( $payload['block_rules'] ) ) {
			$import_ok = $this->block_manager->replace_all( $payload['block_rules'] ) && $import_ok;
		}

		wp_safe_redirect( $redirect . '&ccwps_imported=' . ( $import_ok ? '1' : '0' ) ); exit;
	}

	private function get_supported_import_plugin_ids(): array {
		return [
			'web-pixel-studio-cookie-consent-eu',
			'web-pixel-studio-cookie-consent-for-eu',
			'cookie-consent-webpixelstudio',
			'advanced-cookie-consent-for-eu',
		];
	}

	private function normalize_import_payload( array $decoded ): ?array {
		$plugin_raw = isset( $decoded['_plugin'] ) && is_scalar( $decoded['_plugin'] )
			? (string) $decoded['_plugin']
			: '';
		$plugin_id = sanitize_key( $plugin_raw );

		if ( '' !== $plugin_id && ! in_array( $plugin_id, $this->get_supported_import_plugin_ids(), true ) ) {
			return null;
		}

		$settings = [];
		if ( isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ) {
			$settings = $decoded['settings'];
		} elseif ( isset( $decoded['options'] ) && is_array( $decoded['options'] ) ) {
			$settings = $decoded['options'];
		} else {
			$ignored = [
				'_plugin', '_plugin_aliases', '_version', '_date', '_site',
				'settings', 'options', 'cookies', 'cookie_declarations',
				'block_rules', 'blocks', 'rules',
			];

			foreach ( $decoded as $key => $value ) {
				if ( ! is_string( $key ) || in_array( $key, $ignored, true ) ) {
					continue;
				}

				$settings[ $key ] = $value;
			}
		}

		if ( empty( $settings ) ) {
			return null;
		}

		$cookies = [];
		if ( isset( $decoded['cookies'] ) && is_array( $decoded['cookies'] ) ) {
			$cookies = $decoded['cookies'];
		} elseif ( isset( $decoded['cookie_declarations'] ) && is_array( $decoded['cookie_declarations'] ) ) {
			$cookies = $decoded['cookie_declarations'];
		}

		$block_rules = [];
		if ( isset( $decoded['block_rules'] ) && is_array( $decoded['block_rules'] ) ) {
			$block_rules = $decoded['block_rules'];
		} elseif ( isset( $decoded['blocks'] ) && is_array( $decoded['blocks'] ) ) {
			$block_rules = $decoded['blocks'];
		} elseif ( isset( $decoded['rules'] ) && is_array( $decoded['rules'] ) ) {
			$block_rules = $decoded['rules'];
		}

		return [
			'settings' => $settings,
			'cookies' => $cookies,
			'block_rules' => $block_rules,
			'source_site' => isset( $decoded['_site'] ) && is_scalar( $decoded['_site'] ) ? (string) $decoded['_site'] : '',
		];
	}

	private function remap_import_cookie_domains( array $cookies, string $source_site_url = '' ): array {
		$target_host = $this->get_home_url_host();
		if ( '' === $target_host ) {
			return $cookies;
		}

		$source_host = (string) ( wp_parse_url( $source_site_url, PHP_URL_HOST ) ?: '' );
		$source_host = preg_replace( '/^www\./i', '', ltrim( $source_host, '.' ) );
		$target_host = preg_replace( '/^www\./i', '', ltrim( $target_host, '.' ) );

		if ( '' === $source_host ) {
			$source_host = $target_host;
		}

		$source_variants = array_filter( [ $source_host, '.' . $source_host ] );
		$target_variants = [ $target_host, '.' . $target_host ];

		foreach ( $cookies as &$cookie ) {
			if ( ! is_array( $cookie ) ) {
				continue;
			}

			$domain = trim( (string) ( $cookie['domain'] ?? '' ) );
			if ( '' === $domain ) {
				continue;
			}

			$normalized = preg_replace( '/^www\./i', '', ltrim( strtolower( $domain ), '.' ) );
			if ( ! in_array( $normalized, [ $source_host, $target_host ], true ) ) {
				continue;
			}

			$cookie['domain'] = in_array( $domain, $target_variants, true ) || str_starts_with( $domain, '.' )
				? $target_variants[1]
				: $target_variants[0];
		}
		unset( $cookie );

		return $cookies;
	}

	private function map_legacy_import_setting_key( $key ): string {
		if ( ! is_string( $key ) ) {
			return '';
		}

		$legacy_map = [
			'banner_show_powered_by' => 'floating_show_powered_by',
			'floating_show_powered'  => 'floating_show_powered_by',
			'banner_icon_show'       => 'banner_show_icon',
			'hide_empty_cats'        => 'hide_empty_categories',
		];

		return $legacy_map[ $key ] ?? $key;
	}
}