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
	}

	public function add_menu(): void {
		add_menu_page(
			__( 'Cookie Consent', 'cookie-consent-webpixelstudio' ),
			__( 'Cookie Consent', 'cookie-consent-webpixelstudio' ),
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
				'confirmLangChange' => $this->tx( 'Zmeniť jazyk administrácie a aplikovať preklady na frontend?' ),
				'logCleared'    => $this->tx( 'Záznamy boli vymazané.' ),
				'langApplied'   => $this->tx( 'Jazyk bol aplikovaný. Uložte nastavenia.' ),
				'resetDone'     => $this->tx( 'Nastavenia boli resetované.' ),
				'saving'        => $this->tx( 'Ukladám…' ),
				'saveSettings'  => $this->tx( 'Uložiť nastavenia' ),
				'addCookie'     => $this->tx( 'Pridať cookie' ),
				'editCookie'    => $this->tx( 'Upraviť cookie' ),
				'enterCookieName' => $this->tx( 'Zadajte názov cookie.' ),
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
				'cookieColName' => $this->settings->get( 'lang_cookie_name', __( 'Názov', 'cookie-consent-webpixelstudio' ) ),
				'cookieColDomain' => $this->settings->get( 'lang_cookie_domain', __( 'Doména', 'cookie-consent-webpixelstudio' ) ),
				'cookieColExpiration' => $this->settings->get( 'lang_cookie_expiration', __( 'Platnosť', 'cookie-consent-webpixelstudio' ) ),
				'cookieColDescription' => $this->settings->get( 'lang_cookie_description', __( 'Popis', 'cookie-consent-webpixelstudio' ) ),
				'catNecessary'  => $this->settings->get( 'lang_necessary_title', __( 'Nevyhnutné', 'cookie-consent-webpixelstudio' ) ),
				'catAnalytics'  => $this->settings->get( 'lang_analytics_title', __( 'Analytické', 'cookie-consent-webpixelstudio' ) ),
				'catTargeting'  => $this->settings->get( 'lang_targeting_title', __( 'Marketingové', 'cookie-consent-webpixelstudio' ) ),
				'catPreferences'=> $this->settings->get( 'lang_preferences_title', __( 'Preferenčné', 'cookie-consent-webpixelstudio' ) ),
				'applyPreset'   => $this->tx( 'Použiť predvoľbu' ),
				'selectPreset'  => $this->tx( 'Vyberte predvoľbu.' ),
				'presetsAdded'  => $this->tx( 'Predvoľby boli pridané.' ),
				'noNewPresets'  => $this->tx( 'Všetky vybrané predvoľby už existujú.' ),
			],
			'langPresets' => CCWPS_Language_Presets::get_all(),
			'cookies'     => $this->cookie_manager->get_grouped(),
			'blockRules'  => $this->block_manager->get_all(),
			'settings'    => $this->settings->get_all(),
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
		try {
		ob_start();

		$import_notice = '';
		if ( '' !== $this->get_query_string( 'ccwps_imported' ) ) {
			$import_notice = '1' === $this->get_query_string( 'ccwps_imported' )
				? '<div class="ccwps-notice success">' . esc_html__( 'Nastavenia boli importované.', 'cookie-consent-webpixelstudio' ) . '</div>'
				: '<div class="ccwps-notice error">'   . esc_html__( 'Import zlyhal. Skontrolujte súbor.', 'cookie-consent-webpixelstudio' ) . '</div>';
		}

		$s          = $this->settings;
		$active_tab = $this->get_query_key( 'tab', 'settings' );
		$admin_lang = $s->get( 'admin_lang', 'sk' );

		$nav_items = [
			'settings'     => [ 'icon' => '⚙️', 'label' => $s->get( 'admin_nav_settings',     __( 'Nastavenia',          'cookie-consent-webpixelstudio' ) ) ],
			'appearance'   => [ 'icon' => '🎨', 'label' => $s->get( 'admin_nav_appearance',   __( 'Vzhľad',              'cookie-consent-webpixelstudio' ) ) ],
			'translations' => [ 'icon' => '🌐', 'label' => $s->get( 'admin_nav_translations', __( 'Preklady',            'cookie-consent-webpixelstudio' ) ) ],
			'cookies'      => [ 'icon' => '🍪', 'label' => $s->get( 'admin_nav_cookies',      __( 'Cookies',             'cookie-consent-webpixelstudio' ) ) ],
			'blocking'     => [ 'icon' => '🚫', 'label' => $s->get( 'admin_nav_blocking',     __( 'Blokovanie skriptov', 'cookie-consent-webpixelstudio' ) ) ],
			'log'          => [ 'icon' => '📋', 'label' => $s->get( 'admin_nav_log',          __( 'Záznamy súhlasov',    'cookie-consent-webpixelstudio' ) ) ],
			'shortcodes'   => [ 'icon' => '[ ]','label' => $s->get( 'admin_nav_shortcodes',   __( 'Shortcodes',          'cookie-consent-webpixelstudio' ) ) ],
			'gtm-template' => [ 'icon' => '🏷️', 'label' => $this->tx( 'GTM šablóna' ) ],
			'tools'        => [ 'icon' => '🔧', 'label' => $s->get( 'admin_nav_tools',        __( 'Nástroje',            'cookie-consent-webpixelstudio' ) ) ],
			'about'        => [ 'icon' => 'ℹ️', 'label' => $s->get( 'admin_nav_about',        __( 'O plugine',           'cookie-consent-webpixelstudio' ) ) ],
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
					<span class="ccwps-admin-lang-label">🌐 <?php echo esc_html( $s->get( 'admin_lang_label', __( 'Jazyk:', 'cookie-consent-webpixelstudio' ) ) ); ?></span>
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
					<div class="ccwps-sidebar-preview-title"><?php echo esc_html( $s->get( 'admin_preview_label', __( 'Náhľad:', 'cookie-consent-webpixelstudio' ) ) ); ?></div>
					<button type="button" class="button ccwps-btn-preview-full ccwps-btn-preview-action" id="ccwps-preview-banner">
						👁 <?php echo esc_html( $s->get( 'admin_preview_banner', __( 'Zobraziť banner', 'cookie-consent-webpixelstudio' ) ) ); ?>
					</button>
					<button type="button" class="button ccwps-btn-preview-full ccwps-btn-preview-action" id="ccwps-preview-modal">
						⚙️ <?php echo esc_html( $s->get( 'admin_preview_modal', __( 'Nastavenia cookies', 'cookie-consent-webpixelstudio' ) ) ); ?>
					</button>
				</div>

				<div class="ccwps-sidebar-footer">
					<div class="ccwps-sidebar-powered">
						<?php esc_html_e( 'Funguje na základe', 'cookie-consent-webpixelstudio' ); ?>
						<a href="https://cookieconsent.orestbida.com/" target="_blank" rel="noopener">orest bida</a>
					</div>
					<div class="ccwps-sidebar-author">
						<?php esc_html_e( 'Bezplatný plugin vytvoril:', 'cookie-consent-webpixelstudio' ); ?><br>
						<a href="https://wps.sk" target="_blank" rel="noopener"><strong>Web Pixel Studio</strong></a>
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
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Full admin markup is generated by this class and escaped at source.
		echo $translated_html;
		} finally {
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
				<?php $this->trow_toggle( 'autorun', __( 'Automatické spustenie', 'cookie-consent-webpixelstudio' ), __( 'Banner sa zobrazí automaticky pri načítaní stránky.', 'cookie-consent-webpixelstudio' ), $s->get( 'autorun' ), __( '💡 Odporúčame zapnúť. Vypnuté = zobraziť manuálne cez <code>CookieConsentWPS.showBanner()</code>', 'cookie-consent-webpixelstudio' ) ); ?>
				<?php $this->trow_toggle( 'force_consent', __( 'Vynútený súhlas', 'cookie-consent-webpixelstudio' ), __( 'Používateľ nemôže prehliadať web bez udelenia súhlasu.', 'cookie-consent-webpixelstudio' ), $s->get( 'force_consent' ), __( '⚠️ GDPR neukladá povinnosť vynútiť súhlas. Odporúčame VYPNÚŤ – používateľ má právo odmietnuť.', 'cookie-consent-webpixelstudio' ) ); ?>
				<?php $this->trow_toggle( 'auto_clear_cookies', __( 'Automatické vymazanie cookies', 'cookie-consent-webpixelstudio' ), __( 'Pri odvolaní súhlasu sa automaticky vymažú cookies danej kategórie.', 'cookie-consent-webpixelstudio' ), $s->get( 'auto_clear_cookies' ), __( '💡 Funguje len pre cookies deklarované v záložke Cookies. Odporúčame zapnúť.', 'cookie-consent-webpixelstudio' ) ); ?>
				<?php $this->trow_toggle( 'page_scripts', __( 'Správa skriptov na stránke', 'cookie-consent-webpixelstudio' ), __( 'Blokuje skripty tretích strán, kým používateľ neudelí súhlas.', 'cookie-consent-webpixelstudio' ), $s->get( 'page_scripts' ), __( '💡 Definujte pravidlá v záložke "Blokovanie skriptov". Plugin zmení typ skriptu na text/plain.', 'cookie-consent-webpixelstudio' ) ); ?>
				<?php $this->trow_toggle( 'hide_from_bots', __( 'Skryť pre robotov', 'cookie-consent-webpixelstudio' ), __( 'Banner sa nezobrazí vyhľadávačom a crawlerom.', 'cookie-consent-webpixelstudio' ), $s->get( 'hide_from_bots' ), __( '💡 Odporúčame zapnúť. Zlepšuje SEO skóre webu.', 'cookie-consent-webpixelstudio' ) ); ?>
				<?php $this->trow_toggle( 'reconsent', __( 'Opätovný súhlas', 'cookie-consent-webpixelstudio' ), __( 'Zobrazí banner znova, keď sa zmení zoznam cookies.', 'cookie-consent-webpixelstudio' ), $s->get( 'reconsent' ), __( '💡 Odporúčame zapnúť – GDPR vyžaduje nový súhlas po zmene účelu spracovania.', 'cookie-consent-webpixelstudio' ) ); ?>
				<?php $this->trow_toggle( 'record_consents', __( 'Zaznamenávať súhlasy', 'cookie-consent-webpixelstudio' ), __( 'Každý súhlas sa uloží do databázy (ID, IP, URL, čas).', 'cookie-consent-webpixelstudio' ), $s->get( 'record_consents' ), __( '💡 Odporúčame zapnúť – záznamy slúžia ako dôkaz súhlasu pre GDPR audit.', 'cookie-consent-webpixelstudio' ) ); ?>
				<?php $this->trow_toggle( 'frontend_detect_visitor_language', __( 'Jazyk podľa návštevníka', 'cookie-consent-webpixelstudio' ), __( 'Frontend cookie lišta sa pokúsi načítať jazyk návštevníka a zobraziť zodpovedajúci dostupný preklad.', 'cookie-consent-webpixelstudio' ), $s->get( 'frontend_detect_visitor_language' ), __( '💡 Ak jazyk návštevníka nie je dostupný, plugin použije English (GB). Ak je voľba vypnutá, frontend používa aktuálne uložené texty bannera.', 'cookie-consent-webpixelstudio' ) ); ?>
				<?php $this->trow_toggle( 'hide_empty_categories', __( 'Skryť prázdne kategórie', 'cookie-consent-webpixelstudio' ), __( 'V modáli preferencií sa skryjú kategórie bez deklarovaných cookies.', 'cookie-consent-webpixelstudio' ), $s->get( 'hide_empty_categories' ), __( '💡 Zapnite, keď máte vyplnené všetky kategórie v záložke Cookies.', 'cookie-consent-webpixelstudio' ) ); ?>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->t( 'admin_sect_cookie_settings', 'Nastavenia cookie súhlasu' ) ); ?></h2>
			<table class="ccwps-table">
				<tr>
					<th><label for="delay"><?php esc_html_e( 'Oneskorenie zobrazenia (ms)', 'cookie-consent-webpixelstudio' ); ?></label><p class="desc"><?php esc_html_e( 'Čas v milisekundách pred zobrazením bannera.', 'cookie-consent-webpixelstudio' ); ?></p></th>
					<td><input type="number" name="delay" id="delay" value="<?php echo esc_attr( $s->get( 'delay' ) ); ?>" min="0" class="small-text"><?php $this->tip( __( '💡 Hodnota 0 = okamžite. Odporúčame 0–500 ms. Vyššie hodnoty môžu byť problém pri GDPR auditoch.', 'cookie-consent-webpixelstudio' ) ); ?></td>
				</tr>
				<tr>
					<th><label for="cookie_expiration"><?php esc_html_e( 'Platnosť súhlasu (dni)', 'cookie-consent-webpixelstudio' ); ?></label><p class="desc"><?php esc_html_e( 'Po koľkých dňoch vyprší súhlas a banner sa zobrazí znova.', 'cookie-consent-webpixelstudio' ); ?></p></th>
					<td><input type="number" name="cookie_expiration" id="cookie_expiration" value="<?php echo esc_attr( $s->get( 'cookie_expiration' ) ); ?>" min="1" class="small-text"><?php $this->tip( __( '💡 GDPR odporúča max. 13 mesiacov (390 dní). Predvolených 182 dní (6 mes.) je bezpečná hodnota.', 'cookie-consent-webpixelstudio' ) ); ?></td>
				</tr>
				<tr>
					<th><label for="cookie_path"><?php esc_html_e( 'Cesta cookie', 'cookie-consent-webpixelstudio' ); ?></label><p class="desc"><?php esc_html_e( 'Cesta, na ktorej bude cookie platná.', 'cookie-consent-webpixelstudio' ); ?></p></th>
					<td><input type="text" name="cookie_path" id="cookie_path" value="<?php echo esc_attr( $s->get( 'cookie_path' ) ); ?>" class="regular-text"><?php $this->tip( __( '💡 Nechajte "/" – cookie bude platná na celom webe.', 'cookie-consent-webpixelstudio' ) ); ?></td>
				</tr>
				<tr>
					<th><label for="cookie_domain"><?php esc_html_e( 'Doména cookie', 'cookie-consent-webpixelstudio' ); ?></label><p class="desc"><?php esc_html_e( 'Nechajte prázdne pre automatickú detekciu.', 'cookie-consent-webpixelstudio' ); ?></p></th>
					<td><input type="text" name="cookie_domain" id="cookie_domain" value="<?php echo esc_attr( $s->get( 'cookie_domain' ) ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $this->get_home_url_host() ); ?>"><?php $this->tip( __( '💡 Vyplňte ".mojeweb.sk" (s bodkou) ak chcete zdieľať súhlas medzi subdoménami.', 'cookie-consent-webpixelstudio' ) ); ?></td>
				</tr>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Google Consent Mode', 'cookie-consent-webpixelstudio' ); ?></h2>
			<div class="ccwps-info-box">
				<strong><?php esc_html_e( 'Čo je Consent Mode?', 'cookie-consent-webpixelstudio' ); ?></strong>
				<?php esc_html_e( ' Google Consent Mode umožňuje Googlu modelovať chýbajúce dáta, keď používatelia odmietnu analytické/reklamné cookies. Vaše kampane budú stále merateľné aj bez plného súhlasu.', 'cookie-consent-webpixelstudio' ); ?>
			</div>
			<table class="ccwps-table">
				<tr>
					<th><?php esc_html_e( 'Verzia Consent Mode', 'cookie-consent-webpixelstudio' ); ?></th>
					<td>
						<div class="ccwps-radio-group">
							<?php
							$radio_opts = [
								'off' => [ __( 'Vypnuté', 'cookie-consent-webpixelstudio' ), __( 'Consent Mode sa nepoužíva. Vhodné ak nepoužívate Google Analytics ani Ads.', 'cookie-consent-webpixelstudio' ), '' ],
								'v2'  => [ __( 'Consent Mode v2', 'cookie-consent-webpixelstudio' ), __( 'Štandard od marca 2024. Vyžadovaný pre Google Ads a GA4 v EÚ. Posiela signály ad_user_data a ad_personalization. Nutné pre remarketing a konverzie.', 'cookie-consent-webpixelstudio' ), 'recommended' ],
								'v3'  => [ __( 'Consent Mode v3', 'cookie-consent-webpixelstudio' ), __( 'Rozšírenie v2 o developer ID signál. Zahŕňa všetky funkcie v2 + nastavenie developer_id pre presnejšie modelovanie konverzií. Vhodné pre pokročilé Google Marketing Platform funkcie.', 'cookie-consent-webpixelstudio' ), 'new' ],
							];
							foreach ( $radio_opts as $val => [ $label, $desc, $badge ] ) :
								$selected = $mode === $val ? 'selected' : '';
							?>
							<label class="ccwps-radio-option <?php echo esc_attr( $selected ); ?>">
								<input type="radio" name="consent_mode_version" value="<?php echo esc_attr( $val ); ?>" <?php checked( $mode, $val ); ?>>
								<div>
									<strong>
										<?php echo esc_html( $label ); ?>
										<?php if ( $badge === 'recommended' ) : ?><span class="ccwps-badge-recommended"><?php esc_html_e( 'Odporúčané', 'cookie-consent-webpixelstudio' ); ?></span><?php endif; ?>
										<?php if ( $badge === 'new' ) : ?><span class="ccwps-badge-new"><?php esc_html_e( 'Nové', 'cookie-consent-webpixelstudio' ); ?></span><?php endif; ?>
									</strong>
									<span><?php echo esc_html( $desc ); ?></span>
								</div>
							</label>
							<?php endforeach; ?>
						</div>
						<?php $this->tip( __( '💡 Pre väčšinu webov stačí v2. v3 použite ak využívate pokročilé Google Marketing Platform funkcie. Obe verzie vyžadujú GTM alebo manuálne načítanie gtag.js.', 'cookie-consent-webpixelstudio' ) ); ?>
					</td>
				</tr>
				<tr>
					<th><label for="gtm_id"><?php esc_html_e( 'GTM Container ID', 'cookie-consent-webpixelstudio' ); ?></label><p class="desc"><?php esc_html_e( 'Voliteľné: Plugin načíta GTM automaticky.', 'cookie-consent-webpixelstudio' ); ?></p></th>
					<td><input type="text" name="gtm_id" id="gtm_id" value="<?php echo esc_attr( $s->get( 'gtm_id' ) ); ?>" class="regular-text" placeholder="GTM-XXXXXXX"><?php $this->tip( __( '💡 Vyplňte len ak GTM nenačítavate iným spôsobom. Ak GTM máte v téme alebo inom plugine, nechajte prázdne.', 'cookie-consent-webpixelstudio' ) ); ?></td>
				</tr>
			</table>
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
		$font_choices = $this->get_font_family_choices();
		$current_font = (string) $s->get( 'font_family' );

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
					<th><label><?php esc_html_e( 'Typ bannera', 'cookie-consent-webpixelstudio' ); ?></label></th>
					<td>
						<div class="ccwps-layout-picker">
							<?php foreach ( [ 'box' => '⬜ Box', 'bar' => '▬ Bar', 'cloud' => '☁️ Cloud' ] as $v => $l ) : ?>
							<label class="ccwps-layout-opt <?php echo $s->get( 'banner_layout' ) === $v ? 'active' : ''; ?>">
								<input type="radio" name="banner_layout" value="<?php echo esc_attr( $v ); ?>" <?php checked( $s->get( 'banner_layout' ), $v ); ?>>
								<?php echo esc_html( $l ); ?>
							</label>
							<?php endforeach; ?>
						</div>
						<?php $this->tip( __( '💡 Box – kompaktný popup v rohu. Bar – horizontálny pruh cez celú šírku. Cloud – zaoblený popup s väčším tieňom.', 'cookie-consent-webpixelstudio' ) ); ?>
					</td>
				</tr>
				<tr>
					<th><label for="banner_position"><?php esc_html_e( 'Poloha bannera', 'cookie-consent-webpixelstudio' ); ?></label></th>
					<td>
						<select name="banner_position" id="banner_position">
							<?php foreach ( [
								'bottom-left'   => __( 'Dole vľavo', 'cookie-consent-webpixelstudio' ),
								'bottom-right'  => __( 'Dole vpravo', 'cookie-consent-webpixelstudio' ),
								'bottom-center' => __( 'Dole v strede', 'cookie-consent-webpixelstudio' ),
								'top-left'      => __( 'Hore vľavo', 'cookie-consent-webpixelstudio' ),
								'top-right'     => __( 'Hore vpravo', 'cookie-consent-webpixelstudio' ),
								'top-center'    => __( 'Hore v strede', 'cookie-consent-webpixelstudio' ),
								'middle-center' => __( 'Stred obrazovky', 'cookie-consent-webpixelstudio' ),
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
				<?php $this->trow_toggle( 'banner_show_icon', __( 'Zobraziť plávajúcu ikonu', 'cookie-consent-webpixelstudio' ), __( 'Po udelení súhlasu sa zobrazí ikona na opätovnú správu preferencií.', 'cookie-consent-webpixelstudio' ), $s->get( 'banner_show_icon' ), __( '💡 Odporúčame zapnúť – GDPR vyžaduje, aby mohol používateľ kedykoľvek odvolať súhlas. Po kliknutí na ikonu sa zobrazí panel s ID súhlasu a tlačidlami.', 'cookie-consent-webpixelstudio' ) ); ?>
				<tr>
					<th><label for="icon_position"><?php esc_html_e( 'Poloha ikony', 'cookie-consent-webpixelstudio' ); ?></label></th>
					<td>
						<select name="icon_position" id="icon_position">
							<?php foreach ( [ 'bottom-right' => __( 'Dole vpravo', 'cookie-consent-webpixelstudio' ), 'bottom-left' => __( 'Dole vľavo', 'cookie-consent-webpixelstudio' ), 'top-right' => __( 'Hore vpravo', 'cookie-consent-webpixelstudio' ), 'top-left' => __( 'Hore vľavo', 'cookie-consent-webpixelstudio' ) ] as $v => $l ) : ?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $s->get( 'icon_position' ), $v ); ?>><?php echo esc_html( $l ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="icon_type"><?php esc_html_e( 'Typ ikony', 'cookie-consent-webpixelstudio' ); ?></label></th>
					<td>
						<select name="icon_type" id="icon_type" class="ccwps-icon-type-select">
							<?php foreach ( [
								'cookie'  => '🍪 Cookie',
								'shield'  => '🛡 Štít',
								'settings'=> '⚙️ Nastavenia',
								'lock'    => '🔒 Zámok',
								'custom'  => __( '🖼 Vlastná ikona (obrázok)', 'cookie-consent-webpixelstudio' ),
							] as $v => $l ) : ?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $s->get( 'icon_type' ), $v ); ?>><?php echo esc_html( $l ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr id="ccwps-custom-icon-row" style="<?php echo $s->get( 'icon_type' ) === 'custom' ? '' : 'display:none;'; ?>">
					<th>
						<label><?php esc_html_e( 'Vlastná ikona', 'cookie-consent-webpixelstudio' ); ?></label>
						<p class="desc"><?php esc_html_e( 'Nahrajte alebo vyberte obrázok z knižnice médií. Odporúčaná veľkosť: 50×50 px, formát PNG alebo SVG s priehľadným pozadím.', 'cookie-consent-webpixelstudio' ); ?></p>
					</th>
					<td>
						<div class="ccwps-icon-upload-wrap">
							<?php $custom_url = $s->get( 'icon_custom_url' ); ?>
							<?php if ( $custom_url ) : ?>
								<div class="ccwps-icon-preview" id="ccwps-icon-preview">
									<img src="<?php echo esc_url( $custom_url ); ?>" alt="<?php esc_attr_e( 'Vlastná ikona', 'cookie-consent-webpixelstudio' ); ?>">
								</div>
							<?php else : ?>
								<div class="ccwps-icon-preview ccwps-icon-preview--empty" id="ccwps-icon-preview">
									<span><?php esc_html_e( 'Žiadny obrázok', 'cookie-consent-webpixelstudio' ); ?></span>
								</div>
							<?php endif; ?>
							<input type="hidden" name="icon_custom_url" id="icon_custom_url" value="<?php echo esc_url( $custom_url ); ?>">
							<div class="ccwps-icon-upload-btns">
								<button type="button" class="button" id="ccwps-icon-upload-btn">
									<?php esc_html_e( '📂 Vybrať / nahrať obrázok', 'cookie-consent-webpixelstudio' ); ?>
								</button>
								<?php if ( $custom_url ) : ?>
									<button type="button" class="button button-link-delete" id="ccwps-icon-remove-btn">
										<?php esc_html_e( 'Odstrániť', 'cookie-consent-webpixelstudio' ); ?>
									</button>
								<?php else : ?>
									<button type="button" class="button button-link-delete" id="ccwps-icon-remove-btn" style="display:none;">
										<?php esc_html_e( 'Odstrániť', 'cookie-consent-webpixelstudio' ); ?>
									</button>
								<?php endif; ?>
							</div>
						</div>
						<?php $this->tip( __( '💡 Ikona sa zobrazí v kruhu s farbou pozadia (primárna farba). Pre najlepší výsledok použite biely alebo priehľadný obrázok 50×50 px.', 'cookie-consent-webpixelstudio' ) ); ?>
					</td>
				</tr>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php echo esc_html( $this->t( 'admin_sect_typography', 'Typografia a zaoblenie' ) ); ?></h2>
			<table class="ccwps-table">
				<tr>
					<th><label for="font_family"><?php esc_html_e( 'Font', 'cookie-consent-webpixelstudio' ); ?></label><p class="desc"><?php esc_html_e( 'CSS font-family hodnota', 'cookie-consent-webpixelstudio' ); ?></p></th>
					<td>
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
						<p class="description" style="margin-top:8px;"><?php esc_html_e( 'Pole ponúka iba detegované fonty z témy, theme.json, Elementora a frontend CSS.', 'cookie-consent-webpixelstudio' ); ?></p>
						<?php $this->tip( __( '💡 V zozname sa zobrazuje iba názov fontu. Po výbere sa uloží kompletná font-family hodnota detegovaná na stránke. Voľba "Použiť font témy" zodpovedá hodnote inherit.', 'cookie-consent-webpixelstudio' ) ); ?>
					</td>
				</tr>
				<tr>
					<th><label for="btn_border_radius"><?php esc_html_e( 'Zaoblenie tlačidiel (px)', 'cookie-consent-webpixelstudio' ); ?></label></th>
					<td><input type="number" name="btn_border_radius" id="btn_border_radius" value="<?php echo esc_attr( $s->get( 'btn_border_radius' ) ); ?>" min="0" max="50" class="small-text"><?php $this->tip( __( '💡 0 = hranaté, 8 = mierne zaoblené, 24+ = "pill" tvar.', 'cookie-consent-webpixelstudio' ) ); ?></td>
				</tr>
				<tr>
					<th><label for="banner_border_radius"><?php esc_html_e( 'Zaoblenie bannera / modálu (px)', 'cookie-consent-webpixelstudio' ); ?></label></th>
					<td><input type="number" name="banner_border_radius" id="banner_border_radius" value="<?php echo esc_attr( $s->get( 'banner_border_radius' ) ?: 12 ); ?>" min="0" max="40" class="small-text"><?php $this->tip( __( '💡 Zaoblenie rohov bannera aj modálu. Predvolené: 12 px.', 'cookie-consent-webpixelstudio' ) ); ?></td>
				</tr>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Farby bannera', 'cookie-consent-webpixelstudio' ); ?></h2>
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Nastavte farby pre pozadie a text cookie lišty (bannera).', 'cookie-consent-webpixelstudio' ); ?></p>
			<table class="ccwps-table">
				<?php foreach ( [
					'bg_color'    => [ __( 'Pozadie bannera', 'cookie-consent-webpixelstudio' ),    __( 'Farba pozadia cookie lišty.', 'cookie-consent-webpixelstudio' ), '#ffffff' ],
					'text_color'  => [ __( 'Farba textu bannera', 'cookie-consent-webpixelstudio' ), __( 'Farba nadpisu a popisu v banneri.', 'cookie-consent-webpixelstudio' ), '#111827' ],
					'primary_color' => [ __( 'Primárna / akcentová farba', 'cookie-consent-webpixelstudio' ), __( 'Používa sa ako fallback farba pre tlačidlá a zvýraznenia.', 'cookie-consent-webpixelstudio' ), '#1a73e8' ],
				] as $k => [ $lbl, $desc, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label><p class="desc"><?php echo esc_html( $desc ); ?></p></th>
					<td><input type="text" name="<?php echo esc_attr( $k ); ?>" id="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $s->get( $k ) ?: $def ); ?>" class="ccwps-color-picker"></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Tlačidlo "Prijať všetky" (primárne)', 'cookie-consent-webpixelstudio' ); ?></h2>
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Farebné nastavenia pre hlavné modré tlačidlo súhlasu. Prázdne pole = použije sa primárna farba.', 'cookie-consent-webpixelstudio' ); ?></p>
			<table class="ccwps-table">
				<?php
				$btn_primary = [
					'btn_primary_bg'     => [ __( 'Pozadie', 'cookie-consent-webpixelstudio' ),           '#1a73e8' ],
					'btn_primary_bg_hv'  => [ __( 'Pozadie (hover)', 'cookie-consent-webpixelstudio' ),    '#1557b0' ],
					'btn_primary_txt'    => [ __( 'Farba textu', 'cookie-consent-webpixelstudio' ),         '#ffffff' ],
					'btn_text_color'     => [ __( 'Farba textu (hover)', 'cookie-consent-webpixelstudio' ), '#ffffff' ],
				];
				foreach ( $btn_primary as $k => [ $lbl, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label></th>
					<td><input type="text" name="<?php echo esc_attr( $k ); ?>" id="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $s->get( $k ) ?: $def ); ?>" class="ccwps-color-picker"></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Tlačidlo "Odmietnuť" (ghost)', 'cookie-consent-webpixelstudio' ); ?></h2>
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Farebné nastavenia pre tlačidlo odmietnutia.', 'cookie-consent-webpixelstudio' ); ?></p>
			<table class="ccwps-table">
				<?php
				$btn_ghost = [
					'btn_ghost_bg'     => [ __( 'Pozadie', 'cookie-consent-webpixelstudio' ),          '#f0f2f5' ],
					'btn_ghost_bg_hv'  => [ __( 'Pozadie (hover)', 'cookie-consent-webpixelstudio' ),  '#e5e7eb' ],
					'btn_ghost_txt'    => [ __( 'Farba textu', 'cookie-consent-webpixelstudio' ),        '#111827' ],
					'btn_ghost_border' => [ __( 'Farba ohraničenia', 'cookie-consent-webpixelstudio' ),  '#e5e7eb' ],
				];
				foreach ( $btn_ghost as $k => [ $lbl, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label></th>
					<td><input type="text" name="<?php echo esc_attr( $k ); ?>" id="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $s->get( $k ) ?: $def ); ?>" class="ccwps-color-picker"></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Tlačidlo "Spravovať nastavenia" (outline)', 'cookie-consent-webpixelstudio' ); ?></h2>
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Farebné nastavenia pre tlačidlo správy preferencií.', 'cookie-consent-webpixelstudio' ); ?></p>
			<table class="ccwps-table">
				<?php
				$btn_outline = [
					'btn_outline_bg'     => [ __( 'Pozadie', 'cookie-consent-webpixelstudio' ),           'transparent' ],
					'btn_outline_bg_hv'  => [ __( 'Pozadie (hover)', 'cookie-consent-webpixelstudio' ),   '#1a73e8' ],
					'btn_outline_txt'    => [ __( 'Farba textu', 'cookie-consent-webpixelstudio' ),         '#1a73e8' ],
					'btn_outline_border' => [ __( 'Farba ohraničenia', 'cookie-consent-webpixelstudio' ),   '#1a73e8' ],
				];
				foreach ( $btn_outline as $k => [ $lbl, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label></th>
					<td>
						<?php if ( $k === 'btn_outline_bg' ) : ?>
							<div class="ccwps-color-with-transparent">
								<input type="text" name="<?php echo esc_attr( $k ); ?>" id="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $s->get( $k ) ?: $def ); ?>" class="ccwps-color-picker">
								<label style="margin-left:8px;font-size:12px;color:#6b7280;"><input type="checkbox" class="ccwps-transparent-check" data-target="<?php echo esc_attr( $k ); ?>" <?php checked( !$s->get( $k ) || $s->get( $k ) === 'transparent', true ); ?>> <?php esc_html_e( 'Priehľadné', 'cookie-consent-webpixelstudio' ); ?></label>
							</div>
						<?php else : ?>
							<input type="text" name="<?php echo esc_attr( $k ); ?>" id="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $s->get( $k ) ?: $def ); ?>" class="ccwps-color-picker">
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Okno modálu (Spravovať súhlas)', 'cookie-consent-webpixelstudio' ); ?></h2>
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Farby pre okno nastavenia cookies, ktoré sa otvorí po kliknutí na "Spravovať nastavenia".', 'cookie-consent-webpixelstudio' ); ?></p>
			<table class="ccwps-table">
				<?php
				$modal_colors = [
					'modal_bg'         => [ __( 'Pozadie modálu', 'cookie-consent-webpixelstudio' ),          '#ffffff' ],
					'modal_text'       => [ __( 'Farba textu', 'cookie-consent-webpixelstudio' ),               '#111827' ],
					'modal_header_bg'  => [ __( 'Pozadie hlavičky', 'cookie-consent-webpixelstudio' ),         '#ffffff' ],
					'modal_footer_bg'  => [ __( 'Pozadie päty', 'cookie-consent-webpixelstudio' ),             '#f9fafb' ],
					'modal_border'     => [ __( 'Farba ohraničení', 'cookie-consent-webpixelstudio' ),          '#e5e7eb' ],
					'cat_header_bg'    => [ __( 'Pozadie kategórie', 'cookie-consent-webpixelstudio' ),         '#f9fafb' ],
					'cat_header_bg_hv' => [ __( 'Pozadie kategórie (hover)', 'cookie-consent-webpixelstudio' ),'#f0f2f5' ],
					'toggle_on_color'  => [ __( 'Farba prepínača (zapnutý)', 'cookie-consent-webpixelstudio' ), '#1a73e8' ],
					'always_on_color'  => [ __( 'Farba textu "Vždy aktívne"', 'cookie-consent-webpixelstudio' ),'#1a73e8' ],
				];
				foreach ( $modal_colors as $k => [ $lbl, $def ] ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></label></th>
					<td><input type="text" name="<?php echo esc_attr( $k ); ?>" id="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $s->get( $k ) ?: $def ); ?>" class="ccwps-color-picker"></td>
				</tr>
				<?php endforeach; ?>
				<tr>
					<th><label for="modal_border_radius"><?php esc_html_e( 'Zaoblenie modálu (px)', 'cookie-consent-webpixelstudio' ); ?></label></th>
					<td><input type="number" name="modal_border_radius" id="modal_border_radius" value="<?php echo esc_attr( $s->get( 'modal_border_radius' ) ?: 12 ); ?>" min="0" max="40" class="small-text"></td>
				</tr>
			</table>
		</div>

		<div class="ccwps-form-actions">
			<button type="button" class="button button-primary ccwps-save-settings ccwps-btn-primary-action"><?php echo esc_html( $this->t( 'admin_btn_save_appearance', 'Uložiť vzhľad' ) ); ?></button>
		</div>
		</form>
		<?php
	}

	/* ================================================
	   TRANSLATIONS TAB
	   ================================================ */
	private function tab_translations(): void {
		$s = $this->settings;
		$fields = [
			__( 'Banner', 'cookie-consent-webpixelstudio' ) => [
				'lang_banner_title'       => $this->t( 'admin_trans_banner_title', 'Nadpis bannera' ),
				'lang_banner_description' => $this->t( 'admin_trans_banner_desc', 'Popis bannera' ),
				'lang_accept_all'         => $this->t( 'admin_trans_accept_all', 'Tlačidlo "Prijať všetky"' ),
				'lang_reject_all'         => $this->t( 'admin_trans_reject_all', 'Tlačidlo "Odmietnuť všetky"' ),
				'lang_manage_preferences' => $this->t( 'admin_trans_manage_pref', 'Tlačidlo "Spravovať nastavenia"' ),
				'lang_powered_by'         => $this->t( 'admin_trans_powered_by', 'Text "Powered by"' ),
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
			<p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Kliknite na jazyk – všetky polia sa vyplnia automaticky. Potom kliknite Uložiť.', 'cookie-consent-webpixelstudio' ); ?></p>
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
				<button type="button" class="button button-primary ccwps-btn-primary-action" id="ccwps-add-cookie">+ <?php echo esc_html( $this->t( 'admin_btn_add_cookie', 'Pridať cookie' ) ); ?></button>
				<button type="button" class="button ccwps-btn-secondary-action" id="ccwps-preview-cookie-list">📋 <?php echo esc_html( $this->t( 'admin_btn_show_list', 'Zobraziť zoznam' ) ); ?></button>
			</div>
		</div>
		<div class="ccwps-card">
			<p class="description" style="margin-bottom:14px;"><?php esc_html_e( 'Popis cookie je viditeľný návštevníkom v modáli preferencií – pomáha im pochopiť, na čo daná cookie slúži.', 'cookie-consent-webpixelstudio' ); ?></p>
			<div class="ccwps-table-scroll">
				<table class="wp-list-table widefat striped ccwps-data-table">
					<thead><tr>
						<th><?php echo esc_html( $this->t( 'admin_col_name', 'Názov' ) ); ?></th>
						<th><?php echo esc_html( $this->t( 'admin_col_domain', 'Doména' ) ); ?></th>
						<th><?php echo esc_html( $this->t( 'admin_col_expiry', 'Platnosť' ) ); ?></th>
						<th><?php echo esc_html( $this->t( 'admin_col_description', 'Popis' ) ); ?> <span class="ccwps-tag-info"><?php esc_html_e( 'viditeľný návštevníkom', 'cookie-consent-webpixelstudio' ); ?></span></th>
						<th><?php echo esc_html( $this->t( 'admin_col_category', 'Kategória' ) ); ?></th>
						<th><?php esc_html_e( 'Regex', 'cookie-consent-webpixelstudio' ); ?></th>
						<th><?php echo esc_html( $this->t( 'admin_col_actions', 'Akcie' ) ); ?></th>
					</tr></thead>
					<tbody>
					<?php if ( empty( $cookies ) ) : ?>
						<tr><td colspan="7" style="text-align:center;padding:24px;"><?php esc_html_e( 'Žiadne cookies. Kliknite "+ Pridať cookie".', 'cookie-consent-webpixelstudio' ); ?></td></tr>
					<?php else : foreach ( $cookies as $c ) : ?>
						<tr>
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
						<tr><th><label for="c-preset"><?php echo esc_html( $this->tx( 'Predpripravené predvoľby' ) ); ?></label></th><td><div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;"><select id="c-preset" style="min-width:260px;"><option value=""><?php echo esc_html( $this->tx( 'Vyberte predvoľbu' ) ); ?></option><option value="google_necessary"><?php echo esc_html( $this->tx( 'Google Necessary' ) ); ?></option><option value="google_analytics"><?php echo esc_html( $this->tx( 'Google Analytics' ) ); ?></option><option value="google_ads"><?php echo esc_html( $this->tx( 'Google Ads' ) ); ?></option><option value="facebook_pixel"><?php echo esc_html( $this->tx( 'Facebook Pixel' ) ); ?></option></select><button type="button" class="button button-primary ccwps-btn-primary-action" id="ccwps-apply-cookie-preset"><?php echo esc_html( $this->tx( 'Použiť predvoľbu' ) ); ?></button></div></td></tr>
						<tr><th><label for="c-name"><?php esc_html_e( 'Názov', 'cookie-consent-webpixelstudio' ); ?> <span class="required">*</span></label></th><td><input type="text" id="c-name" class="regular-text" placeholder="napr. _ga"></td></tr>
						<tr><th><label for="c-domain"><?php esc_html_e( 'Doména', 'cookie-consent-webpixelstudio' ); ?></label></th><td><input type="text" id="c-domain" class="regular-text" placeholder="<?php echo esc_attr( $this->get_home_url_host() ); ?>"></td></tr>
						<tr><th><label for="c-expiration"><?php esc_html_e( 'Platnosť', 'cookie-consent-webpixelstudio' ); ?></label></th><td><input type="text" id="c-expiration" class="regular-text" placeholder="napr. 2 roky, Relácia"></td></tr>
						<tr><th><label for="c-path"><?php esc_html_e( 'Cesta', 'cookie-consent-webpixelstudio' ); ?></label></th><td><input type="text" id="c-path" class="regular-text" value="/"></td></tr>
						<tr><th><label for="c-description"><?php esc_html_e( 'Popis', 'cookie-consent-webpixelstudio' ); ?></label><p class="desc"><?php esc_html_e( 'Viditeľný návštevníkom v modáli preferencií.', 'cookie-consent-webpixelstudio' ); ?></p></th><td><textarea id="c-description" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'napr. Používa sa Google Analytics na rozlíšenie návštevníkov.', 'cookie-consent-webpixelstudio' ); ?>"></textarea></td></tr>
						<tr><th><label for="c-category"><?php esc_html_e( 'Kategória', 'cookie-consent-webpixelstudio' ); ?></label></th><td><select id="c-category"><?php foreach ( $categories as $cat ) : ?><option value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( ucfirst( $cat ) ); ?></option><?php endforeach; ?></select></td></tr>
						<tr><th><label for="c-is-regex"><?php esc_html_e( 'Je Regex?', 'cookie-consent-webpixelstudio' ); ?></label></th><td><label class="ccwps-toggle"><input type="checkbox" id="c-is-regex"><span class="ccwps-toggle-slider"></span></label></td></tr>
					</table>
				</div>
				<div class="ccwps-modal-footer"><button type="button" class="button ccwps-modal-close ccwps-btn-secondary-action"><?php echo esc_html( $this->t( 'admin_btn_cancel', 'Zrušiť' ) ); ?></button><button type="button" class="button button-primary ccwps-btn-primary-action" id="ccwps-save-cookie"><?php echo esc_html( $this->t( 'admin_btn_save', 'Uložiť' ) ); ?></button></div>
			</div>
		</div>

		<!-- Cookie list preview modal -->
		<div id="ccwps-cookie-list-modal" class="ccwps-modal" style="display:none;">
			<div class="ccwps-modal-inner" style="width:720px;max-width:95vw;">
				<div class="ccwps-modal-header"><h3><?php esc_html_e( 'Náhľad zoznamu cookies', 'cookie-consent-webpixelstudio' ); ?></h3><button type="button" class="ccwps-modal-close">×</button></div>
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
			<div class="ccwps-info-box"><?php esc_html_e( 'Definujte zdroje skriptov, ktoré sa majú blokovať, kým používateľ neudelí súhlas pre danú kategóriu. Plugin zmení typ skriptu na text/plain, čo zabrání jeho spusteniu.', 'cookie-consent-webpixelstudio' ); ?></div>
			<table class="wp-list-table widefat striped ccwps-data-table">
				<thead><tr><th><?php echo esc_html( $this->t( 'admin_col_script_source', 'Zdroj skriptu' ) ); ?></th><th style="width:130px"><?php echo esc_html( $this->t( 'admin_col_category', 'Kategória' ) ); ?></th><th style="width:70px"><?php esc_html_e( 'Regex', 'cookie-consent-webpixelstudio' ); ?></th><th style="width:120px"><?php echo esc_html( $this->t( 'admin_col_actions', 'Akcie' ) ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $rules ) ) : ?>
					<tr><td colspan="4" style="text-align:center;padding:24px;"><?php esc_html_e( 'Žiadne pravidlá blokovania.', 'cookie-consent-webpixelstudio' ); ?></td></tr>
				<?php else : foreach ( $rules as $r ) : ?>
					<tr><td><code><?php echo esc_html( $r['script_source'] ); ?></code></td><td><span class="ccwps-badge ccwps-badge-<?php echo esc_attr( $r['category'] ); ?>"><?php echo esc_html( ucfirst( $r['category'] ) ); ?></span></td><td><?php echo $r['is_regex'] ? '<span class="ccwps-badge ccwps-badge-regex">Regex</span>' : '—'; ?></td><td><button class="button button-small ccwps-edit-block" data-row='<?php echo esc_attr( wp_json_encode( $r ) ); ?>'><?php echo esc_html( $this->t( 'admin_btn_edit', 'Upraviť' ) ); ?></button> <button class="button button-small button-link-delete ccwps-delete-block" data-id="<?php echo esc_attr( $r['id'] ); ?>"><?php echo esc_html( $this->t( 'admin_btn_delete', 'Zmazať' ) ); ?></button></td></tr>
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
						<tr><th><label for="b-preset"><?php echo esc_html( $this->tx( 'Predpripravené predvoľby' ) ); ?></label></th><td><div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;"><select id="b-preset" style="min-width:260px;"><option value=""><?php echo esc_html( $this->tx( 'Vyberte predvoľbu' ) ); ?></option><option value="ga"><?php echo esc_html( $this->tx( 'Google Analytics' ) ); ?></option><option value="gtm"><?php echo esc_html( $this->tx( 'Google Tag Manager' ) ); ?></option><option value="gads"><?php echo esc_html( $this->tx( 'Google Ads' ) ); ?></option><option value="fb"><?php echo esc_html( $this->tx( 'Facebook Pixel' ) ); ?></option></select><button type="button" class="button button-primary ccwps-btn-primary-action" id="ccwps-apply-block-preset"><?php echo esc_html( $this->tx( 'Použiť predvoľbu' ) ); ?></button></div></td></tr>
						<tr><th><label for="b-source"><?php esc_html_e( 'Zdroj skriptu', 'cookie-consent-webpixelstudio' ); ?> <span class="required">*</span></label><p class="desc"><?php esc_html_e( 'Fragment URL, napr. "google-analytics.com"', 'cookie-consent-webpixelstudio' ); ?></p></th><td><input type="text" id="b-source" class="large-text" placeholder="napr. google-analytics.com"></td></tr>
						<tr><th><label for="b-category"><?php esc_html_e( 'Kategória', 'cookie-consent-webpixelstudio' ); ?></label></th><td><select id="b-category"><?php foreach ( $categories as $cat ) : ?><option value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( ucfirst( $cat ) ); ?></option><?php endforeach; ?></select></td></tr>
						<tr><th><label for="b-is-regex"><?php esc_html_e( 'Je Regex?', 'cookie-consent-webpixelstudio' ); ?></label></th><td><label class="ccwps-toggle"><input type="checkbox" id="b-is-regex"><span class="ccwps-toggle-slider"></span></label></td></tr>
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
		$per_page = 50;
		$page     = $this->get_query_int( 'log_page', 1 );
		$records  = $this->log->get_all( $per_page, $page );
		$total    = $this->log->count();
		$pages    = (int) ceil( $total / $per_page );
		?>
		<div class="ccwps-page-header">
			<h1><?php echo esc_html( $this->t( 'admin_tab_log_h1', 'Záznamy súhlasov' ) ); ?> <span class="ccwps-count">(<?php echo esc_html( number_format( $total ) ); ?>)</span></h1>
			<div class="ccwps-header-actions">
				<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=ccwps_export_csv&_wpnonce=' . wp_create_nonce( 'ccwps_export_csv' ) ) ); ?>" class="button ccwps-btn-secondary-action"><?php echo esc_html( $this->t( 'admin_btn_export_csv', 'Exportovať CSV' ) ); ?></a>
				<button type="button" class="button button-link-delete ccwps-btn-danger-action" id="ccwps-clear-log"><?php echo esc_html( $this->t( 'admin_btn_clear_log', 'Vymazať všetky záznamy' ) ); ?></button>
			</div>
		</div>
		<div class="ccwps-card">
			<?php if ( empty( $records ) ) : ?>
				<p style="padding:16px;"><?php esc_html_e( 'Žiadne záznamy súhlasov.', 'cookie-consent-webpixelstudio' ); ?></p>
			<?php else : ?>
				<div class="ccwps-table-scroll">
					<table class="wp-list-table widefat striped ccwps-data-table ccwps-log-table">
						<thead><tr>
							<th><?php echo esc_html( $this->t( 'admin_col_date', 'Dátum' ) ); ?></th>
							<th><?php echo esc_html( $this->t( 'admin_col_consent_id', 'ID súhlasu' ) ); ?></th>
							<th>URL</th>
							<th><?php echo esc_html( $this->t( 'admin_col_ip', 'IP adresa' ) ); ?></th>
							<th><?php esc_html_e( 'Nev.', 'cookie-consent-webpixelstudio' ); ?></th>
							<th><?php esc_html_e( 'Anal.', 'cookie-consent-webpixelstudio' ); ?></th>
							<th><?php esc_html_e( 'Market.', 'cookie-consent-webpixelstudio' ); ?></th>
							<th><?php esc_html_e( 'Pref.', 'cookie-consent-webpixelstudio' ); ?></th>
							<th><?php echo esc_html( $this->t( 'admin_col_updated', 'Aktualizácia' ) ); ?></th>
						</tr></thead>
						<tbody>
							<?php foreach ( $records as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['recorded_at'] ); ?></td>
								<td><code style="font-size:10px;"><?php echo esc_html( substr( $row['consent_id'], 0, 14 ) . '…' ); ?></code></td>
								<td class="ccwps-cell-url" title="<?php echo esc_attr( $row['url'] ); ?>"><?php echo esc_html( $row['url'] ); ?></td>
								<td><?php echo esc_html( $row['ip_address'] ); ?></td>
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
				<?php if ( $pages > 1 ) : ?>
				<div class="ccwps-pagination">
					<?php for ( $i = 1; $i <= $pages; $i++ ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ccwps&tab=log&log_page=' . $i ) ); ?>" class="button button-small <?php echo $i === $page ? 'button-primary' : ''; ?>"><?php echo esc_html( $i ); ?></a>
					<?php endfor; ?>
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
		<div class="ccwps-page-header"><h1><?php esc_html_e( 'Shortcodes', 'cookie-consent-webpixelstudio' ); ?></h1></div>
		<div class="ccwps-card">
			<h2><?php esc_html_e( 'ID súhlasu návštevníka', 'cookie-consent-webpixelstudio' ); ?></h2>
			<p><?php esc_html_e( 'Zobrazí jedinečné ID súhlasu aktuálneho návštevníka. Vhodné pre zásady ochrany osobných údajov – umožní používateľovi preukázať, kedy a ako súhlasil.', 'cookie-consent-webpixelstudio' ); ?></p>
			<div class="ccwps-shortcode-box"><code>[ccwps_consent_id]</code><button class="button button-small ccwps-copy-btn" data-copy="[ccwps_consent_id]"><?php echo esc_html( $this->t( 'admin_btn_copy', 'Kopírovať' ) ); ?></button></div>
			<table class="ccwps-sc-params"><thead><tr><th><?php esc_html_e( 'Parameter', 'cookie-consent-webpixelstudio' ); ?></th><th><?php esc_html_e( 'Predvolené', 'cookie-consent-webpixelstudio' ); ?></th><th><?php esc_html_e( 'Popis', 'cookie-consent-webpixelstudio' ); ?></th></tr></thead>
			<tbody>
				<tr><td><code>label</code></td><td><code><?php echo esc_html( $this->tx( (string) $this->settings->get( 'lang_consent_id_label', 'ID vášho súhlasu' ) ) ); ?></code></td><td><?php esc_html_e( 'Text pred ID.', 'cookie-consent-webpixelstudio' ); ?></td></tr>
				<tr><td><code>wrapper</code></td><td><code>p</code></td><td><?php esc_html_e( 'HTML obal (p, div, span…)', 'cookie-consent-webpixelstudio' ); ?></td></tr>
			</tbody></table>
		</div>
		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Zoznam cookies používaných na webe', 'cookie-consent-webpixelstudio' ); ?></h2>
			<p><?php esc_html_e( 'Automaticky zobrazí prehľadnú tabuľku všetkých deklarovaných cookies, rozdelenú podľa kategórií. Vložte na stránku s cookie politikou.', 'cookie-consent-webpixelstudio' ); ?></p>
			<div class="ccwps-shortcode-box"><code>[ccwps_cookie_list]</code><button class="button button-small ccwps-copy-btn" data-copy="[ccwps_cookie_list]"><?php echo esc_html( $this->t( 'admin_btn_copy', 'Kopírovať' ) ); ?></button></div>
			<table class="ccwps-sc-params"><thead><tr><th><?php esc_html_e( 'Parameter', 'cookie-consent-webpixelstudio' ); ?></th><th><?php esc_html_e( 'Predvolené', 'cookie-consent-webpixelstudio' ); ?></th><th><?php esc_html_e( 'Popis', 'cookie-consent-webpixelstudio' ); ?></th></tr></thead>
			<tbody>
				<tr><td><code>category</code></td><td><code>(všetky)</code></td><td><?php esc_html_e( 'Filtruj: necessary, analytics, targeting, preferences', 'cookie-consent-webpixelstudio' ); ?></td></tr>
			</tbody></table>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Tlačidlo správy súhlasu', 'cookie-consent-webpixelstudio' ); ?></h2>
			<p><?php esc_html_e( 'Vloží tlačidlo, ktoré otvorí modál nastavenia cookies. Vhodné pre stránky so zásadami ochrany osobných údajov a právne dokumenty – umožní návštevníkovi kedykoľvek zmeniť preferencie.', 'cookie-consent-webpixelstudio' ); ?></p>
			<div class="ccwps-shortcode-box"><code>[ccwps_manage_consent]</code><button class="button button-small ccwps-copy-btn" data-copy="[ccwps_manage_consent]"><?php echo esc_html( $this->t( 'admin_btn_copy', 'Kopírovať' ) ); ?></button></div>
			<table class="ccwps-sc-params"><thead><tr><th><?php esc_html_e( 'Parameter', 'cookie-consent-webpixelstudio' ); ?></th><th><?php esc_html_e( 'Predvolené', 'cookie-consent-webpixelstudio' ); ?></th><th><?php esc_html_e( 'Popis', 'cookie-consent-webpixelstudio' ); ?></th></tr></thead>
			<tbody>
				<tr><td><code>label</code></td><td><code><?php echo esc_html( $this->tx( (string) $this->settings->get( 'lang_manage_preferences', 'Manage Preferences' ) ) ); ?></code></td><td><?php esc_html_e( 'Text tlačidla (predvolene: text z nastavení Prekladov).', 'cookie-consent-webpixelstudio' ); ?></td></tr>
				<tr><td><code>class</code></td><td><code>ccwps-manage-consent-btn</code></td><td><?php esc_html_e( 'CSS trieda pre vlastné štýlovanie.', 'cookie-consent-webpixelstudio' ); ?></td></tr>
				<tr><td><code>id</code></td><td><code>(prázdne)</code></td><td><?php esc_html_e( 'Voliteľné HTML id atribút.', 'cookie-consent-webpixelstudio' ); ?></td></tr>
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
				<p class="description"><?php esc_html_e( 'Exportuje všetky nastavenia do JSON súboru. Vhodné pre presun medzi servermi alebo zálohu.', 'cookie-consent-webpixelstudio' ); ?></p>
				<div class="ccwps-tool-action"><a href="<?php echo esc_url( $export_url ); ?>" class="button button-primary">⬇ <?php echo esc_html( $this->t( 'admin_btn_export_json', 'Stiahnuť settings.json' ) ); ?></a></div>
			</div>
			<div class="ccwps-card">
				<h2><?php echo esc_html( $this->t( 'admin_sect_import', 'Import nastavení' ) ); ?></h2>
				<p class="description"><?php esc_html_e( 'Nahrajte predtým exportovaný settings.json. Prepisuje aktuálnu konfiguráciu.', 'cookie-consent-webpixelstudio' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'ccwps_import_settings', '_wpnonce_import' ); ?>
					<input type="hidden" name="action" value="ccwps_import_settings">
					<div class="ccwps-file-drop" id="ccwps-file-drop">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
						<span id="ccwps-file-name"><?php esc_html_e( 'Kliknite alebo presuňte settings.json sem', 'cookie-consent-webpixelstudio' ); ?></span>
						<input type="file" id="ccwps-import-file" name="ccwps_import_file" accept=".json,application/json">
					</div>
					<div class="ccwps-tool-action"><button type="submit" class="button button-primary" id="ccwps-import-btn" disabled>⬆ <?php echo esc_html( $this->t( 'admin_btn_import_json', 'Importovať nastavenia' ) ); ?></button></div>
				</form>
			</div>
			<div class="ccwps-card">
				<h2 style="color:#dc2626;"><?php echo esc_html( $this->t( 'admin_sect_reset', 'Reset na predvolené' ) ); ?></h2>
				<p class="description"><?php esc_html_e( 'Resetuje všetky nastavenia na predvolené hodnoty (SK jazyk, predvolený vzhľad). Deklarácie cookies, pravidlá blokovania a záznamy súhlasov nie sú ovplyvnené.', 'cookie-consent-webpixelstudio' ); ?></p>
				<div class="ccwps-tool-action"><button type="button" class="button" id="ccwps-reset-defaults" style="border-color:#dc2626;color:#dc2626;">↺ <?php echo esc_html( $this->t( 'admin_btn_reset', 'Resetovať nastavenia' ) ); ?></button></div>
			</div>
			<div class="ccwps-card">
				<h2><?php echo esc_html( $this->t( 'admin_sect_plugin_info', 'Informácie o plugine' ) ); ?></h2>
				<table class="ccwps-table">
					<tr><th><?php esc_html_e( 'Verzia', 'cookie-consent-webpixelstudio' ); ?></th><td><strong><?php echo esc_html( CCWPS_VERSION ); ?></strong></td></tr>
					<tr><th>WordPress</th><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
					<tr><th>PHP</th><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Záznamy súhlasov', 'cookie-consent-webpixelstudio' ); ?></th><td><?php echo esc_html( number_format( $this->log->count() ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Deklarácie cookies', 'cookie-consent-webpixelstudio' ); ?></th><td><?php echo esc_html( count( $this->cookie_manager->get_all() ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Pravidlá blokovania', 'cookie-consent-webpixelstudio' ); ?></th><td><?php echo esc_html( count( $this->block_manager->get_all() ) ); ?></td></tr>
				</table>
			</div>
		</div>
		<?php
	}

	/* ================================================
	   GTM TEMPLATE TAB
	   ================================================ */
	private function tab_gtm_template(): void {
		$v2_url       = CCWPS_PLUGIN_URL . 'gtm-template/cookie-consent-webpixelstudio.tpl';
		$v3_url       = CCWPS_PLUGIN_URL . 'gtm-template/cookie-consent-webpixelstudio-v3.tpl';
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
		<div class="ccwps-page-header"><h1><?php esc_html_e( 'O plugine Cookie Consent by Web Pixel Studio', 'cookie-consent-webpixelstudio' ); ?></h1></div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Popis pluginu', 'cookie-consent-webpixelstudio' ); ?></h2>
			<p><?php esc_html_e( 'Cookie Consent by Web Pixel Studio je bezplatný WordPress plugin pre správu súhlasov s cookies (GDPR / ePrivacy). Poskytuje plne prispôsobiteľný banner a modál preferencií, zaznamenáva súhlasy pre GDPR audit, blokuje skripty tretích strán a podporuje Google Consent Mode v2 a v3.', 'cookie-consent-webpixelstudio' ); ?></p>
			<p style="margin-top:10px;"><?php esc_html_e( 'Plugin je postavený na knižnici orest bida (cookieconsent) a rozširuje ju o WordPress admin panel, databázové záznamy súhlasov a blokovanie skriptov.', 'cookie-consent-webpixelstudio' ); ?></p>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Hlavné funkcie', 'cookie-consent-webpixelstudio' ); ?></h2>
			<table class="ccwps-table">
				<?php
				$features = [
					[ '🍪', __( 'Cookie banner', 'cookie-consent-webpixelstudio' ),            __( 'Prispôsobiteľný popup/bar/cloud banner s výberom polohy na stránke.', 'cookie-consent-webpixelstudio' ) ],
					[ '⚙️', __( 'Modál preferencií', 'cookie-consent-webpixelstudio' ),         __( 'Používateľ si môže zvoliť, ktoré kategórie cookies povolí (nevyhnutné, analytické, marketingové, preferenčné).', 'cookie-consent-webpixelstudio' ) ],
					[ '📋', __( 'Záznamy súhlasov', 'cookie-consent-webpixelstudio' ),          __( 'Každý súhlas sa uloží do databázy s ID, IP adresou, URL a časovou pečiatkou. Export do CSV.', 'cookie-consent-webpixelstudio' ) ],
					[ '🚫', __( 'Blokovanie skriptov', 'cookie-consent-webpixelstudio' ),       __( 'Skripty tretích strán (napr. Google Analytics, Meta Pixel) sa zablokujú, kým používateľ neudelí súhlas.', 'cookie-consent-webpixelstudio' ) ],
					[ '🧩', __( 'Predpripravené sady cookies a skriptov', 'cookie-consent-webpixelstudio' ), __( 'Hotové predvoľby jedným kliknutím doplnia bežné Google a Meta cookies spolu so súvisiacimi pravidlami blokovania.', 'cookie-consent-webpixelstudio' ) ],
					[ '🌐', __( 'Consent Mode v2 / v3', 'cookie-consent-webpixelstudio' ),      __( 'Automaticky nastaví default denied stav a aktualizuje Google signály po udelení súhlasu.', 'cookie-consent-webpixelstudio' ) ],
					[ '🎨', __( 'Plné prispôsobenie vzhľadu', 'cookie-consent-webpixelstudio' ),__( 'Farby, font, zaoblenie tlačidiel, rozloženie bannera, poloha floating ikony.', 'cookie-consent-webpixelstudio' ) ],
					[ '🌍', __( '9 jazykových predvolieb', 'cookie-consent-webpixelstudio' ),   __( 'SK, EN, CS, DE, FR, ES, PL, HU, IT. Všetky texty sú plne editovateľné z admin panelu.', 'cookie-consent-webpixelstudio' ) ],
					[ '🔗', __( 'Shortcodes', 'cookie-consent-webpixelstudio' ),                __( '[ccwps_consent_id] – zobrazí ID súhlasu návštevníka. [ccwps_cookie_list] – tabuľka deklarovaných cookies. [ccwps_manage_consent] – tlačidlo na správu súhlasu.', 'cookie-consent-webpixelstudio' ) ],
					[ '💾', __( 'Export/Import nastavení', 'cookie-consent-webpixelstudio' ),   __( 'Nastavenia je možné exportovať do JSON a importovať na inom webe.', 'cookie-consent-webpixelstudio' ) ],
					[ '🤖', __( 'Detekcia robotov', 'cookie-consent-webpixelstudio' ),          __( 'Banner sa automaticky skryje pred vyhľadávačmi a crawlermi.', 'cookie-consent-webpixelstudio' ) ],
					[ '🔄', __( 'Opätovný súhlas', 'cookie-consent-webpixelstudio' ),           __( 'Ak sa zmení zoznam cookies, plugin automaticky požiada o nový súhlas.', 'cookie-consent-webpixelstudio' ) ],
					[ '🛡', __( 'Floating ikona', 'cookie-consent-webpixelstudio' ),             __( 'Plávajúca ikona umožní používateľovi kedykoľvek zmeniť preferencie. Po kliknutí zobrazí ID súhlasu a dátum.', 'cookie-consent-webpixelstudio' ) ],
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
			<h2><?php esc_html_e( 'Postup nastavenia', 'cookie-consent-webpixelstudio' ); ?></h2>
			<?php
			$steps = [
				[ '1', __( 'Nastavenia', 'cookie-consent-webpixelstudio' ), __( 'V záložke Nastavenia zapnite Zaznamenávať súhlasy, Opätovný súhlas, Skryť pre robotov a Automatické spustenie. Vyberte verziu Consent Mode (v2 pre väčšinu webov). Ak používate GTM, vyplňte GTM Container ID.', 'cookie-consent-webpixelstudio' ) ],
				[ '2', __( 'Preklady', 'cookie-consent-webpixelstudio' ), __( 'V záložke Preklady vyberte jazyk pomocou rýchlych predvolieb alebo upravte texty manuálne. Kliknite Uložiť preklady.', 'cookie-consent-webpixelstudio' ) ],
				[ '3', __( 'Vzhľad', 'cookie-consent-webpixelstudio' ), __( 'V záložke Vzhľad nastavte farby, rozloženie bannera a polohu plávajúcej ikony. Použite tlačidlá Náhľad na overenie zmien.', 'cookie-consent-webpixelstudio' ) ],
				[ '4', __( 'Deklarácia cookies', 'cookie-consent-webpixelstudio' ), __( 'V záložke Cookies pridajte všetky cookies, ktoré váš web používa. Pre každú cookie vyplňte názov, doménu, platnosť, popis a kategóriu. Popis bude viditeľný návštevníkovi.', 'cookie-consent-webpixelstudio' ) ],
				[ '5', __( 'Blokovanie skriptov', 'cookie-consent-webpixelstudio' ), __( 'V záložke Blokovanie skriptov pridajte pravidlá pre skripty, ktoré chcete blokovať. Napr. pre Google Analytics pridajte "google-analytics.com" → kategória Analytické.', 'cookie-consent-webpixelstudio' ) ],
				[ '6', __( 'Cookie politika', 'cookie-consent-webpixelstudio' ), __( 'Na stránku s Cookie politikou vložte [ccwps_cookie_list] pre automatickú tabuľku cookies, [ccwps_consent_id] pre zobrazenie ID súhlasu a [ccwps_manage_consent] pre tlačidlo zmeny preferencií.', 'cookie-consent-webpixelstudio' ) ],
				[ '7', __( 'Overenie', 'cookie-consent-webpixelstudio' ), __( 'Otvorte web v incognito okne a overte, že sa banner zobrazí, súhlas sa zaznamená v záložke Záznamy súhlasov a floating ikona funguje správne.', 'cookie-consent-webpixelstudio' ) ],
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
			<h2><?php esc_html_e( 'Nastavenie Google Tag Manager + Consent Mode', 'cookie-consent-webpixelstudio' ); ?></h2>
			<div class="ccwps-about-steps">
				<div class="ccwps-about-step">
					<h4><?php esc_html_e( 'Možnosť A: Plugin načíta GTM automaticky', 'cookie-consent-webpixelstudio' ); ?></h4>
					<p><?php esc_html_e( 'V záložke Nastavenia vyplňte GTM Container ID (napr. GTM-XXXXXXX). Plugin automaticky vloží GTM kód do hlavičky stránky spolu s Consent Mode default stavom (všetky signály = denied). Po udelení súhlasu sa GTM aktualizuje pomocou gtag(\'consent\', \'update\', ...).', 'cookie-consent-webpixelstudio' ); ?></p>
				</div>
				<div class="ccwps-about-step">
					<h4><?php esc_html_e( 'Možnosť B: GTM máte nainštalovaný inak (téma, iný plugin)', 'cookie-consent-webpixelstudio' ); ?></h4>
					<p><?php esc_html_e( 'Nechajte GTM Container ID prázdne. Plugin automaticky vloží Consent Mode default stav pred GTM kód, takže default denied signály sa nastavia skôr, ako GTM spustí akékoľvek tagy. Toto je správne správanie podľa Google dokumentácie.', 'cookie-consent-webpixelstudio' ); ?></p>
				</div>
				<div class="ccwps-about-step">
					<h4><?php esc_html_e( 'Nastavenie tagov v GTM', 'cookie-consent-webpixelstudio' ); ?></h4>
					<p><?php esc_html_e( 'V GTM nastavte pre každý tag Consent Settings. Pre Google Analytics (GA4): analytics_storage = Required. Pre Google Ads: ad_storage, ad_user_data, ad_personalization = Required. Tagy sa spustia len po udelení príslušného súhlasu.', 'cookie-consent-webpixelstudio' ); ?></p>
				</div>
				<div class="ccwps-about-step">
					<h4><?php esc_html_e( 'Overenie pomocou Google Tag Assistant', 'cookie-consent-webpixelstudio' ); ?></h4>
					<p><?php esc_html_e( 'Otvorte Google Tag Assistant a overte, že pri odmietnutí cookies sú tagy v stave "Consent Blocked" a po udelení súhlasu sa aktivujú. Skontrolujte tiež Google Analytics Realtime report.', 'cookie-consent-webpixelstudio' ); ?></p>
				</div>
			</div>
		</div>

		<div class="ccwps-card">
			<h2><?php esc_html_e( 'Autor a podpora', 'cookie-consent-webpixelstudio' ); ?></h2>
			<p><?php esc_html_e( 'Plugin vytvoril Web Pixel Studio. Pre otázky, návrhy alebo hlásenie chýb nás kontaktujte na:', 'cookie-consent-webpixelstudio' ); ?> <a href="https://wps.sk" target="_blank" rel="noopener">wps.sk</a></p>
			<p style="margin-top:8px;"><?php esc_html_e( 'Plugin je bezplatný a open-source pod licenciou GPL-2.0. Postavený na:', 'cookie-consent-webpixelstudio' ); ?> <a href="https://cookieconsent.orestbida.com/" target="_blank" rel="noopener">cookieconsent.orestbida.com</a></p>
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
				'expiration'  => '1 year',
				'path'        => '/',
				'description' => 'Stores the visitor\'s consent choices for cookie categories so the plugin can respect and reapply them.',
				'is_regex'    => 0,
				'category'    => 'necessary',
			],
			[
				'name'        => 'ccwps_version',
				'domain'      => ltrim( $host, '.' ),
				'expiration'  => '6 months',
				'path'        => '/',
				'description' => 'Stores the current consent configuration version to detect changes and request consent again when needed.',
				'is_regex'    => 0,
				'category'    => 'necessary',
			],
		];

		foreach ( $required as $cookie ) {
			if ( in_array( strtolower( $cookie['name'] ), $names, true ) ) {
				continue;
			}

			$this->cookie_manager->insert( $cookie );
		}
	}

	private function get_font_family_choices(): array {
		$cache_key = 'ccwps_font_choices_v2_' . md5( home_url( '/' ) );
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
		$this->collect_font_choices_from_frontend( $choices );

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
			return (string) __( 'Použiť font témy', 'cookie-consent-webpixelstudio' );
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

				if ( false !== stripos( $css_url, '/cookie-consent-webpixelstudio/' ) ) {
					continue;
				}

				$asset_host = (string) wp_parse_url( $css_url, PHP_URL_HOST );
				if ( '' !== $asset_host && '' !== $home_host && $asset_host !== $home_host && 'fonts.googleapis.com' !== $asset_host ) {
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

	private function get_import_tmp_name(): string {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES['ccwps_import_file']['tmp_name'] ) ) {
			return '';
		}

		$tmp_name = sanitize_text_field( wp_unslash( $_FILES['ccwps_import_file']['tmp_name'] ) );
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing

		return $tmp_name;
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
			'_plugin' => 'cookie-consent-webpixelstudio',
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
		if ( ! is_array( $decoded ) || ( $decoded['_plugin'] ?? '' ) !== 'cookie-consent-webpixelstudio' || empty( $decoded['settings'] ) ) { wp_safe_redirect( $redirect . '&ccwps_imported=0' ); exit; }
		$allowed   = array_keys( $this->settings->get_all() );
		$color_keys = [
			'primary_color', 'text_color', 'bg_color', 'btn_text_color',
			'btn_primary_bg', 'btn_primary_bg_hv', 'btn_primary_txt',
			'btn_ghost_bg', 'btn_ghost_bg_hv', 'btn_ghost_txt', 'btn_ghost_border',
			'btn_outline_bg', 'btn_outline_bg_hv', 'btn_outline_txt', 'btn_outline_border',
			'modal_bg', 'modal_header_bg', 'modal_footer_bg', 'modal_border', 'modal_text',
			'cat_header_bg', 'cat_header_bg_hv', 'toggle_on_color', 'always_on_color',
		];
		$url_keys   = [ 'icon_custom_url' ];
		$html_keys  = [ 'lang_banner_description', 'lang_necessary_desc', 'lang_analytics_desc', 'lang_targeting_desc', 'lang_preferences_desc' ];
		foreach ( $decoded['settings'] as $key => $value ) {
			if ( ! in_array( $key, $allowed, true ) ) continue;
			if ( in_array( $key, $color_keys, true ) ) $value = ( $value === 'transparent' ) ? 'transparent' : sanitize_hex_color( $value );
			elseif ( in_array( $key, $url_keys, true ) ) $value = esc_url_raw( $value );
			elseif ( in_array( $key, $html_keys, true ) ) $value = wp_kses_post( $value );
			else $value = sanitize_text_field( $value );
			$this->settings->set( $key, $value );
		}

		if ( isset( $decoded['cookies'] ) && is_array( $decoded['cookies'] ) ) {
			$this->cookie_manager->replace_all( $decoded['cookies'] );
		}

		if ( isset( $decoded['block_rules'] ) && is_array( $decoded['block_rules'] ) ) {
			$this->block_manager->replace_all( $decoded['block_rules'] );
		}

		wp_safe_redirect( $redirect . '&ccwps_imported=1' ); exit;
	}
}
