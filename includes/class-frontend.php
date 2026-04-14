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
		$should_enqueue_head = $this->should_block_scripts() || in_array( $this->settings->get( 'consent_mode_version', 'v2' ), [ 'v2', 'v3' ], true );

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

		return '(function () {' .
			'var rules = ' . wp_json_encode( $rules ) . ' || [];' .
			'window.__ccwpsConsentState = ' . wp_json_encode( $consent ) . ';' .
			'if (!rules.length) return;' .
			'function getConsentState(){return window.__ccwpsConsentState || null;}' .
			'function hasConsent(cat){var consent=getConsentState();if(cat==="necessary") return true;return !!(consent && consent[cat]);}' .
			'function matches(src,rule){if(!src || !rule) return false;if(rule.isRegex){try{return new RegExp(rule.source,"i").test(src);}catch(e){return false;}}return String(src).toLowerCase().indexOf(String(rule.source).toLowerCase())!==-1;}' .
			'function neutralize(scriptEl){if(!scriptEl || !scriptEl.tagName || scriptEl.tagName.toLowerCase()!=="script") return;if(scriptEl.dataset && scriptEl.dataset.ccwpsHandled) return;var src=scriptEl.getAttribute("src") || scriptEl.src || "";if(!src) return;for(var i=0;i<rules.length;i++){var rule=rules[i];if(!hasConsent(rule.cat) && matches(src,rule)){scriptEl.dataset.ccwpsCat=rule.cat;scriptEl.dataset.ccwpsOrigSrc=src;scriptEl.dataset.ccwpsHandled="1";scriptEl.type="text/plain";scriptEl.removeAttribute("src");break;}}}' .
			'var appendChild=Node.prototype.appendChild;Node.prototype.appendChild=function(node){neutralize(node);return appendChild.apply(this,arguments);};' .
			'var insertBefore=Node.prototype.insertBefore;Node.prototype.insertBefore=function(node){neutralize(node);return insertBefore.apply(this,arguments);};' .
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
			echo '<div id="ccwps-floating-icon" aria-label="' . esc_attr__( 'Cookie settings', 'cookie-consent-webpixelstudio' ) . '" role="button" tabindex="0" style="display:none;"></div>' . "\n";
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
			<span class="ccwps-sc-id" data-ccwps-consent-id="1" data-empty-label="<?php echo esc_attr__( 'Súhlas nebol udelený.', 'cookie-consent-webpixelstudio' ); ?>">
				<?php if ( $consent_id ) : ?>
					<code class="ccwps-sc-id-code"><?php echo esc_html( $consent_id ); ?></code>
				<?php else : ?>
					<em class="ccwps-sc-id-empty"><?php esc_html_e( 'Súhlas nebol udelený.', 'cookie-consent-webpixelstudio' ); ?></em>
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
			return '<p>' . esc_html__( 'Žiadne cookies nie sú deklarované.', 'cookie-consent-webpixelstudio' ) . '</p>';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $atts['class'] ); ?>">
		<?php foreach ( $cookies_by_cat as $cat => $cookies ) :
			if ( empty( $cookies ) ) continue;
			?>
			<h3 class="ccwps-cl-cat-title"><?php echo esc_html( $cat_labels[ $cat ] ?? ucfirst( $cat ) ); ?></h3>
			<div class="ccwps-cl-table-wrap">
				<table class="ccwps-cl-table">
					<thead>
						<tr>
							<th><?php echo esc_html( $s->get( 'lang_cookie_name', 'Názov' ) ); ?></th>
							<th><?php echo esc_html( $s->get( 'lang_cookie_domain', 'Doména' ) ); ?></th>
							<th><?php echo esc_html( $s->get( 'lang_cookie_expiration', 'Platnosť' ) ); ?></th>
							<th><?php echo esc_html( $s->get( 'lang_cookie_description', 'Popis' ) ); ?></th>
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
		<style>
		.ccwps-cookie-list-table { font-size: 14px; line-height: 1.5; }
		.ccwps-cl-cat-title { font-size: 16px; font-weight: 700; margin: 20px 0 8px; }
		.ccwps-cl-table-wrap { overflow-x: auto; }
		.ccwps-cl-table { width: 100%; border-collapse: collapse; }
		.ccwps-cl-table th, .ccwps-cl-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
		.ccwps-cl-table th { background: #f9fafb; font-weight: 600; font-size: 13px; }
		.ccwps-cl-table tr:last-child td { border-bottom: none; }
		.ccwps-sc-regex { display: inline-block; background: #fef3c7; color: #92400e; font-size: 10px; padding: 1px 5px; border-radius: 3px; }
		.ccwps-consent-id-wrap code { background: #f3f4f6; padding: 2px 8px; border-radius: 4px; font-size: 13px; }
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * [ccwps_manage_consent] – renders a button that opens the cookie preferences modal.
	 */
	public function shortcode_manage_consent( array $atts ): string {
		$atts = shortcode_atts( [
			'label'  => $this->settings->get( 'lang_manage_preferences', 'Manage Preferences' ),
			'class'  => 'ccwps-manage-consent-btn',
			'id'     => '',
		], $atts, 'ccwps_manage_consent' );

		$id_attr    = $atts['id'] ? ' id="' . esc_attr( $atts['id'] ) . '"' : '';
		$class_attr = esc_attr( $atts['class'] );
		$label      = esc_html( $atts['label'] );

		return '<button type="button"' . $id_attr . ' class="' . $class_attr . '" data-ccwps-manage-consent="1" aria-label="' . $label . '">' . $label . '</button>';
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

		if ( ! empty( $rule['isRegex'] ) ) {
			return 1 === @preg_match( '/' . $pattern . '/i', $src );
		}

		return false !== stripos( $src, $pattern );
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
}
