<?php
/**
 * Frontend – enqueue assets, inject banner, shortcodes.
 *
 * @package CookieConsentWPS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CCWPS_Frontend {

	private CCWPS_Settings $settings;

	public function __construct( CCWPS_Settings $settings ) {
		$this->settings = $settings;
		add_action( 'template_redirect', [ $this, 'start_output_buffer' ], 0 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_footer', [ $this, 'output_banner' ] );

		// Shortcodes.
		add_shortcode( 'ccwps_consent_id',      [ $this, 'shortcode_consent_id' ] );
		add_shortcode( 'ccwps_cookie_list',     [ $this, 'shortcode_cookie_list' ] );
		add_shortcode( 'ccwps_manage_consent',  [ $this, 'shortcode_manage_consent' ] );
	}

	public function enqueue_assets(): void {
		if ( $this->settings->get( 'hide_from_bots' ) && $this->is_bot() ) {
			return;
		}

		$this->enqueue_head_assets();

		wp_enqueue_style(
			'ccwps-frontend',
			CCWPS_PLUGIN_URL . 'public/css/frontend.css',
			[],
			CCWPS_VERSION
		);

		wp_enqueue_script(
			'ccwps-frontend',
			CCWPS_PLUGIN_URL . 'public/js/frontend.js',
			[],
			CCWPS_VERSION,
			true
		);

		$config = $this->settings->get_frontend_config();

		$cookie_manager = new CCWPS_Cookie_Manager();
		$block_manager  = new CCWPS_Block_Manager();
		$config['cookies']       = $cookie_manager->get_grouped();
		$config['blockingRules'] = $block_manager->get_rules_for_frontend();

		wp_localize_script( 'ccwps-frontend', 'ccwpsConfig', $config );
	}

	private function enqueue_head_assets(): void {
		$should_enqueue_head = $this->should_block_scripts() || in_array( $this->settings->get( 'consent_mode_version', 'v2' ), [ 'v2', 'v3' ], true ) || $this->is_matomo_configured();

		if ( ! $should_enqueue_head ) {
			return;
		}

		wp_enqueue_script(
			'ccwps-head',
			CCWPS_PLUGIN_URL . 'public/js/head.js',
			[],
			CCWPS_VERSION,
			false
		);

		$consent_mode_js = $this->get_consent_mode_js();
		if ( '' !== $consent_mode_js ) {
			wp_add_inline_script( 'ccwps-head', $consent_mode_js, 'before' );
		}

		$matomo_bootstrap_js = $this->get_matomo_bootstrap_js();
		if ( '' !== $matomo_bootstrap_js ) {
			wp_add_inline_script( 'ccwps-head', $matomo_bootstrap_js, 'before' );
		}

		$blocker_bootstrap_js = $this->get_script_blocker_bootstrap_js();
		if ( '' !== $blocker_bootstrap_js ) {
			wp_add_inline_script( 'ccwps-head', $blocker_bootstrap_js, 'before' );
		}
	}

	/**
	 * Start output buffering so static script tags can be neutralized before they execute.
	 */
	public function start_output_buffer(): void {
		if ( ! $this->should_block_scripts() ) {
			return;
		}

		ob_start( [ $this, 'rewrite_blocked_scripts_in_html' ] );
		add_action( 'shutdown', [ $this, 'end_output_buffer' ], 0 );
	}

	/**
	 * Explicitly close the output buffer opened in start_output_buffer().
	 * Called on 'shutdown' so the buffer is always paired with an explicit close.
	 */
	public function end_output_buffer(): void {
		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	}

	/**
	 * Emit an early bootstrap that intercepts dynamically inserted scripts.
	 */
	private function get_script_blocker_bootstrap_js(): string {
		if ( ! $this->should_block_scripts() ) {
			return '';
		}

		$block_manager = new CCWPS_Block_Manager();
		$rules = $block_manager->get_rules_for_frontend();
		if ( empty( $rules ) ) {
			return '';
		}

		$consent = $this->get_consent_from_cookie();
		$plugin_assets_path = (string) ( wp_parse_url( CCWPS_PLUGIN_URL . 'public/js/', PHP_URL_PATH ) ?: '' );
		$allow_matomo_without_consent = (bool) $this->settings->get( 'matomo_anonymous_without_consent', 0 );
		$matomo_host = $this->get_matomo_host();

		return '(function () {' .
			'var rules = ' . wp_json_encode( $rules ) . ' || [];' .
			'var pluginAssetsPath = ' . wp_json_encode( $plugin_assets_path ) . ';' .
			'var allowMatomoWithoutConsent = ' . wp_json_encode( $allow_matomo_without_consent ) . ';' .
			'var matomoHost = ' . wp_json_encode( $matomo_host ) . ';' .
			'window.__ccwpsConsentState = ' . wp_json_encode( $consent ) . ';' .
			'if (!rules.length) return;' .
			'function getConsentState(){return window.__ccwpsConsentState || null;}' .
			'function hasConsent(cat){var consent=getConsentState();if(cat==="necessary") return true;return !!(consent && consent[cat]);}' .
			'function matches(src,rule){if(!src || !rule) return false;if(rule.isRegex){try{return new RegExp(rule.source,"i").test(src);}catch(e){return false;}}return String(src).toLowerCase().indexOf(String(rule.source).toLowerCase())!==-1;}' .
			'function isPluginAsset(src){if(!pluginAssetsPath || !src) return false;try{var u=new URL(src,window.location.href);return String(u.pathname||"").indexOf(pluginAssetsPath)!==-1;}catch(e){return String(src).indexOf(pluginAssetsPath)!==-1;}}' .
			'function isMatomoScriptAllowed(src,cat){if(cat!=="analytics" || !allowMatomoWithoutConsent || !matomoHost || !src) return false;try{var u=new URL(src,window.location.href);var h=String(u.hostname||"").replace(/^www\\./i,"").toLowerCase();var mh=String(matomoHost).replace(/^www\\./i,"").toLowerCase();return h===mh;}catch(e){return String(src).toLowerCase().indexOf(String(matomoHost).toLowerCase())!==-1;}}' .
			'function neutralize(scriptEl){if(!scriptEl || !scriptEl.tagName || scriptEl.tagName.toLowerCase()!=="script") return;if(scriptEl.dataset && scriptEl.dataset.ccwpsHandled) return;var src=scriptEl.getAttribute("src") || scriptEl.src || "";if(!src || isPluginAsset(src)) return;for(var i=0;i<rules.length;i++){var rule=rules[i];if(!hasConsent(rule.cat) && matches(src,rule) && !isMatomoScriptAllowed(src,rule.cat)){scriptEl.dataset.ccwpsCat=rule.cat;scriptEl.dataset.ccwpsOrigSrc=src;scriptEl.dataset.ccwpsHandled="1";scriptEl.type="text/plain";scriptEl.removeAttribute("src");break;}}}' .
			'var appendChild=Node.prototype.appendChild;Node.prototype.appendChild=function(node){neutralize(node);return appendChild.apply(this,arguments);};' .
			'var insertBefore=Node.prototype.insertBefore;Node.prototype.insertBefore=function(node){neutralize(node);return insertBefore.apply(this,arguments);};' .
		'})();';
	}

	private function get_matomo_bootstrap_js(): string {
		if ( ! $this->is_matomo_configured() ) {
			return '';
		}

		$matomo_url = trailingslashit( (string) $this->settings->get( 'matomo_url', '' ) );
		$matomo_php = $matomo_url . 'matomo.php';
		$matomo_js = $matomo_url . 'matomo.js';
		$site_id = (int) $this->settings->get( 'matomo_site_id', 0 );
		$allow_anonymous_without_consent = (bool) $this->settings->get( 'matomo_anonymous_without_consent', 0 );
		$consent = $this->get_consent_from_cookie();
		$analytics_granted = (bool) ( is_array( $consent ) && ! empty( $consent['analytics'] ) );

		return '(function(){' .
			'var matomoPhp=' . wp_json_encode( $matomo_php ) . ';' .
			'var matomoJs=' . wp_json_encode( $matomo_js ) . ';' .
			'var siteId=' . wp_json_encode( (string) $site_id ) . ';' .
			'var allowAnonymous=' . wp_json_encode( $allow_anonymous_without_consent ) . ';' .
			'var hasAnalyticsConsent=' . wp_json_encode( $analytics_granted ) . ';' .
			'var _paq=window._paq=window._paq||[];' .
			'var strictInitialized=false;' .
			'function configureBase(){_paq.push(["setTrackerUrl",matomoPhp]);_paq.push(["setSiteId",siteId]);_paq.push(["enableLinkTracking"]);}' .
			'function ensureMatomoScript(){if(window.__ccwpsMatomoScriptLoaded){return;}window.__ccwpsMatomoScriptLoaded=true;var d=document,g=d.createElement("script"),s=d.getElementsByTagName("script")[0];g.async=true;g.src=matomoJs;s.parentNode.insertBefore(g,s);}' .
			'function initStrictIfNeeded(){if(strictInitialized){return;}strictInitialized=true;configureBase();_paq.push(["requireConsent"]);}' .
			'if(allowAnonymous){configureBase();_paq.push(["requireCookieConsent"]);if(hasAnalyticsConsent){_paq.push(["setCookieConsentGiven"]);}else{_paq.push(["forgetCookieConsentGiven"]);}ensureMatomoScript();_paq.push(["trackPageView"]);}else if(hasAnalyticsConsent){initStrictIfNeeded();_paq.push(["setConsentGiven"]);ensureMatomoScript();_paq.push(["trackPageView"]);}' .
			'window.addEventListener("ccwps:consent-updated",function(event){var detail=(event&&event.detail)||{};var prefs=(detail&&detail.prefs)?detail.prefs:detail;var granted=!!(prefs&&prefs.analytics);if(allowAnonymous){if(granted){_paq.push(["setCookieConsentGiven"]);}else{_paq.push(["forgetCookieConsentGiven"]);}ensureMatomoScript();_paq.push(["trackPageView"]);return;}if(granted){initStrictIfNeeded();_paq.push(["setConsentGiven"]);ensureMatomoScript();_paq.push(["trackPageView"]);}else if(strictInitialized){_paq.push(["forgetConsentGiven"]);}});' .
		'})();';
	}

	/**
	 * Output Google Consent Mode default state in <head>.
	 */
	private function get_consent_mode_js(): string {
		$mode = $this->settings->get( 'consent_mode_version', 'v2' );
		if ( ! in_array( $mode, [ 'v2', 'v3' ], true ) ) {
			return '';
		}

		$gtm_id = $this->settings->get( 'gtm_id' );

		$script = 'window.dataLayer = window.dataLayer || [];' .
			'function gtag(){dataLayer.push(arguments);}' .
			'gtag("consent", "default", {' .
			'"ad_storage":"denied",' .
			'"ad_user_data":"denied",' .
			'"ad_personalization":"denied",' .
			'"analytics_storage":"denied",' .
			'"functionality_storage":"denied",' .
			'"personalization_storage":"denied",' .
			'"security_storage":"granted",' .
			'"wait_for_update":500' .
			'});' .
			'gtag("set", "ads_data_redaction", true);' .
			'gtag("set", "url_passthrough", true);';

		if ( 'v3' === $mode ) {
			$script .= 'gtag("set", "developer_id.dZTNiMT", true);';
		}

		if ( $gtm_id ) {
			$script .= '(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({"gtm.start":new Date().getTime(),event:"gtm.js"});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!=="dataLayer"?"&l="+l:"";j.async=true;j.src="https://www.googletagmanager.com/gtm.js?id="+i+dl;f.parentNode.insertBefore(j,f);})(window,document,"script","dataLayer",' . wp_json_encode( $gtm_id ) . ');';
		}

		return $script;
	}

	/**
	 * Rewrite static script tags in buffered HTML to text/plain placeholders until consent is granted.
	 */
	public function rewrite_blocked_scripts_in_html( string $html ): string {
		$script_open_token  = '<scr' . 'ipt';
		$script_close_token = '</scr' . 'ipt>';

		if ( false === stripos( $html, $script_open_token ) ) {
			return $html;
		}

		$block_manager = new CCWPS_Block_Manager();
		$rules = $block_manager->get_rules_for_frontend();
		if ( empty( $rules ) ) {
			return $html;
		}

		$consent = $this->get_consent_from_cookie();

		return preg_replace_callback(
			'/' . preg_quote( $script_open_token, '/' ) . '\\b([^>]*?)\\bsrc=("|\')(.*?)\\2([^>]*)>\\s*' . preg_quote( $script_close_token, '/' ) . '/is',
			function ( array $matches ) use ( $rules, $consent, $script_open_token, $script_close_token ): string {
				$src = html_entity_decode( $matches[3], ENT_QUOTES, 'UTF-8' );
				$category = null;
				foreach ( $rules as $rule ) {
					if ( ! $this->matches_block_rule( $src, $rule ) ) {
						continue;
					}
					if ( 'analytics' === (string) $rule['cat'] && $this->should_allow_matomo_analytics_without_consent( $src ) ) {
						return $matches[0];
					}
					if ( ! $this->should_block_category( (string) $rule['cat'], $consent ) ) {
						return $matches[0];
					}
					$category = (string) $rule['cat'];
					break;
				}

				if ( null === $category ) {
					return $matches[0];
				}

				$attrs = trim( preg_replace( '/\stype=("|\').*?\1/i', '', $matches[1] . ' ' . $matches[4] ) );
				$attrs = preg_replace( '/\sdata-ccwps-[^=]+=("|\').*?\1/i', '', $attrs );

				return sprintf(
					$script_open_token . ' %1$s type="text/plain" data-ccwps-cat="%2$s" data-ccwps-orig-src="%3$s" data-ccwps-handled="1">' . $script_close_token,
					trim( $attrs ),
					esc_attr( $category ),
					esc_attr( $src )
				);
			},
			$html
		) ?? $html;
	}

	/**
	 * Output banner HTML shell.
	 */
	public function output_banner(): void {
		if ( $this->settings->get( 'hide_from_bots' ) && $this->is_bot() ) {
			return;
		}
		echo '<div id="ccwps-banner-wrap" aria-hidden="true"></div>' . "\n";
		echo '<div id="ccwps-modal-wrap" aria-hidden="true"></div>' . "\n";
		echo '<div id="ccwps-tip-wrap"></div>' . "\n";

		if ( $this->settings->get( 'banner_show_icon' ) ) {
				echo '<div id="ccwps-floating-icon" aria-label="' . esc_attr__( 'Cookie settings', 'web-pixel-studio-cookie-consent-eu' ) . '" role="button" tabindex="0" style="display:none;"></div>' . "\n";
		}

		$gtm_id = $this->settings->get( 'gtm_id' );
		$mode   = $this->settings->get( 'consent_mode_version', 'v2' );
		if ( $gtm_id && in_array( $mode, [ 'v2', 'v3' ], true ) ) {
			echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( $gtm_id ) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
		}
	}

	/* ===================== SHORTCODES ===================== */

	/**
	 * [ccwps_consent_id] – shows the current user's consent ID.
	 */
	public function shortcode_consent_id( array $atts ): string {
		$atts = shortcode_atts( [
			'label'   => $this->settings->get( 'lang_consent_id_label', 'ID vášho súhlasu' ),
			'wrapper' => 'p',
			'class'   => 'ccwps-consent-id-wrap',
		], $atts, 'ccwps_consent_id' );

		$tag = sanitize_key( $atts['wrapper'] );
		if ( ! in_array( $tag, [ 'p', 'div', 'span' ], true ) ) {
			$tag = 'p';
		}

		$consent    = $this->get_consent_from_cookie();
		$consent_id = is_array( $consent ) && ! empty( $consent['id'] ) ? sanitize_text_field( (string) $consent['id'] ) : '';

		ob_start();
		?>
		<<?php echo esc_attr( $tag ); ?> class="<?php echo esc_attr( $atts['class'] ); ?>">
			<?php if ( $atts['label'] ) : ?>
				<strong><?php echo esc_html( $atts['label'] ); ?>:</strong>
			<?php endif; ?>
			<span class="ccwps-sc-id" data-ccwps-consent-id="1" data-empty-label="<?php echo esc_attr__( 'Súhlas nebol udelený.', 'web-pixel-studio-cookie-consent-eu' ); ?>">
				<?php if ( $consent_id ) : ?>
					<code class="ccwps-sc-id-code"><?php echo esc_html( $consent_id ); ?></code>
				<?php else : ?>
						<em class="ccwps-sc-id-empty"><?php esc_html_e( 'Súhlas nebol udelený.', 'web-pixel-studio-cookie-consent-eu' ); ?></em>
				<?php endif; ?>
			</span>
		</<?php echo esc_attr( $tag ); ?>>
		<?php
		return ob_get_clean();
	}

	/**
	 * [ccwps_cookie_list] – renders declared cookies grouped by category.
	 */
	public function shortcode_cookie_list( array $atts ): string {
		$atts = shortcode_atts( [
			'category' => '', // empty = all categories
			'class'    => 'ccwps-cookie-list-table',
		], $atts, 'ccwps_cookie_list' );

		$manager  = new CCWPS_Cookie_Manager();
		$s        = $this->settings;

		if ( $atts['category'] ) {
			$cookies_by_cat = [ sanitize_key( $atts['category'] ) => $manager->get_by_category( sanitize_key( $atts['category'] ) ) ];
		} else {
			$all = $manager->get_all();
			$cookies_by_cat = [];
			foreach ( $all as $ck ) {
				$cookies_by_cat[ $ck['category'] ][] = $ck;
			}
		}

		$cat_labels = [
			'necessary'   => $s->get( 'lang_necessary_title',   'Nevyhnutné' ),
			'analytics'   => $s->get( 'lang_analytics_title',   'Analytické' ),
			'targeting'   => $s->get( 'lang_targeting_title',   'Marketingové' ),
			'preferences' => $s->get( 'lang_preferences_title', 'Preferenčné' ),
		];

		if ( empty( array_filter( $cookies_by_cat ) ) ) {
			return '<p>' . esc_html__( 'Žiadne cookies nie sú deklarované.', 'web-pixel-studio-cookie-consent-eu' ) . '</p>';
		}

		// Store labels for JS localization when detectVisitorLanguage is enabled
		$detect_visitor_lang = $s->get( 'frontend_detect_visitor_language' ) ? 'true' : 'false';
		$data_attrs = ' data-ccwps-cookie-list="true" data-ccwps-detect-lang="' . esc_attr( $detect_visitor_lang ) . '"';
		$data_attrs .= ' data-label-necessary="' . esc_attr( $cat_labels['necessary'] ) . '"';
		$data_attrs .= ' data-label-analytics="' . esc_attr( $cat_labels['analytics'] ) . '"';
		$data_attrs .= ' data-label-targeting="' . esc_attr( $cat_labels['targeting'] ) . '"';
		$data_attrs .= ' data-label-preferences="' . esc_attr( $cat_labels['preferences'] ) . '"';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $atts['class'] ); ?>"<?php echo wp_kses_data( $data_attrs ); ?>>
		<?php foreach ( $cookies_by_cat as $cat => $cookies ) :
			if ( empty( $cookies ) ) continue;
			?>
			<h3 class="ccwps-cl-cat-title" data-ccwps-cat="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( $cat_labels[ $cat ] ?? ucfirst( $cat ) ); ?></h3>
			<div class="ccwps-cl-table-wrap">
				<table class="ccwps-cl-table">
					<thead>
						<tr>
							<th data-ccwps-col="name"><?php echo esc_html( $s->get( 'lang_cookie_name', 'Názov' ) ); ?></th>
							<th data-ccwps-col="domain"><?php echo esc_html( $s->get( 'lang_cookie_domain', 'Doména' ) ); ?></th>
							<th data-ccwps-col="expiration"><?php echo esc_html( $s->get( 'lang_cookie_expiration', 'Platnosť' ) ); ?></th>
							<th data-ccwps-col="description"><?php echo esc_html( $s->get( 'lang_cookie_description', 'Popis' ) ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $cookies as $ck ) : ?>
						<tr>
							<td><code><?php echo esc_html( $ck['name'] ); ?></code><?php if ( $ck['is_regex'] ) echo ' <span class="ccwps-sc-regex">regex</span>'; ?></td>
							<td><?php echo esc_html( $ck['domain'] ); ?></td>
							<td><?php echo esc_html( $ck['expiration'] ); ?></td>
							<td><?php echo esc_html( $ck['description'] ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * [ccwps_manage_consent] – renders a button that opens the cookie preferences modal.
	 */
	public function shortcode_manage_consent( array $atts ): string {
		$atts = shortcode_atts( [
			'label'  => $this->settings->get( 'lang_manage_preferences', 'Customize' ),
			'class'  => 'ccwps-manage-consent-btn',
			'id'     => '',
		], $atts, 'ccwps_manage_consent' );

		$id_attr    = $atts['id'] ? ' id="' . esc_attr( $atts['id'] ) . '"' : '';
		$class_attr = esc_attr( $atts['class'] );
		$label      = esc_html( $atts['label'] );

		// Helper pro validaci barvy
		$sanitize_color = function($val, $default) {
			$val = trim((string)$val);
			if ($val === '' || !preg_match('/^#([A-Fa-f0-9]{3,8})$/', $val)) return $default;
			return $val;
		};

		// Helper pro validaci čísla
		$sanitize_int = function($val, $default, $min = null, $max = null) {
			if (!is_numeric($val)) return $default;
			$val = (int)$val;
			if ($min !== null && $val < $min) return $default;
			if ($max !== null && $val > $max) return $default;
			return $val;
		};

		$bg         = $sanitize_color($this->settings->get('manage_btn_bg', '#1a73e8'), '#1a73e8');
		$txt        = $sanitize_color($this->settings->get('manage_btn_txt', '#fff'), '#fff');
		$border     = $sanitize_color($this->settings->get('manage_btn_border', '#1a73e8'), '#1a73e8');
		$bg_hv      = $sanitize_color($this->settings->get('manage_btn_bg_hv', '#1557b0'), '#1557b0');
		$txt_hv     = $sanitize_color($this->settings->get('manage_btn_txt_hv', '#fff'), '#fff');
		$border_hv  = $sanitize_color($this->settings->get('manage_btn_border_hv', '#1557b0'), '#1557b0');
		$border_w   = $sanitize_int($this->settings->get('manage_btn_border_width', 2), 2, 0, 10);
		$radius     = $sanitize_int($this->settings->get('manage_btn_radius', 8), 8, 0, 50);

		$style = sprintf(
			'--ccwps-manage-btn-bg:%1$s;--ccwps-manage-btn-txt:%2$s;--ccwps-manage-btn-border:%3$s;--ccwps-manage-btn-border-width:%4$spx;--ccwps-manage-btn-radius:%5$spx;--ccwps-manage-btn-bg-hv:%6$s;--ccwps-manage-btn-txt-hv:%7$s;--ccwps-manage-btn-border-hv:%8$s;',
			$bg,
			$txt,
			$border,
			$border_w,
			$radius,
			$bg_hv,
			$txt_hv,
			$border_hv
		);
		$style_attr = ' style="' . esc_attr( $style ) . '"';

		return '<button type="button"' . $id_attr . ' class="' . $class_attr . '"' . $style_attr . ' data-ccwps-manage-consent="1" aria-label="' . $label . '">' . $label . '</button>';
	}

	private function is_bot(): bool {
		$ua   = strtolower( sanitize_text_field( $this->get_server_value( 'HTTP_USER_AGENT' ) ) );
		$bots = [ 'bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'googlebot', 'bingbot', 'yandex', 'baidu', 'duckduck', 'facebot', 'ia_archiver', 'msnbot', 'teoma', 'phantomjs', 'headless', 'selenium', 'webdriver' ];
		foreach ( $bots as $b ) {
			if ( strpos( $ua, $b ) !== false ) return true;
		}
		return false;
	}

	private function should_block_scripts(): bool {
		if ( is_admin() || wp_doing_ajax() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( is_feed() || is_trackback() || is_robots() ) {
			return false;
		}

		if ( ! $this->settings->get( 'page_scripts' ) ) {
			return false;
		}

		if ( $this->settings->get( 'hide_from_bots' ) && $this->is_bot() ) {
			return false;
		}

		return true;
	}

	private function get_consent_from_cookie(): ?array {
		$raw = $this->get_raw_cookie_value( 'ccwps_consent' );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return null;
		}

		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}

		$decoded = json_decode( rawurldecode( $raw ), true );
		return is_array( $decoded ) ? $decoded : null;
	}

	private function should_block_category( string $category, ?array $consent ): bool {
		if ( 'necessary' === $category ) {
			return false;
		}

		if ( ! is_array( $consent ) ) {
			return true;
		}

		return empty( $consent[ $category ] );
	}

	private function matches_block_rule( string $src, array $rule ): bool {
		$pattern = (string) ( $rule['source'] ?? '' );
		if ( '' === $src || '' === $pattern ) {
			return false;
		}

		if ( $this->is_internal_plugin_script_src( $src ) ) {
			return false;
		}

		if ( ! empty( $rule['isRegex'] ) ) {
			return 1 === @preg_match( '/' . $pattern . '/i', $src );
		}

		return false !== stripos( $src, $pattern );
	}

	private function is_internal_plugin_script_src( string $src ): bool {
		$src_path = wp_parse_url( $src, PHP_URL_PATH );
		$plugin_assets_path = wp_parse_url( CCWPS_PLUGIN_URL . 'public/js/', PHP_URL_PATH );

		if ( ! is_string( $src_path ) || ! is_string( $plugin_assets_path ) || '' === $src_path || '' === $plugin_assets_path ) {
			return false;
		}

		return false !== strpos( $src_path, $plugin_assets_path );
	}

	private function get_server_value( string $key ): string {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_SERVER[ $key ] ) || is_array( $_SERVER[ $key ] ) ) {
			return '';
		}

		$value = wp_unslash( $_SERVER[ $key ] );
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return $value;
	}

	private function get_raw_cookie_value( string $key ): ?string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw JSON cookie is decoded and validated before use.
		if ( ! isset( $_COOKIE[ $key ] ) || ! is_string( $_COOKIE[ $key ] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw JSON cookie is decoded and validated before use.
		return wp_unslash( $_COOKIE[ $key ] );
	}

	private function is_matomo_configured(): bool {
		$matomo_url = trim( (string) $this->settings->get( 'matomo_url', '' ) );
		$site_id = (int) $this->settings->get( 'matomo_site_id', 0 );

		return '' !== $matomo_url && $site_id > 0;
	}

	private function get_matomo_host(): string {
		$matomo_url = (string) $this->settings->get( 'matomo_url', '' );

		return (string) ( wp_parse_url( $matomo_url, PHP_URL_HOST ) ?: '' );
	}

	private function should_allow_matomo_analytics_without_consent( string $src ): bool {
		if ( ! $this->settings->get( 'matomo_anonymous_without_consent', 0 ) ) {
			return false;
		}

		$matomo_host = strtolower( preg_replace( '/^www\./i', '', $this->get_matomo_host() ) );
		if ( '' === $matomo_host ) {
			return false;
		}

		$src_host = (string) ( wp_parse_url( $src, PHP_URL_HOST ) ?: '' );
		if ( '' !== $src_host ) {
			$src_host = strtolower( preg_replace( '/^www\./i', '', $src_host ) );
			return $src_host === $matomo_host;
		}

		return false !== stripos( $src, $matomo_host );
	}
}
